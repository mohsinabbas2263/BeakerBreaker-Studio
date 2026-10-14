# Beaker Breakers Studio

AI powered gamified virtual chemistry laboratory portal with augmented reality, for
secondary schools. Final Year Project, BSCS, The University of Chenab, Gujrat — and
intended for real deployment in schools afterwards, not only for the defence.

Team: Rabail Mehboob (frontend, documentation), Syed Mohsin Abbas (backend).
Supervisor: Dr. Waqas Ahmed.

---

## Status

Chapters 1 to 4 are written and signed off. Development started in October 2026.

| Part | State |
|---|---|
| `backend/` | **M1 written, not yet run.** Schema, auth, safety certification, experiment runner, quizzes, rewards, leaderboard, Filament admin panel, and tests for the five rules below. It has never been executed: it was written where Composer could not reach Packagist, so every file is syntax checked and nothing is runtime checked. **First job: `composer install` and `php artisan migrate --seed`, then `php artisan test`.** Expect to fix a few things on that first run. |
| `Beaker Breakers Studio (2)/` | A working **UI prototype**: vanilla HTML, CSS and JavaScript, 9 student screens, all data hardcoded in `data.js`. No auth, no teacher or admin screens, no backend. It is the visual source of truth, not the application. |
| `web/` | Not started. M2. |
| `mobile/` | Not started. Phase 2, after the web portal is in schools. |

---

## Read these before writing code

| File | What it holds |
|---|---|
| `docs/requirements.md` | All 85 requirements with their IDs. The source of truth. |
| `docs/use-cases.md` | 28 use cases with flows and preconditions. |
| `docs/architecture.md` | Tiers, components, stack, external services, performance targets. |
| `docs/data-model.md` | The entities, attributes, schema rules, state machines. |
| `docs/api.md` | The endpoints M1 actually exposes, with their status codes. |
| `docs/build-order.md` | The milestones in dependency order. |
| `backend/README.md` | How to get the backend running, and what is deliberately missing. |

Diagrams are in `FYP/` and `Documentation/UML Diagrams/`. Screen layouts are in
`FYP/Wire framing & error Handling/` as `wf_S01` to `wf_S18`, and the prototype in
`Beaker Breakers Studio (2)/` shows how nine of them actually look and move.

The Word chapters in `Documentation/` and the repository root are the submitted versions.
Treat the markdown in `docs/` as the working copy; if the two disagree, the Word chapters
win and the markdown needs fixing — except where this file records a deliberate deviation.

---

## Requirement IDs

Every requirement has a stable ID: `FR-03.7`, `NFR-06`, `SR-01`. These IDs appear in the
report, in the diagrams and in the SRS. **Never renumber them.** An earlier numbering
scheme used `IR-`, `AL-`, `BR-` and `DR-` prefixes; those are dead and must not come back.

Cite the ID wherever the code implements it:

```php
// FR-02.5: the score is computed here, on the server, and a client supplied
// score is never accepted.
```

Commit messages carry it too: `feat(safety): tier scoring on the server (FR-02.5)`.

---

## Five rules that are not negotiable

These come straight from the requirements and each one appears in several places at once.
Breaking any of them is a defect, not a design preference. All five are asserted in
`backend/tests/Feature/NonNegotiableRulesTest.php`.

1. **All scoring happens on the server.** A score computed or supplied by a client is never
   accepted, for safety tiers or knowledge quizzes (`FR-02.5`, `SR-07`). A client that could
   score itself could forge a safety certificate.

2. **Every experiment stays completable when the AI service is down.** Pre authored guidance
   is stored for every step and is used whenever Gemini does not answer in time (`NFR-06`).
   Test this by removing the API key and running the whole flow.

3. **No account exists without administrator action.** There is no registration endpoint, no
   sign up screen, no invite-yourself flow (`FR-01.6`).

4. **Points and rank are never reduced, and never reward speed** (`FR-06.2`, `FR-06.4`).
   Experience points are awarded once per experiment, on the first pass at 80 percent or
   above (`FR-04.3`).

5. **Certificates and audit entries are insert only.** No update path, no delete path, no
   `updated_at` column (`FR-02.8`, `FR-10.8`). Audit entries are kept 7 years.
   `App\Models\Concerns\InsertOnly` raises rather than writing, so a slip fails a test
   instead of losing history.

Two gates are equally absolute: no experiment opens before Safety Tier 1 is passed
(`FR-02.3`), and a teacher can act only on students of a class they are formally assigned to,
enforced on the server (`FR-09.1`, `SR-04`).

---

## Stack, and why

