# Experiment engine: game-style steps

Status: **agreed and built.** Mohsin merged this proposal (PR #3) on 8 Nov 2026 without
comments on the four questions at the end, so the proposals there stand as written. Built
on 9 Nov 2026: schema, `StepJudge`, the action endpoint, the 36 textbook experiments
seeded in this format, the web runner, and readiness from `step_actions`.

Approved look and feel: the clickable demo at
https://claude.ai/artifact/2SJ8zaHA5gd82F6YcfHxRa (titration, 8 steps).

## What was decided

- Experiments play like a game. The **student's own profile avatar** guides every step with
  short English messages, reacts to right and wrong moves, and the next item glows.
- Students **drag, choose, measure and read**, instead of pressing "Done". They can make
  mistakes, see what happens, and fix them.
- An **Ask AI** chat is available on every step (`FR-03.7`), with the pre-authored note as
  the answer when Gemini is unavailable (`NFR-06`).
- Every move is recorded, because the readiness report (`FR-08.1`) is computed from it.

## Rules this design keeps

| Rule | How |
|---|---|
| The server judges, never the client (`FR-02.5`, `SR-07`) | The client sends what the student did. The server decides right or wrong from answers it keeps to itself, and records it. |
| Steps in order, saved as they happen (`FR-03.4`, `FR-03.5`, `NFR-05`) | Same as today: a step completes only through the server, and progress inside a step is saved too, so a refresh resumes mid-step. |
| Completable without AI (`NFR-06`) | Every message the avatar says is stored with the step. Gemini only answers free questions in Ask AI. |
| Nobody gets stuck | After 3 mistakes on one step the avatar shows the answer and the student does it. Still recorded as mistakes. |
| No points for speed (`FR-06.2`) | Time is recorded for readiness only. Points stay "once, on first quiz pass at 80" (`FR-04.3`). |
| Virtual only (`SR-09`) | Messages never tell students to try anything outside the virtual lab. Review checks this. |

---

## Step kinds

Nine kinds cover every textbook experiment in Classes 9 to 12, both boards (36 experiments
written, see [Content written](#content-written)). An experiment is a list of steps,
each one of these kinds. Existing experiments become `confirm` steps, so nothing breaks.

| Kind | Student does | `action` sent | Server checks | Mistake category |
|---|---|---|---|---|
| `gear` | Drags safety gear onto themself | `{item}` | item is in `required`; a `forbidden` item is a mistake | safety |
| `place` | Drags apparatus into places on the bench | `{item, zone}` | `answer[zone] == item` | procedure |
| `choose` | Drags one reagent or indicator into a target | `{item}` | `item == correct` | reagent or procedure, per wrong item |
| `measure` | Sets an amount on a slider and transfers it | `{value}` | `|value - target| <= tolerance` | reagent |
| `add_until` | Adds in steps (1 mL, 1 drop) and stops at a change | `{stop_at}` | `end <= stop_at <= end + tolerance`; below is "too early", above is overshoot | procedure (early), reagent (overshoot) |
| `heat` | Picks a flame level and how long to heat, one stage at a time | `{level, seconds}` | levels follow `sequence` in order; each stage lasts at least `min_seconds` | safety (too hot first), procedure (too short) |
| `read` | Reads an instrument or a ruler and types the value | `{value}` | within tolerance of the true reading | procedure |
| `observe` | Picks what they saw | `{option}` | `option == correct` | concept |
| `confirm` | Presses the action button (today's behaviour) | `{}` | always correct | none |

`add_until` sends the end point to the client, because the client has to show the colour change
at the right moment. Grading still happens on the server.

### Config reference

What each kind's `config` holds, as the written content uses it. **Private** keys are the
answers: the server keeps them and strips them from the public copy sent to the client.

| Kind | Public keys | Private keys | Notes |
|---|---|---|---|
| `gear` | `items` | `required`, `forbidden` | Done when every `required` item is worn |
| `place` | `items`, `zones`, `simultaneous` | `answer` (zone → item) | `simultaneous: true` means all zones fill first and are judged together (both cotton plugs in the diffusion tube at once). Default `false`: each drop is judged as it happens |
| `choose` | `items`, `target` | `correct`, `wrong` (item → category) | `wrong` maps each wrong item to `procedure`, `reagent`, `safety` or `concept`. An item missing from `wrong` counts as `procedure` |
| `measure` | `unit`, `min`, `max`, `step` | `target`, `tolerance` | `tolerance: 0` means exact. `unit` may be empty for a ratio such as Rf or Kpc |
| `add_until` | `unit`, `increments`, `end`, `visual.from`, `visual.to`, `visual.fade_from` | `tolerance` | `end` is public (see above). The client starts fading `visual.from` toward `visual.to` at `fade_from` |
| `heat` | `levels` | `sequence`, `min_seconds` | `sequence: ['low']` is gentle heat only; `['low', 'high']` means warm gently first, then strongly. Starting on a higher level than `sequence[0]` is `too_hot_first` |
| `read` | `unit`, `instrument` | `value`, `tolerance` | A fixed `value`, or `value_from: 'step:N'` to read back what the student did in step N (the burette in Appendix A). With `instrument` (`ruler`, `syringe`, `thermometer`) the public config also carries `display`, the reading to draw on the scale: reading the scale is the task. A read that is a calculation (the mean of concordant titres) has no instrument and its value stays private |
| `observe` | `options` | `correct` | Options are keyed `a` to `d` |
| `confirm` | none | none | Uses the step's `action_label` for the button |

Units in the content: `cm³`, `M`, `mg/dm³`, `°C`, `cm`, `drops`, `granules`, `spatulas`, or
empty. The books use cm³, so new content should too; the appendix's `mL` is equivalent.

### Message keys

`intro` and `success` on every step. A mistake looks up its line by key, and falls back to
`preauthored_guidance` when the key is missing:

| Kind | Mistake keys |
|---|---|
| `gear` | the forbidden item's id (`sandals`, `snack`) |
| `place`, `choose` | the wrong item's id |
| `measure` | `too_low`, `too_high` |
| `add_until` | `too_early`, `overshoot` |
| `heat` | `too_hot_first`, `too_short` |
| `read`, `observe` | `wrong` |

---

## Schema changes

### `experiment_steps`, three new columns

| Column | Type | Purpose |
|---|---|---|
| `kind` | enum of the nine kinds, default `confirm` | Which interaction the runner shows |
| `config` | JSON, nullable | Items, zones, ranges and the answers. Answers never leave the server |
| `messages` | JSON, nullable | The avatar's lines: `intro`, `success`, and one per expected mistake |

`preauthored_guidance` stays, still `NOT NULL`. It is the hint and the Ask AI fallback.
`item_emoji` and `action_label` stay for `confirm` steps.

### `experiment_sessions`, one new column

| Column | Type | Purpose |
|---|---|---|
| `step_state` | JSON, nullable | Progress inside the current step (gear worn, items placed, titration volume, reading), so a refresh resumes mid-step |

### New table `step_actions` (insert only)

One row per thing the student did. This is the evidence for the readiness report, so rows are
never edited or deleted (same `InsertOnly` guard as audit entries).

| Column | Type |
|---|---|
| `id` | PK |
| `session_id` | FK `experiment_sessions` |
| `step_id` | FK `experiment_steps` |
| `user_id` | FK `users` |
| `kind` | step kind |
| `action` | JSON, what the student did |
| `outcome` | enum `correct`, `partial`, `mistake`, `revealed` |
| `category` | enum `procedure`, `safety`, `reagent`, `concept`, nullable |
| `mistake_code` | string, nullable, e.g. `sandals`, `overshoot` |
| `hinted` | bool, the student asked for a hint or the idle hint showed |
| `created_at` | timestamp |

This is a 21st table. Chapter 4's data dictionary needs this row, recorded as a deliberate
deviation in `CLAUDE.md`.

---

## API

| Endpoint | Change |
|---|---|
| `GET /experiments/{id}` | Each step also returns `kind`, a **public** copy of `config` (answers removed) and `messages.intro` |
| `POST /sessions/{session}/steps/{step}/actions` | **New.** Body `{action, hinted}`. Answers `{outcome, category, mistake_code, message, mood, step_complete, mistakes, progress, reveal, steps_completed, step_count, quiz_unlocked}`. `reveal` is the answer, sent only after 3 mistakes on the step |
| `POST /sessions/{id}/steps` | Kept for `confirm` steps only; a game step answers 422 `use_actions`, so it cannot be skipped |
| `POST /steps/{id}/guidance` | Unchanged. Ask AI and the hint |
| `GET /experiments/{id}` `resume` | Also returns `step_state` |

`mood` is one of `point`, `happy`, `think`, `oops`, `worried`, `party`; the runner picks the
avatar animation from it. A safety mistake always answers `worried`.

---

## Readiness, from `step_actions` (`FR-08.1`)

Computed in our own code over the student's completed sessions; only the classification goes
to Gemini, with a rule-based fallback (`NFR-06`). No report under 3 completed experiments
(`FR-08.4`).

| Dimension | Formula (0 to 100) |
|---|---|
| Procedural accuracy | steps completed with no mistake ÷ steps completed |
| Safety compliance | 100 minus 25 per safety mistake, per experiment, averaged, floor 0 |
| Reagent management | `measure`, `choose` and `add_until` steps right first time ÷ all of them |
| Time efficiency | experiment's estimated minutes ÷ actual minutes, capped at 100. Never affects points |
| Conceptual understanding | average of first quiz attempt score and `observe` steps right first time |
| Error recovery | steps that had a mistake and were then completed without `revealed` ÷ steps that had a mistake; 100 when there were none |

Rule-based classification when Gemini does not answer: average of the six at least 80 and
safety at least 90 is **Ready for Real Lab**; average at least 60 is **Approaching Readiness**;
otherwise **Not Ready**.

Note: `readiness_reports.classification` is currently `ready`, `developing`, `not_ready`.
`FR-08.3` names the middle one "Approaching Readiness", so the value should be renamed.

---

## Content format

Content is written in the seeder. One step looks like this:

```php
[
    'step_order' => 4,
    'kind' => 'measure',
    'title' => 'Measure the NaOH',
    'instruction_text' => 'Measure exactly 25.0 mL of sodium hydroxide and transfer it to the flask.',
    'preauthored_guidance' => 'Use small steps on the slider. The method says 25.0 mL.',
    'config' => [
        'unit' => 'mL', 'min' => 0, 'max' => 50, 'step' => 0.5,
        'target' => 25.0, 'tolerance' => 0.5,
    ],
    'messages' => [
        'intro' => 'Time to measure. Slide to exactly 25.0 mL of NaOH, then press Transfer.',
        'success' => 'Spot on! ✅',
        'too_low' => 'Too little base changes your answer. Measure 25.0 mL.',
        'too_high' => 'Extra base means you will need more acid. Measure 25.0 mL.',
    ],
],
```

### Template for teachers

For each step, fill one row. Everything is in simple English for Classes 9 to 12.

| Field | Example (titration step 3) |
|---|---|
| Step number and title | 3. Measure the NaOH |
| Kind | measure |
| What the student must do | Measure 25.0 mL of NaOH and transfer it |
| The right answer | 25.0 mL, accept 24.5 to 25.5 |
| Wrong answers and what the avatar says | Below 24.5: "Too little base changes your answer." Above 25.5: "Extra base means you'll need more acid." |
| Is any wrong answer a safety mistake? | No |
| Avatar's opening line | "Time to measure. Slide to exactly 25.0 mL of NaOH." |
| Avatar's line when right | "Spot on!" |
| Hint if stuck | "Use small steps on the slider. The method says 25.0 mL." |

### Full example

The titration in the demo, as seed data, is in [Appendix A](#appendix-a-titration-as-seed-data).

### Content written

All 36 textbook experiments are written in this format on `main`, in
`backend/database/seeders/game/`. None is seeded until this document is agreed.

| File | Experiments |
|---|---|
| `punjab.php` | Punjab 9 and 10: 7 |
| `federal.php` | Federal 9 and 10: 6 |
| `federal-activities.php` | Federal 9 and 10: 8, rewritten from the book pages to replace the old-format ones |
| `class-11.php` | Punjab 11: 6, Federal 11: 1 (aspirin) |
| `class-12.php` | Federal 12: 5, Punjab 12: 1 (aldehyde or ketone) |

Every step has `preauthored_guidance` (`NFR-06`), and every experiment has a four question quiz.
Each file's header lists its book pages and any place the book text looked wrong.

---

## Rollout

| Order | Work | Who |
|---|---|---|
| 1 | Agree this document | Mohsin, Rabail |
| 2 | Migrations, models, `step_actions` with `InsertOnly`, the action endpoint and its checks per kind, tests at 100 percent (it is score computation, `NFR-10`) | Claude, reviewed by Mohsin |
| 3 | Game runner in `web/`: avatar from the profile, glow, drag and tap, each kind's control, Ask AI over `/steps/{id}/guidance` | Claude |
| 4 | Titration converted as the first real experiment, then 7 to 9 more for the pilot | Rabail and the school's teacher, with Claude |
| 5 | Readiness computation from `step_actions` | Mohsin and Claude |

State, 9 Nov 2026: steps 1, 2, 3 and 5 done; all 36 textbook experiments are seeded in
this format (step 4 went further than planned), and every published experiment is a game
experiment. The content follows the textbook pages; the team decided (9 Nov 2026) that
what is in the books is what is offered, with no separate teacher review.

Experiments not yet converted keep working as `confirm` steps throughout.

## Questions for Mohsin (unanswered; merged as proposed)

1. Is a new `step_actions` table fine, or would you rather extend `experiment_sessions`?
2. Should `config` live on the step as JSON, or do you prefer a separate `step_options` table?
3. Rename `developing` to `approaching` in `readiness_reports` now, before any reports exist? *Not done: the interface shows "Approaching Readiness" for the stored `developing`, which is enough.*
4. Any objection to the action endpoint replacing `POST /sessions/{id}/steps` for non-`confirm` steps?

---

## Appendix A: titration as seed data

```php
[
    'slug' => 'acid-base-neutral',
    'steps' => [
        ['step_order' => 1, 'kind' => 'gear', 'title' => 'Get ready to work safely',
         'instruction_text' => 'Put on the two things that protect you. Leave the rest on the bench.',
         'preauthored_guidance' => 'Look for what protects your eyes and your clothes.',
         'config' => ['items' => ['goggles', 'sandals', 'coat', 'snack'], 'required' => ['goggles', 'coat'],
                      'forbidden' => ['sandals', 'snack']],
         'messages' => ['intro' => "Before we touch any chemical, let's get safe. Drag your goggles and lab coat onto yourself.",
                        'success' => "Perfect, you're lab ready!",
                        'sandals' => 'Sandals leave your feet open to spills. In a lab we wear closed shoes.',
                        'snack' => 'Never eat in the lab! Chemicals can get onto food.']],

        ['step_order' => 2, 'kind' => 'place', 'title' => 'Set up the apparatus',
         'instruction_text' => 'Clamp the burette to the stand, then put the conical flask underneath it.',
         'preauthored_guidance' => 'The burette is the long thin tube with a tap. The conical flask has a wide bottom and a narrow neck.',
         'config' => ['items' => ['beaker', 'burette', 'testtube', 'flask'], 'zones' => ['stand', 'under'],
                      'answer' => ['stand' => 'burette', 'under' => 'flask']],
         'messages' => ['intro' => 'Drag the burette onto the clamp, and the conical flask under it.',
                        'success' => 'Great setup!',
                        'beaker' => 'A beaker spills when you swirl it. The conical flask is safer to swirl.',
                        'testtube' => 'A test tube is too small here. We need the conical flask.']],

        ['step_order' => 3, 'kind' => 'measure', 'title' => 'Measure the NaOH',
         'instruction_text' => 'Measure exactly 25.0 mL of sodium hydroxide and transfer it to the flask.',
         'preauthored_guidance' => 'Use small steps on the slider. The method says 25.0 mL.',
         'config' => ['unit' => 'mL', 'min' => 0, 'max' => 50, 'step' => 0.5, 'target' => 25.0, 'tolerance' => 0.5],
         'messages' => ['intro' => 'Slide to exactly 25.0 mL of NaOH, then press Transfer.', 'success' => 'Spot on!',
                        'too_low' => 'Too little base changes your answer. Measure 25.0 mL.',
                        'too_high' => 'Extra base means you will need more acid. Measure 25.0 mL.']],

        ['step_order' => 4, 'kind' => 'choose', 'title' => 'Add the indicator',
         'instruction_text' => 'Add two drops of the indicator this method uses.',
         'preauthored_guidance' => 'This method uses an indicator that is pink in a base and colourless in an acid.',
         'config' => ['items' => ['water', 'methyl_orange', 'phenolphthalein'], 'target' => 'flask', 'correct' => 'phenolphthalein',
                      'wrong' => ['water' => 'procedure', 'methyl_orange' => 'procedure']],
         'messages' => ['intro' => 'An indicator shows when the base is used up. Drag the right one into the flask.',
                        'success' => 'It turned pink! The solution is basic.',
                        'water' => "Water won't change colour, so you couldn't see the end point.",
                        'methyl_orange' => 'Methyl orange works in other titrations, but this method uses phenolphthalein.']],

        ['step_order' => 5, 'kind' => 'choose', 'title' => 'Fill the burette',
         'instruction_text' => 'Fill the burette with the acid. It starts at the 0.0 mL mark.',
         'preauthored_guidance' => 'The flask already has the base in it. What goes in the burette?',
         'config' => ['items' => ['naoh', 'hcl'], 'target' => 'burette', 'correct' => 'hcl', 'wrong' => ['naoh' => 'reagent']],
         'messages' => ['intro' => 'Drag the right bottle into the burette.', 'success' => 'Burette filled with HCl, reading 0.0 mL.',
                        'naoh' => 'That is the base, and the base is already in the flask. The acid goes in the burette.']],

        ['step_order' => 6, 'kind' => 'add_until', 'title' => 'Titrate to the end point',
         'instruction_text' => 'Add acid while swirling. Stop the moment the pink disappears.',
         'preauthored_guidance' => 'Once the pink gets lighter, use single drops so you do not go past the end point.',
         'config' => ['unit' => 'mL', 'increments' => [1.0, 0.1], 'end' => 18.4, 'tolerance' => 0.3,
                      'visual' => ['from' => 'pink', 'to' => 'colourless', 'fade_from' => 15.9]],
         'messages' => ['intro' => 'Add acid 1 mL at a time. When the pink starts to fade, switch to single drops.',
                        'success' => 'You stopped at the end point. Great control!',
                        'too_early' => 'Not yet! It is still pink, so there is still base left.',
                        'overshoot' => 'You went past the end point. Real chemists start a fresh run.']],

        ['step_order' => 7, 'kind' => 'read', 'title' => 'Read the burette',
         'instruction_text' => 'Read the volume of acid you used, to one decimal place.',
         'preauthored_guidance' => 'Each small line is 0.1 mL. Read from the bottom of the meniscus.',
         'config' => ['unit' => 'mL', 'value_from' => 'step:6', 'tolerance' => 0.1],
         'messages' => ['intro' => 'Read the burette at eye level, from the bottom of the meniscus.', 'success' => 'Correct reading!',
                        'wrong' => 'Not quite. Count the small 0.1 mL lines from the last labelled mark.']],

        ['step_order' => 8, 'kind' => 'observe', 'title' => 'What did you see?',
         'instruction_text' => 'Describe the colour change at the end point.',
         'preauthored_guidance' => 'Phenolphthalein is pink in a base and colourless once the base is neutralised.',
         'config' => ['options' => ['a' => 'Colourless turned pink', 'b' => 'Pink turned colourless',
                                    'c' => 'Blue turned yellow', 'd' => 'Nothing changed'], 'correct' => 'b'],
         'messages' => ['intro' => 'Tell me what happened to the colour at the end point.', 'success' => 'Exactly right!',
                        'wrong' => 'Think again. Phenolphthalein is pink in a base and colourless once neutralised.']],
    ],
]
```

Item ids (`goggles`, `burette`, `hcl`) map to an icon and label in one shared list in the web
app, so the same item looks the same in every experiment.
