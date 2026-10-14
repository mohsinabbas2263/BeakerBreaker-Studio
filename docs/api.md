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
Each question carries `id`, `prompt` and `options`. **The answer key is not in
the response**, and `403 tier_locked` comes back for a tier that is not open.

### `POST /safety/tiers/{tier}/submit`
```json
{ "answers": { "17": 0, "23": 2, "41": 1 } }
```
Question id to the index of the chosen option. The score is computed on the
server from the stored key (`FR-02.5`). A `score` or `passed` field in the
request body is ignored; `NonNegotiableRulesTest` asserts exactly that.

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
{ "answers": { "5": 0, "6": 1 }, "session_id": 31 }
```
Marked on the server. `feedback` carries one entry per question with `correct`,
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
Points, rank, streak, experiments completed, highest safety tier, certificate
and badge codes.

### `PATCH /profile`
`display_name` and `avatar_key` only. Points, rank, class and role are not
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

## Not in M1

Teacher routes (`FR-09.*`), readiness reporting (`FR-08.*`), administrator
reporting and export (`FR-10.3` to `FR-10.7`), notifications and FCM
registration (`FR-09.6`, `FR-09.7`). The tables exist; the endpoints do not.
