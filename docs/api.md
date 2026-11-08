# API contract, milestone M1

Base URL `http://localhost:8000/api`. Every response is JSON.

Authentication is a bearer token from `POST /login`:

```
Authorization: Bearer 3|pQ7f...
```

A token is valid for 24 hours (`FR-01.2`), and a fresh login invalidates the
tokens that came before it (`SR-05`). A request to any route below `/login`
without a valid token answers `401` (`SR-03`); one from the wrong role answers
`403` (`SR-04`).

---

## Accounts

### `POST /login`
```json
{ "email": "rabail@beakerbreakers.test", "password": "password", "device_name": "web" }
```
Answers the token, its expiry, the user, and `home`, which is the route the
client should go to for that role (`FR-01.1`). The client follows `home` rather
than deciding from the role string itself.

Five failures from one address inside fifteen minutes answers `429` with
`retry_after` in seconds (`FR-01.4`). Wrong email and wrong password give the
same `422` message, so the endpoint cannot be used to discover which addresses
exist.

**There is no `POST /register`.** An account exists because an administrator
made one, and for no other reason (`FR-01.6`).

### `GET /me`
The current user, with points, rank, streak and the highest safety tier passed.

### `POST /logout`
Deletes the token used to make the call.

### `POST /forgot-password`, `POST /reset-password`
Single-use token, expires 60 minutes after issue (`FR-01.3`). The reset answers
the same message whether or not the address is known. A successful reset deletes
every token of that user (`SR-05`).

---

## Safety certification — student

### `GET /safety/tiers`
The four tiers (`FR-02.1`), each with `available`, `passed` and `best_score`,
plus the certificate if one has been issued. Tier 1 is always available; a later
tier opens only when the one before it is passed at 80 or above (`FR-02.2`).

### `POST /safety/tiers/{tier}/start`
Draws a fresh random set of 10 questions from that tier's bank (`FR-02.4`).
Each question carries `id`, `prompt` and `options`, the options in a shuffled
order. **The answer key is not in the response**, and `403 tier_locked` comes back
for a tier that is not open. The response also carries `_safety_session`, which
identifies this drawn set and its shuffle; the submit must send it back.

### `POST /safety/tiers/{tier}/submit`
```json
{ "answers": { "17": 0, "23": 2, "41": 1 }, "_safety_session": "9b1d…" }
```
Question id to the position of the chosen option, as it was shown. The score is
computed on the server from the stored key (`FR-02.5`), **over every question
drawn for that attempt**: an unanswered question counts as wrong, and an answer
to a question that was not drawn is ignored. A `score` or `passed` field in the
request body is ignored; `NonNegotiableRulesTest` asserts exactly that, and
`ScoringIntegrityTest` covers partial and replayed submissions.

`_safety_session` is required and works once, for the student and tier it was
drawn for. A missing one answers `422` with a validation error; an expired,
reused or foreign one answers `422 attempt_expired`, and the client starts the
tier again.

Answers with:

| Field | Meaning |
|---|---|
| `score` | 0 to 100, computed here |
| `passed` | `score >= 80` |
| `points_awarded` | 100 on a first pass of that tier, 0 otherwise (`FR-06.1`) |
| `next_tier_available` | whether the next tier just opened |
| `certificate` | present the moment all four tiers are passed (`FR-02.7`) |
| `may_reattempt` | a failed tier may be retaken without limit (`FR-02.6`) |

### `GET /safety/certificate`
The student's own certificate, or `404`. Once issued it is never edited or
revoked (`FR-02.8`).

---

## Experiments — student

### `GET /experiments`
Optional `topic`, `difficulty` (`easy|medium|hard`) and `ar_only` (`FR-03.2`).
Each entry carries `locked`, `lock_reason` and `lock_code`, because a locked
experiment has to say why (`FR-03.3`). The codes are
`safety_tier_1_required`, `safety_tier_required`, `prerequisite_incomplete`
and `unpublished`.

**Licensing.** The list holds only experiments the student is licensed for: their
class's board-exam pair (Class 9 and 10, or 11 and 12), limited to the classes their
school is licensed for, and only of the school's board or marked for both boards. A
student with no class, or no school, gets an empty list. On any single-experiment
route, an experiment outside that licence answers `404` from `GET /experiments/{id}`,
and `403` with code `not_licensed` (or `no_class`) from `start`.

### `GET /experiments/{id}`
The experiment with its chemical equation and explanatory note (`FR-03.6`), its
steps in declared order (`FR-03.4`), and `resume` when an attempt was left
unfinished (`FR-03.5`).

### `POST /experiments/{id}/start`
```json
{ "presentation_mode": "standard" }
```
`standard` or `ar`. An unfinished session is returned rather than a second one
being opened. `403` with the lock reason when the gates are not met;
`422 ar_unavailable` for AR on an experiment that has none (`FR-05.1`).

