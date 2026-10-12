# Beaker Breakers Studio

AI powered gamified virtual chemistry laboratory portal with augmented reality, for
secondary schools. Final Year Project, BSCS, The University of Chenab, Gujrat.

Team: Rabail Mehboob (frontend, documentation), Syed Mohsin Abbas (backend).
Supervisor: Dr. Waqas Ahmed.

**Status: design complete, implementation not started.** Chapters 1 to 4 are written and
signed off. There is no application code in this repository yet.

---

## Read these before writing code

| File | What it holds |
|---|---|
| `docs/requirements.md` | All 85 requirements with their IDs. The source of truth. |
| `docs/use-cases.md` | 28 use cases with flows and preconditions. |
| `docs/architecture.md` | Tiers, components, stack, external services, performance targets. |
| `docs/data-model.md` | 19 entities, attributes, schema rules, state machines. |
| `docs/build-order.md` | Eleven milestones in dependency order. Start at M1. |

Diagrams are in `FYP/` and `Documentation/UML Diagrams/`. Screen layouts are in
`FYP/Wire framing & error Handling/` as `wf_S01` to `wf_S18`.

The Word chapters in `Documentation/` and the repository root are the submitted versions.
Treat the markdown in `docs/` as the working copy; if the two disagree, the Word chapters
win and the markdown needs fixing.

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
Breaking any of them is a defect, not a design preference.

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

Two gates are equally absolute: no experiment opens before Safety Tier 1 is passed
(`FR-02.3`), and a teacher can act only on students of a class they are formally assigned to,
enforced on the server (`FR-09.1`, `SR-04`).

---

## Stack

Laravel 11 on PHP 8.2 for the API and the Filament admin panel. React 18 with Next.js 14 for
web. Flutter 3 for Android and iOS, with ARCore and ARKit for AR mode. MySQL 8 for data,
Redis 7 for sessions, queues and the leaderboard. AWS S3 behind Cloudflare for assets.

External services: Gemini (guidance and readiness classification), ElevenLabs (avatar
voice), Pusher (live class), Firebase Cloud Messaging (push), SendGrid (email).

Intended layout once scaffolding starts:

```
backend/     Laravel 11 API and admin panel
web/         Next.js 14 portal
mobile/      Flutter app
docs/        the specification, in markdown
FYP/         diagrams and wireframes
```

---

## Working agreements

- **Start at M1 in `docs/build-order.md`** and finish a milestone before opening the next.
  Each one is defined to be shippable and testable on its own.
- **Write the test with the code** where the requirement demands coverage. `NFR-10` requires
  100 percent coverage on authentication, score computation, reward award and safety
  certification logic, and 70 percent overall.
- **Never put a secret in the repository.** All keys go in `.env`, and only `.env.example` is
  committed. No provider credential may reach a client device (`SR-06`, `SR-07`).
- **Build each screen against its wireframe** rather than inventing a layout.
- **Ask before changing a requirement.** If something in `docs/` looks wrong or impossible,
  say so and stop; do not quietly implement a different behaviour. The requirements are
  signed off and the report depends on them.
- The project has **not** been implemented yet, so no claim about performance, accuracy or
  test results belongs in any document until it has been measured.

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