Laravel 11 on PHP 8.2 for the API and the Filament administrator panel. MySQL 8 for data,
Redis 7 for cache, queues and the leaderboard. Next.js 14 with React 18 for the web portal,
delivered as a PWA. AWS S3 behind Cloudflare for assets.

External services: Gemini (guidance and readiness classification), ElevenLabs (avatar
voice), Pusher (live class), Firebase Cloud Messaging (push), SendGrid (email).

**Flutter is Phase 2, after the web portal is in schools.** Chapter 4 names Flutter 3 with
ARCore and ARKit, and that stays the plan — the order changed, not the target. Three
platforms at once is not deliverable by two people, and a PWA reaches a school's phones,
desktops and shared computers with no app store, no review cycle and no device-specific
build. AR runs through WebXR in the browser in the meantime, with the same three fallbacks
to simulated presentation that `FR-05.4` to `FR-05.6` already require. When the report
mentions the mobile application, say so as roadmap rather than as something built.

```
backend/                      Laravel 11 API and Filament admin panel
Beaker Breakers Studio (2)/   the UI prototype (vanilla HTML/CSS/JS)
web/                          Next.js 14 portal (M2)
mobile/                       Flutter app (Phase 2)
docs/                         the specification, in markdown
FYP/                          diagrams and wireframes
Documentation/                the submitted Word chapters
```

### Design tokens from the prototype

The prototype's look is settled, so the web portal inherits it rather than inventing a
second one. From `Beaker Breakers Studio (2)/styles.css`:

- Fonts: **Fredoka** for headings and brand, **Nunito** 400 to 900 for everything else
- `--blue #4FC3F7` / `--blue-d #29B6F6`, `--green #4CAF82` / `--green-d #2E7D57`,
  `--orange #FF8A50` / `--orange-d #FF6D00`, `--purple #9575CD`, `--yellow #FFD54F`
- Pastel surfaces: `--sky #E8F4FD`, `--mint #C8F7E4`, `--lemon #FFF9C4`, `--coral #FFD6C8`
- Text `#2D3748`, subtext `#718096`; cards 18px radius with
  `0 8px 32px rgba(0,0,0,.10)`; `cubic-bezier(.68,-0.55,.27,1.55)` for the bouncy
  transitions; 260px sidebar on desktop, topbar and bottom nav on mobile
- Beaky the mascot, the toast, the confetti burst and the floating XP are part of the
  product, not decoration someone added. Carry them across.

---

## Deliberate deviations from Chapter 4

Record any further ones here, with the reason. These three are already decided:

1. **Primary keys are `id`, not `<entity>_id`.** The data dictionary lists `user_id PK`
   and so on. Laravel and Filament both assume `id`, and fighting that convention costs
   more than it buys. Foreign keys keep the documented names: `school_id`, `class_id`,
   `prerequisite_experiment_id`.
2. **A twentieth table, `quiz_attempts`.** `FR-04.4` requires every quiz attempt to be
   recorded with its score, answers and time, and the nineteen entities in the data
   dictionary have nowhere to put it. Chapter 4's data dictionary needs this row added.
3. **Flutter after the web portal**, as above.

---

## Working agreements

- **Follow `docs/build-order.md`** and finish a milestone before opening the next. Each one
  is defined to be shippable and testable on its own.
- **Write the test with the code** where the requirement demands coverage. `NFR-10` requires
  100 percent coverage on authentication, score computation, reward award and safety
  certification logic, and 70 percent overall.
- **Never put a secret in the repository.** All keys go in `.env`, and only `.env.example` is
  committed. No provider credential may reach a client device (`SR-06`, `SR-07`).
- **Build each screen against its wireframe and the prototype** rather than inventing a
  layout.
- **Ask before changing a requirement.** If something in `docs/` looks wrong or impossible,
  say so and stop; do not quietly implement a different behaviour. The requirements are
  signed off and the report depends on them.
- **Measure before claiming.** No statement about performance, accuracy or test results
  belongs in any document until it has been run. The seeded demo data is not evidence.

---

## Survey findings worth remembering

Requirements were elicited partly from a structured survey (13 responses, September 2026).
Two findings ran against the team's own assumptions and were kept anyway:

- **Augmented reality ranked fifth** among the features respondents wanted. That is why AR
  extends the experiment rather than being its primary mode, and why `FR-05.1` provides it
  for no fewer than four experiments rather than all of them.
- **Gamification ranked last.** That is why rewards are tuned to thorough and safe practice
  rather than speed, and why the leaderboard shows only name, avatar, rank and points.

Sample size is small and self selected. Do not quote proportions from it as general facts.