### `POST /sessions/{id}/steps`
```json
{ "step_id": 12 }
```
Records the completion at the time it happens (`FR-03.5`). A step out of
sequence answers `422 out_of_order` (`FR-03.4`). The response carries
`steps_completed`, `step_count` and `quiz_unlocked`.

### `POST /steps/{id}/guidance`
```json
{ "question": "Why does it foam?" }
```
Omit `question` for the step opener, which is cached for a day. The response is
always `{ "text": "...", "source": "ai" | "preauthored" }`. `source` says where
the text came from; `preauthored` means the AI service was unavailable and the
stored line was used instead (`NFR-06`). **There is no error state here**: the
client never has to handle an absent answer.

---

## Quizzes — student

### `GET /experiments/{id}/quiz`
Questions in a fresh random order (`FR-04.5`), without the answer key.
`403 steps_incomplete` until every step of the experiment is done (`FR-04.1`).

### `POST /experiments/{id}/quiz`
```json
{ "answers": { "5": 0, "6": 1 }, "session_id": 31, "_quiz_session": "4c2e…" }
```
`_quiz_session` comes from `GET /experiments/{id}/quiz` and follows the same
rules as `_safety_session`: required, single use, `422 attempt_expired` otherwise.
Marked on the server over every question of the quiz; an unanswered one counts as
wrong. `feedback` carries one entry per question with `correct`,
and, only where the answer was wrong, `explanation` and `correct_option`
(`FR-04.2`). `points_awarded` holds the experiment's points on the first pass at
80 or above, and 0 on every pass after that (`FR-04.3`). Every attempt is
recorded with its score, answers and time (`FR-04.4`).

---

## Leaderboard and profile — student

### `GET /leaderboard`
The student's own class. Each row carries `position`, `display_name`,
`avatar_key`, `rank_level`, `rank_name`, `total_points` and `is_me`, and nothing
else: no email, no scores, no safety history (`FR-07.1`, `SR-08`).

