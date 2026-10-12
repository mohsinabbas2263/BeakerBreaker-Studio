# Data Model

Nineteen entities. MySQL 8. `PK` primary key, `FK` foreign key.

| Entity | Purpose | Principal attributes |
|---|---|---|
| `schools` | Licensing institution | school_id PK, name, district, licence_start, licence_end, student_cap |
| `users` | Every account, any role | user_id PK, school_id FK, role, display_name, email, password_hash (`SR-01`), status, created_at, last_login_at |
| `student_profiles` | Student learning state | profile_id PK, user_id FK, class_id FK, total_points, rank_level (`FR-06.4`), current_streak_days (`FR-06.5`) |
| `classes` | Teaching group | class_id PK, school_id FK, title, grade_level, academic_year |
| `class_teachers` | Teacher to class assignment | class_teacher_id PK, class_id FK, user_id FK, assigned_at (`FR-09.1`) |
| `experiments` | Library entry | experiment_id PK, title, topic, difficulty, prerequisite_experiment_id FK (`FR-03.3`), chemical_equation (`FR-03.6`), explanatory_note, ar_available (`FR-05.1`), base_points |
| `experiment_steps` | Ordered step | step_id PK, experiment_id FK, step_order (`FR-03.4`), instruction_text, preauthored_guidance (`NFR-06`) |
| `experiment_quiz_questions` | Knowledge quiz item | question_id PK, experiment_id FK, prompt, options, correct_option, explanation (`FR-04.2`) |
| `experiment_sessions` | One attempt at one experiment | session_id PK, user_id FK, experiment_id FK, presentation_mode, started_at, completed_at, steps_completed (`FR-03.5`) |
| `safety_tier_questions` | Tier bank item | question_id PK, tier_number (`FR-02.1`), prompt, options, correct_option |
| `safety_attempts` | One tier attempt | attempt_id PK, user_id FK, tier_number, score (`FR-02.5`), passed, attempted_at (`FR-02.6`) |
| `certificates` | Issued safety certificate | certificate_id PK, user_id FK, certificate_reference, tier1_score, tier2_score, tier3_score, tier4_score, issued_on (`FR-02.7`) |
| `readiness_reports` | Generated readiness report | report_id PK, subject_user_id FK, requesting_user_id FK, six dimension columns (`FR-08.1`), classification (`FR-08.3`), generated_at (`FR-08.5`) |
| `rewards` | Points or badge award | reward_id PK, user_id FK, reward_type, badge_code, points_awarded, source_session_id FK, awarded_at (`FR-04.3`, `FR-06.3`) |
| `tasks` | Teacher created task | task_id PK, created_by_user_id FK, class_id FK, experiment_id FK, due_at (`FR-09.4`), created_at |
| `task_assignments` | Task per student | assignment_id PK, task_id FK, user_id FK, state (`FR-09.5`), state_changed_at |
| `notifications` | Delivered message | notification_id PK, user_id FK, notification_type (`FR-09.7`), body, created_at, read_at |
| `fcm_tokens` | Device registration | token_id PK, user_id FK, device_token, platform, registered_at (`FR-09.6`) |
| `audit_logs` | Permanent admin record | audit_id PK, actor_user_id FK, action_type, target_reference, occurred_at (`FR-10.8`) |

The six readiness dimensions (`FR-08.1`) are: procedural_accuracy, safety_compliance,
reagent_management, time_efficiency, conceptual_understanding, error_recovery.

## Schema rules

- Passwords stored only as salted hashes from an adaptive function, work factor >= 12
  (`SR-01`). Never recoverable.
- Student personally identifiable information encrypted at rest (`SR-06`).
- `audit_logs` and `certificates` rows are insert only. Neither carries an updated_at
  column, and no code path updates or deletes them (`FR-02.8`, `FR-10.8`).
- A deleted account stays recoverable for 30 days before removal (`FR-01.5`).

## State machines

`task_assignments.state`: `assigned` -> `in_progress` -> `completed`, with `overdue`
reachable from `assigned` and `in_progress` when the due date passes, and leaving `overdue`
to `in_progress` or `completed`. The due date transitions fire with no teacher action
(`FR-09.5`).

Safety certification: `not_certified` -> tier loop -> `certified`. `certified` has no
transition back (`FR-02.8`).
