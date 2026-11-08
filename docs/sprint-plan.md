# Sprint Plan: build, deploy, run in a school, defend

| Date | Milestone |
|---|---|
| Sun 8 Nov 2026 | Build starts |
| Sat 28 Nov | Feature complete, all tests passing locally |
| Mon 30 Nov | Live on production |
| Wed 2 Dec | Pilot school starts using it |
| **Sat 5 Dec** | **Code freeze.** No new features after this date, only critical fixes |
| 6 to 12 Dec | Pilot runs; measure and collect feedback |
| Sun 13 Dec | Results chapter and slides use the pilot's measured numbers |
| **Mon 14 Dec** | **Defence** |

- **BE** = Syed Mohsin Abbas (Laravel, database, external services, server)
- **FE** = Rabail Mehboob (Next.js web, content, school liaison, documentation)
- **Both** = pair on it or split as convenient

## Why the pilot shapes the plan

A real school means real students, most of them under 18. So three things come before any
feature work:

1. **Permission.** A letter from the department through Dr. Waqas Ahmed, the principal's
   written approval, and consent from parents or the school on their behalf. Without this
   the pilot cannot go in the defence.
2. **Their data is protected.** PII encrypted at rest (`SR-06`), HTTPS (`SR-02`), daily
   backups (`SR-10`), no other student's results visible (`SR-08`).
3. **It must not break in front of students.** Hence feature complete on 28 Nov, a week of
   real use before the freeze, and only fixes after 5 Dec.

## What the defence can honestly claim

Only what the pilot measures (see `CLAUDE.md`: no claim without measurement). Plan to
record:

- Number of students and teachers who used it, and over how many days
- How many passed Tier 1, earned a certificate, completed an experiment
- `NFR-07`: how many first time students got through login, Tier 1 and a first
  experiment with no help (watch a group of them on day 1 and note it)
- How often Gemini failed and the fallback guidance was used (`NFR-06`)
- Server response times from logs (`NFR-01`)
- A short feedback form for students and the teacher

## Rules that do not bend

All scoring on the server (`FR-02.5`). Every experiment completable with no Gemini key
(`NFR-06`). No registration route (`FR-01.6`). Points never reduced, never for speed
(`FR-06.2`, `FR-06.4`). Certificates and audit entries insert only (`FR-02.8`, `FR-10.8`).
No experiment before Tier 1 (`FR-02.3`). Teacher acts only on assigned classes (`FR-09.1`).

---

## Sprint 1 · 8 to 14 Nov · Foundation, login, safety

| Owner | Task |
|---|---|
| BE | Laravel 12 (PHP 8.2), MySQL 8, Redis 7 |
| BE | Migrations for all 19 entities; no `updated_at` on certificates and audit; PII columns encrypted (`SR-06`) |
| BE | Seeders: one school, one class, admin, teacher, five students |
| BE | Login with roles, 24 hour tokens, lockout 5 in 15 min, bcrypt 12, session expiry (`FR-01.1` to `FR-01.4`, `SR-01`, `SR-05`) |
| BE | Role middleware on every route; no registration route (`SR-03`, `SR-04`, `FR-01.6`) |
| BE | Safety: four tiers, 80 percent unlock, random questions, server scoring, retries, certificate (`FR-02.1` to `FR-02.9`) |
| BE | Tests at 100 percent on auth and certification (`NFR-10`) |
| FE | Next.js 14 project with the prototype's CSS, logo and avatars |
| FE | Login, forgot and reset password; three role dashboards S01, S10, S16 |
| FE | Safety S06 and certificate on profile S08 |
| FE | Content: four tier question banks (20 or more per tier) |
| FE | **School:** pick the school, get the supervisor's letter, meet the principal and chemistry teacher |

**Done when:** each role logs in, and a student earns a certificate only after four passes.

## Sprint 2 · 15 to 21 Nov · Experiments, quiz, AI, rewards

| Owner | Task |
|---|---|
| BE | Library with filters, prerequisites, ordered steps, save and resume, repeat (`FR-03.1` to `FR-03.6`, `FR-03.8`, `NFR-05`) |
| BE | Tier 1 gate on every experiment route (`FR-02.3`) |
| BE | Quiz after last step, instant feedback, attempts recorded, shuffled retries (`FR-04.1`, `FR-04.2`, `FR-04.4`, `FR-04.5`) |
| BE | Gemini behind an interface, Redis cache, pre authored fallback per step (`FR-03.7`, `NFR-06`) |
| BE | XP once on first pass at 80 percent, idempotent; eight ranks; badges once each; Redis leaderboard (`FR-04.3`, `FR-06.1` to `FR-06.4`, `FR-07.1`, `FR-07.2`) |
| BE | Tests at 100 percent on scoring and rewards |
| FE | Experiments S02, detail S03, runner S04, quiz S05, guidance panel |
| FE | Leaderboard S07, profile XP and badges |
| FE | Content: experiments 1 to 15 with steps, equations, fallback guidance and quizzes |
| FE | **School:** consent forms out; get the class list (names, class, section) for bulk import |

