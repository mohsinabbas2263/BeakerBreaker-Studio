# Build Order

Each milestone is shippable and testable on its own. Do not start one before the
requirements of the previous one pass their tests.

## M1. Foundation
Laravel 11 project, MySQL schema and migrations for all nineteen entities, Redis wired,
Docker compose for local work. Seed data for one school, one class, a few users.
**Done when:** migrations run clean and seeds load.

## M2. Authentication and accounts
`FR-01.1` to `FR-01.6`, `SR-01` to `SR-05`. Role based login, signed tokens, password
reset by email, lockout, administrator account management. No registration route exists.
**Done when:** the three role dashboards load, lockout fires after 5 attempts in 15
minutes, and 100 percent test coverage on auth (`NFR-10`).

## M3. Safety certification
`FR-02.1` to `FR-02.9`. Four tiers, random selection from the tier bank, server side
scoring, unlimited reattempts, certificate issue, certificate register.
**Done when:** a student cannot open any experiment before Tier 1 passes, and a certificate
is issued only after four tiers at 80 percent or above.

## M4. Experiment engine
`FR-03.1` to `FR-03.8`, `FR-04.1` to `FR-04.5`, `NFR-05`. Library with filters,
prerequisite gate, ordered steps, step persistence and resume, knowledge quiz with answer
explanations.
**Done when:** a refresh mid experiment loses nothing, and the quiz unlocks only after the
last step.

## M5. AI guidance
`FR-03.7`, `NFR-06`. Gemini integration behind an interface, Redis cache, and pre authored
guidance for every step as the fallback.
**Done when:** the whole flow still completes with the Gemini key removed.

## M6. Gamification
`FR-06.1` to `FR-06.5`, `FR-07.1` to `FR-07.3`, `FR-04.3`. Event driven rewards, badges,
eight rank levels, leaderboard in Redis, daily missions, streaks.
**Done when:** the award is idempotent under a replayed completion event, and no operation
can reduce points or rank.

## M7. Teacher features
`FR-09.1` to `FR-09.7`, `SR-04`. Class monitoring over Pusher, task assignment with
automatic state changes, broadcasts, class reports.
**Done when:** a teacher request for an unassigned class is rejected server side.

## M8. Readiness reporting
`FR-08.1` to `FR-08.6`. Six dimension computation in our code, classification through
Gemini, eligibility gate at three experiments, student facing own view only.
**Done when:** a student cannot see another student's values by any route (`SR-08`).

## M9. Administration
`FR-10.1` to `FR-10.8`. Filament panel, class management, bulk import, library
configuration, analytics, export, immutable audit log.
**Done when:** a bulk file with one bad row creates zero accounts and reports that row.

## M10. Mobile and AR
`FR-05.1` to `FR-05.6`, `NFR-11`, `NFR-12`. Flutter app, then AR mode with the three
fallbacks to simulated presentation.
**Done when:** an unsupported device, a refused camera permission and an undetected surface
all land on the same simulated presentation with identical controls.

## M11. Hardening
`NFR-01` to `NFR-04`, `NFR-08`, `NFR-09`, `SR-10`. Load testing to 1000 concurrent users,
WCAG 2.1 AA audit, browser matrix, backups at 24 hour intervals with 30 day retention.

## Wireframes
Screen layouts are in `FYP/Wire framing & error Handling/`, numbered `wf_S01` to `wf_S18`,
plus the navigation map and the error handling states. Build each screen against its
wireframe rather than inventing a layout.
