# Beaker Breakers Studio — backend

Laravel 11 API and Filament administrator panel. This is milestone **M1** of
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
| Administrator | `admin@beakerbreakers.test` |
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

These are later milestones, not oversights:

- **Teacher endpoints** (`FR-09.*`): class monitor, task assignment, safety
  review. The `tasks`, `task_assignments` and `class_teachers` tables and the
  `AssignmentState` machine are in place; the controllers are not.
- **AI readiness reporting** (`FR-08.*`): table and the six dimensions exist;
  the computation does not.
- **Administrator reporting** (`FR-10.3` to `FR-10.7`): CSV export, usage
  reports, licence reports.
- **Notifications and FCM** (`FR-09.6`, `FR-09.7`): tables only.
- **Experiment library** (`FR-03.1`): 6 of the 30 experiments are written.
  Adding the rest is content work in `ExperimentSeeder`, not code.
- **Redis** (`NFR-02`, `FR-07.2`): `.env` is on the database driver. Switch
  `CACHE_STORE` and `QUEUE_CONNECTION` to `redis` once Redis is running.