### `GET /profile`
Points, rank, streak, experiments completed, highest safety tier, certificate,
`class` (the class title, or `null`) and `badges`. Each badge is
`{code, name, icon}`; the server names it (`safety_tier_1` is "Safety Tier 1",
`experiment_{slug}` is the experiment's title, `streak_7_days` is "7-Day Streak")
so every client shows the same words (`FR-06.3`).

### `PATCH /profile`
`display_name` and `avatar_key` only. `avatar_key` must be one of
`avatar-girl1`, `avatar-girl2`, `avatar-boy1`, `avatar-boy2` (`User::AVATARS`);
anything else is a 422.

`display_name` is the name classmates see on the leaderboard (`FR-07.1`). Spaces
are tidied first; then it must be 2 to 40 characters of letters in any script
(Urdu included), spaces, `.`, `'` or `-`, starting with a letter. Digits,
symbols and links are a 422 with "Use letters only, with spaces between words."
A real change writes a `profile.name_changed` audit entry with the old and new
name (`FR-10.8`). The response returns the stored `display_name` and `avatar_key`. Points, rank, class and role are not
writable from the client (`FR-06.4`, `SR-04`).

---

## Error shape

```json
{ "message": "Pass Safety Tier 1, Basic Lab Rules, before opening an experiment.",
  "code": "safety_tier_1_required" }
```

`422` from validation follows Laravel's standard shape, with `errors` keyed by
field. `code` is present wherever the client has to branch on the reason rather
than show the message.

| Status | When |
|---|---|
| 401 | no token, or an expired one (`SR-03`) |
| 403 | wrong role (`SR-04`), or a gate not met (`FR-02.3`, `FR-03.3`, `FR-04.1`) |
| 422 | validation, or an action out of sequence |
| 429 | login lockout (`FR-01.4`) |

---

## Teacher (M3)

Every route below needs the `teacher` role (`SR-04`) and checks the class against
`class_teachers` on the server (`FR-09.1`). A class the teacher is not assigned to
answers **403** `{"code": "not_assigned"}`, and the refusal is written to the
audit log as `teacher.refused` (UC-06 alternate flow).

### `GET /teacher/dashboard`
wf_S10. `stats` (`students`, `avg_xp`, `certified`, `ready_for_lab`) across every
assigned class, the `classes`, and up to ten students in `attention`, each with
`reasons`: not started yet, inactive for 7 days or more, not safety certified,
or a last quiz under 80.

### `GET /teacher/classes`
The assigned classes, each with `student_count`, `avg_xp` and `certified_count`.

### `GET /teacher/classes/{class}/students`
wf_S11, `FR-09.2`. Per student: `total_points`, `rank_level`, `rank_name`,
`experiments_completed`, `safety_tier_passed`, `has_certificate`, the latest
`readiness` classification or `null`, `last_quiz_score` and `last_active_on`.
`FR-09.3` asks for updates within 5 seconds; the web app polls every 5 seconds
until the live channel (Pusher) is added.

### `GET /teacher/classes/{class}/safety`
wf_S14, UC-26, `FR-02.9`. Per student the best score and pass for each tier
(`tier1_passed`, `tier1_score` … `tier4_*`), and the `certificate` reference and
`certificate_issued_on`. `summary` is `{certified, total}`.

### `GET /teacher/classes/{class}/experiments`
The experiments a task may use for this class: published, in the class's
board-exam pair as far as the school is licensed, and in its board or `both`.

### `POST /teacher/tasks`
`FR-09.4`. Body `{class_id, experiment_id, due_at, target, student_ids?, instructions?}`.
`target` is `class` or `students`; with `students`, `student_ids` must all belong
to the class or the whole request is refused with 403 and nothing is created.
`due_at` must be in the future (422). `instructions` lose any markup, 500 at most.
Creates one assignment per student in state `assigned` and one `task`
notification each (`FR-09.6`; the bell polls every 30 seconds). The due time in
the notification is written in `DISPLAY_TIMEZONE` (default `Asia/Karachi`).
Answers **201** with the task.

### `GET /teacher/tasks` and `GET /teacher/tasks/{task}`
The teacher's own tasks with a count per state; one task with each student's
`state` and `state_changed_at` (UC-06.1).

**States move by themselves (`FR-09.5`).** Starting the task's experiment moves
`assigned` or `overdue` to `in_progress`; finishing every step of it moves the
task to `completed`; `assignments:mark-overdue` (every 15 minutes) moves
`assigned` and `in_progress` to `overdue` once the due date has passed.

### `POST /teacher/broadcast` and `GET /teacher/notifications`
`FR-09.7`. Type is `task`, `reminder`, `safety_alert` or `announcement`. Markup is
removed **before** the 500 character limit is checked, so the limit applies to
the text students read. Each broadcast is recorded in the audit log as
`broadcast.sent`, and `GET /teacher/notifications` reads the teacher's sent
history back from there.

---

## Readiness reports (M4)

`FR-08.1` to `FR-08.6`. The six dimensions are computed in `ReadinessService`
from the student's own history; only the classification is asked of Gemini, with
a 4 second timeout. When Gemini has no key, times out, or answers with anything
but a valid classification, the rule decides and `ai_generated` is `false`
(`NFR-06`). Whatever Gemini says, a student under 90 on safety compliance is
never `ready`.

A report: `{id, generated_at, ai_generated, classification, narrative,
dimensions{six values 0 to 100}, estimated[], student{id, display_name, avatar_key}}`.
`classification` is `ready` (Ready for Real Lab), `developing` (Approaching
Readiness) or `not_ready` (Not Ready), the names `FR-08.3` gives.

**Interim formulas** (decided 8 Nov 2026, `ReadinessService::FORMULA_VERSION`
`interim-1`). Two dimensions need the per-step record of the experiment engine,
which is not built, so they are estimated and listed in `estimated`:

| Dimension | Formula, 0 to 100 |
|---|---|
| Procedural accuracy | experiments finished ÷ experiments begun |
| Safety compliance | average of the first attempt's score on each tier attempted |
| Reagent management (estimated) | finished experiments completed in their first session, not abandoned and restarted |
| Time efficiency | per finished experiment, estimated minutes ÷ actual minutes, capped at 100, averaged. Never affects points (`FR-06.2`) |
| Conceptual understanding | average score of the first quiz attempt on each experiment |
| Error recovery (estimated) | quizzes failed the first time that were passed later; 100 when none failed |

Rule: average of the six at least 80 **and** safety at least 90 is `ready`;
average at least 60 is `developing`; otherwise `not_ready`.

### `GET /readiness` (student)
`FR-08.6`, `SR-08`: the caller's own latest report. It takes no student or report
id, so no other student's values are reachable. Under 3 completed experiments it
answers **422** `not_eligible` with `completed` and `required`; with 3 or more and
no report yet, **404** `no_report`.

### `GET /teacher/classes/{class}/readiness` (teacher)
wf_S12. Each student of the class with `completed` (distinct experiments
finished), the latest `report` or `null`, and `required` (3).

### `POST /teacher/students/{student}/readiness` (teacher)
`FR-08.2`. Generates and stores a new report (`FR-08.5`: requester, student, six
values, classification, time) and answers **201**. A student outside the
teacher's classes is **403** `not_assigned`, logged as `teacher.refused`; under 3
completed experiments is **422** `not_eligible` and nothing is stored
(`FR-08.4`). Each report is also written to the audit log as
`readiness.generated`. Measured locally on `php artisan serve` with no Gemini
key: about 0.7 seconds over HTTP; not yet measured with Gemini or on the server.

---

## Not built yet

The live class channel for `FR-09.3`,
administrator reporting and export (`FR-10.3` to `FR-10.7`), and push delivery
through FCM (`FR-09.6`; the tokens are stored, nothing sends to them yet).