**Done when:** a refresh mid experiment loses nothing, and the whole flow passes with the Gemini key removed.

## Sprint 3 · 22 to 28 Nov · Teacher, readiness, admin

| Owner | Task |
|---|---|
| BE | Teacher class scope policy; class view; Pusher live updates (`FR-09.1` to `FR-09.3`, `SR-04`) |
| BE | Task assignment with auto states; notifications and broadcasts (`FR-09.4` to `FR-09.7`) |
| BE | Readiness: six dimensions, Gemini classification, 3 experiment gate, own view only (`FR-08.1` to `FR-08.6`, `SR-08`) |
| BE | Filament: accounts with 30 day soft delete, classes, enable or disable experiments, bulk import all or nothing (`FR-01.5`, `FR-10.1` to `FR-10.4`) |
| BE | Insert only audit log; certificate register; CSV and PDF export; school analytics (`FR-10.5` to `FR-10.8`) |
| FE | Teacher S10 to S15; readiness S12 for teacher and student; simulated AR S09 from the prototype (`FR-05.4`, `FR-05.5`) |
| FE | Content: experiments 16 to 30 (`FR-03.1`), all 60 badges (`FR-06.3`) |
| Both | Full click through against every wireframe; fix what is off |
| FE | **School:** a 20 minute training guide for the teacher, one page help sheet for students |

**Done when:** a full demo runs locally from login to certificate to teacher readiness report, and a teacher is refused an unassigned class.

## Sprint 4 · 29 Nov to 5 Dec · Deploy and launch in the school

| Day | Owner | Task |
|---|---|---|
| Sun 29 Nov | BE | VPS with Docker Compose, domain, HTTPS (TLS 1.3), production `.env` on the server only |
| Sun 29 Nov | FE | Web on Vercel pointing at the production API |
| Sun 29 Nov | BE | **`SR-06`: turn on encryption at rest** for the MySQL data, before any student is imported. InnoDB data-at-rest encryption with a keyring (`ALTER TABLE users ENCRYPTION='Y'`, and the same for every table holding student data), or an encrypted disk volume if the host provides one. Backups encrypted too |
| Mon 30 Nov | BE | Daily backups with 30 day retention (`SR-10`); error logging and uptime alert |
| Mon 30 Nov | Both | Full run on production with test accounts; fix what breaks |
| Tue 1 Dec | Both | Bulk import the real class; create the teacher account; train the teacher |
| Wed 2 Dec | Both | **Day 1 in the school.** Be in the room; observe first time use (`NFR-07`) |
| 3 to 5 Dec | Both | Fix bugs found in the school, nothing else |
| **Sat 5 Dec** | Both | **Code freeze.** Tag the release |

## Pilot week · 6 to 12 Dec

- No new features. Critical fixes only, each tested before it goes live.
- Check the server and backups every day.
- Collect the numbers listed above; send the feedback form on 10 Dec.

## 13 Dec · Defence preparation

- Chapter 5 (testing) and Chapter 6 (results) with the measured pilot numbers only.
- Slides: pilot photos (with the school's permission), live demo, the AI fallback shown live.
- A backup recording of the demo in case the internet fails in the hall.

---

## Only if Sprint 3 finishes early

| Item | Requirement |
|---|---|
| Flutter Android app (APK, no store listing) | Architecture |
| Native AR with ARCore for 4 experiments, surface detection, camera released on exit | `FR-05.1` to `FR-05.3`, `FR-05.6` |

If these are not done by 5 Dec they are not started. The web simulated AR covers `FR-05.4`
and `FR-05.5` for the pilot. State this openly in the defence as future work.

## Also left for after the defence

Streaks and daily missions (`FR-06.5`, `FR-07.3`, optional), iOS and ARKit, the 1000 user
load test (`NFR-04`), full WCAG and browser matrix audit (`NFR-08`, `NFR-09`), surface
detection on 7 devices (`NFR-12`).
