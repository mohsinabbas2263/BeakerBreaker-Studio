# Build Order

Each milestone is shippable and testable on its own. Do not start one before the
previous one passes its tests.

This order was rewritten in October 2026, when development started. The earlier
version assumed a greenfield project and put Flutter alongside the web portal.
Two things changed: a UI prototype already exists for the student screens, and
the mobile application moved to Phase 2 (see `CLAUDE.md`, Stack).

---

## M1. Backend foundation — written, not yet verified

`FR-01.*`, `FR-02.*`, `FR-03.*`, `FR-04.*`, `FR-06.*`, `FR-07.1`, `SR-01` to `SR-08`,
`NFR-06`.

Laravel 11, MySQL schema for all twenty tables, Sanctum tokens, role based login with
lockout, the four safety tiers with server side scoring and certificate issue, the
experiment runner with ordered steps and resume, knowledge quizzes with explanations,
points, badges, ranks, streaks, the class leaderboard, AI guidance with its pre authored
fallback, and the Filament administrator panel over accounts, schools, classes,
experiments, certificates and the audit log.

**Done when:** `composer install`, `php artisan migrate --seed` and `php artisan test`
all succeed, `NonNegotiableRulesTest` passes, the admin panel loads at `/admin`, and a
seeded student can be driven from login to a safety certificate with `curl` alone.

Until that has been done, M1 is not finished. The code has only been syntax checked.

## M2. Web portal, student side

`FR-01.1`, `FR-02.*`, `FR-03.*`, `FR-04.*`, `FR-06.*`, `FR-07.*`, `NFR-05`, `NFR-08`.

Next.js 14, TypeScript, Tailwind, installed as a PWA. Port the nine prototype screens —
home, experiments, experiment detail, step runner, quiz, safety training, leaderboard,
profile, AR placeholder — onto the real API, keeping the prototype's fonts, palette,
mascot, toast, confetti and XP float. Add what the prototype has no equivalent of: the
login screen, the locked-experiment reason, the resume path, and an offline state.

**Done when:** a student completes Tier 1 and a whole experiment against the live API
with no hardcoded data anywhere in `web/`, a mid-experiment refresh loses nothing
(`FR-03.5`), and Lighthouse passes the PWA install criteria.

## M3. Teacher features

`FR-09.1` to `FR-09.7`, `SR-04`, `SR-08`. Screens `wf_S10` to `wf_S15`.

Class monitor, task assignment with the `AssignmentState` machine, safety review, class
reports. Live class view over Pusher. The tables, the enum and its permitted transitions
are already in place from M1; the controllers, policies and screens are not.

**Done when:** a teacher request touching a class they are not assigned to is rejected on
the server (`FR-09.1`), and an assignment moves to overdue with no teacher action
(`FR-09.5`, the `assignments:mark-overdue` command).

## M4. AI readiness reporting

`FR-08.1` to `FR-08.6`.

The six dimensions computed in our own code from session, quiz and safety history;
classification through Gemini with a rule based fallback; the eligibility gate at three
completed experiments; a student sees only their own.

**Done when:** a student cannot reach another student's values by any route (`SR-08`), and
a report still generates with the Gemini key removed (`NFR-06`).

## M5. Administrator reporting

`FR-10.3` to `FR-10.7`. Screens `wf_S16` to `wf_S18`.

Bulk account import, usage and licence reports, CSV export, the certificate register. The
panel from M1 covers creation and editing; this milestone is the reporting half.

**Done when:** a bulk file with one bad row creates zero accounts and names that row.

## M6. AR mode in the browser

`FR-05.1` to `FR-05.6`, `NFR-11`, `NFR-12`.

WebXR for at least four experiments, with the three fallbacks the requirements already
demand: unsupported device, refused camera permission, undetected surface.

**Done when:** all three fallbacks land on the same simulated presentation with identical
controls, and no AR path is the only way to finish an experiment.

## M7. Notifications

`FR-09.6`, `FR-09.7`. Web push first, through the PWA; FCM device tokens are already
modelled for the Flutter app later.

## M8. Content to thirty experiments

`FR-03.1`. Six are written in `ExperimentSeeder` with steps, equations, guidance and
quizzes. The other twenty-four are writing work, against the Class 9 and 10 curriculum,
and each one needs `preauthored_guidance` on every step or it breaks `NFR-06`.

**Done when:** thirty experiments are live and every step has its pre authored line.

## M9. Hardening and deployment

`NFR-01` to `NFR-04`, `NFR-09`, `NFR-10`, `SR-02`, `SR-09`, `SR-10`.

Load testing to 1000 concurrent users, WCAG 2.1 AA audit, the browser matrix, Redis for
cache and queues, TLS, backups every 24 hours retained 30 days, and the coverage `NFR-10`
asks for. This is also where the first school pilot goes out.

## Phase 2. Flutter application

`FR-05.*` on ARCore and ARKit, `NFR-11`, `NFR-12`. After the web portal is in schools, not
before. The API is shared, so this is a client, not a second system.

---

## Wireframes and the prototype

Screen layouts are in `FYP/Wire framing & error Handling/`, numbered `wf_S01` to `wf_S18`,
plus the navigation map and the error handling states. The prototype in
`Beaker Breakers Studio (2)/` covers nine of those screens as working HTML and is the
reference for spacing, motion and tone. Build each screen against both rather than
inventing a layout.
