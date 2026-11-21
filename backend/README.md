# Beaker Breakers Studio — backend

Laravel 12 API and Filament administrator panel. This is milestone **M1** of
`docs/build-order.md`: accounts, safety certification, the experiment runner,
quizzes, rewards and the leaderboard, with the administrator panel over all of it.

Requirement IDs in the comments are not decoration. They point at
`docs/requirements.md`, and they are how a reviewer checks that the code still
does what the report says it does.

---

## What you need installed

| Tool | Version | Check with |
|---|---|---|
| PHP | 8.2 or newer | `php -v` |
| Composer | 2.x | `composer -V` |
| MySQL | 8.x | `mysql --version` |
| Node | 18 or newer (for the web app, not this one) | `node -v` |

On Windows the quickest route to PHP and MySQL together is **Laragon** or
**XAMPP**. Make sure these PHP extensions are on, which both enable by default:
`pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `curl`, `zip`, `intl`, and
`pdo_sqlite` (the test suite runs on in-memory SQLite).

---

## First run

```bash
cd backend
composer install
cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate
```

Create the database once, then migrate and seed:

```sql
CREATE DATABASE beaker_breakers CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
php artisan migrate --seed
php artisan serve
```

The API is then at `http://localhost:8000/api` and the administrator panel at
`http://localhost:8000/admin`.

If Filament's styles do not load, publish its assets once:

```bash
php artisan filament:assets
```

### Seeded accounts

Local development only. Every one of them has the password `password`, which is
why this seeder must never run anywhere a student can reach.

| Role | Email |
|---|---|
| Team administrator (every school) | `admin@beakerbreakers.test` |
| School administrator (the demo school only) | `schooladmin@beakerbreakers.test` |
| Teacher | `teacher@beakerbreakers.test` |
| Student | `rabail@beakerbreakers.test` |
| Student | `mohsin@beakerbreakers.test` |

The seed also loads 40 safety questions across the four tiers and 6 experiments
with their steps, equations, guidance and quizzes.

---

## Run the tests

```bash
php artisan test
```

`tests/Feature/NonNegotiableRulesTest.php` is the one to watch. It holds the
five rules from `CLAUDE.md` as executable checks: a client-supplied score is
ignored, guidance survives an absent API key, there is no registration route,
points never fall and are awarded once, and an issued certificate cannot be
edited or deleted. A failure there is a defect in the build, not in the test.

### Coverage (`NFR-10`)

`NFR-10` asks for 100 percent on authentication, score computation, reward award
and safety certification, and 70 percent overall. It needs a coverage driver
(PCOV or Xdebug) enabled in PHP:

```bash
php -d memory_limit=1G vendor/bin/phpunit --coverage-text
```

Measured on 8 Nov 2026 (96 tests, SQLite): 75.7 percent of lines overall, and
100 percent of lines in `AuthController`, `EnsureRole`, `FilamentAdminsOnly`,
`SafetyController`, `QuizController`, `SafetyCertificationService`,
`RewardService`, `User`, `StudentProfile`, `Certificate`, `AuditLog` and
`InsertOnly`. Re-measure after changing any of them; the figure is only true for
the code it was taken on.

---

## Where things are

```
app/
  Enums/              UserRole, AssignmentState (FR-09.5 transitions live in the enum)
  Models/             19 entities, plus QuizAttempt
    Concerns/InsertOnly.php   raises on update or delete (FR-02.8, FR-10.8)
  Services/
    SafetyCertificationService.php   FR-02.2, 02.4, 02.5, 02.7, 02.8
    RewardService.php                FR-04.3, FR-06.2, 06.3, 06.4, 06.5
    GuidanceService.php              FR-03.7 and the NFR-06 fallback
    ExperimentAccessService.php      FR-02.3 and FR-03.3, with the reason
  Http/Controllers/Api/              the endpoints in docs/api.md
  Http/Middleware/EnsureRole.php     SR-04
  Filament/Resources/                the administrator panel, S16 to S18
database/
  migrations/         schema for all 20 tables
  seeders/            safety bank, experiment library, demo school
tests/Feature/        the rules, and the student journey
```

## Two things to know before you change anything

**Scores are computed from the stored answer key, in a service, and nowhere
else.** `SafetyCertificationService::grade()` does not take a score parameter.
Neither does `QuizController::submit()` read one from the request. That is
`FR-02.5` and `SR-07`, and a certificate is only worth something because of it.

**`experiment_steps.preauthored_guidance` is NOT NULL.** `GuidanceService`
falls back to it on an empty API key, a timeout, an error or an empty answer, so
every experiment stays completable with the AI service down (`NFR-06`). Prove it
whenever you touch that class: empty `GEMINI_API_KEY` in `.env` and run a whole
experiment through.

---

## Not built yet

These wait on keys or a server, not on code decisions:

- **Push notifications** (`FR-09.6`): in-app notifications work; Firebase Cloud
  Messaging is not wired until there is a Firebase project and key.
- **Email delivery**: `MAIL_MAILER=log` writes every email (welcome, password
  reset) to `storage/logs/laravel.log` instead of sending it. Production needs the
  SendGrid settings (`docs/deploy.md`, step 2).
- **PDF export** (`FR-10.7` says PDF *or* delimited text): reports and the
  certificate register export as CSV. PDF would need a PDF library.
- **Redis** (`NFR-02`, `FR-07.2`): `.env` is on the database driver. Switch
  `CACHE_STORE` and `QUEUE_CONNECTION` to `redis` once Redis is running.

---

## Onboarding a school

Administrators come in two kinds, with one Administrator role (`FR-01.1`):

| | `school_id` | Can do |
|---|---|---|
| **Team administrator** (us) | empty | Every school; schools, their board and licence; the experiment library |
| **School administrator** | the school | Its own school only (`SR-04`): teachers, students, classes, turning experiments off for a class (`FR-10.4`), CSV import (`FR-10.2`), certificate register (`FR-10.5`), school report (`FR-10.6`) |

A school administrator cannot open Schools or Experiments, so it cannot extend its
own licence. Onboarding is run by the team on the server:

```bash
php artisan school:onboard "Government High School, Gujrat" --district=Gujrat \
    --board=punjab --classes=9,10 --from=2026-12-01 --until=2027-11-30 --cap=400 \
    --admin-name="Ms Ayesha" --admin-email=admin@ghs-gujrat.edu.pk
```

That makes the school and its first administrator, and emails the administrator a
link to choose a password (valid once, for 60 minutes; after that, "Forgot
password"). Add `--print-link` to print the link instead of emailing it, for when
email is not set up yet. `php artisan school:onboard --team --admin-name=... --admin-email=...`
makes a team administrator.

The school administrator then adds teachers and students in the panel, one at a
time or from a CSV file (Import students: `name,email,class`, up to 500 rows; one
bad row and nothing is created). An account made without a typed password is
emailed the same kind of link, so no administrator ever knows a student's password.
