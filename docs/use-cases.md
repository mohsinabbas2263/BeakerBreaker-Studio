# Use Cases

Twenty eight use cases. The ID is stable; the diagrams in `FYP/` use the same IDs.

## UC-01 Log In

- **Actor:** Student, Teacher, Administrator
- **Preconditions:** The user holds an account created by a school administrator.
- **Main flow:** 1. User opens the application and enters school identifier and password. 2. System validates the credentials. 3. System issues an access token. 4. System loads the dashboard belonging to the user role.
- **Alternate flow:** If the credentials are invalid, the system displays a message that does not indicate which credential was wrong. After five failed attempts within fifteen minutes, further attempts from that address are refused.
- **Postconditions:** The user is authenticated and the correct role dashboard is displayed.
- **Requirements:** FR-01.1, FR-01.2, FR-01.4

## UC-02 Safety Certification

- **Actor:** Student
- **Preconditions:** The student is authenticated.
- **Main flow:** 1. Student opens Safety Training. 2. System presents the lowest tier not yet passed. 3. Student studies the tier material and starts the assessment. 4. System presents questions selected at random from the tier bank. 5. Student submits answers. 6. System computes the score on the server. 7. On a score of 80 percent or above the tier is marked passed and the next tier is unlocked. 8. When all four tiers are passed the system issues the safety certificate.
- **Alternate flow:** On a score below 80 percent the system displays the result and offers a further attempt with a fresh selection of questions. The number of attempts is not limited.
- **Postconditions:** The tier is recorded as passed, or the certificate is issued if this was the fourth tier.
- **Requirements:** FR-02.1 to FR-02.9

## UC-02.1 Attempt Tier Assessment

- **Actor:** Student
- **Preconditions:** The student is authenticated and the tier being attempted is the lowest tier not yet passed.
- **Main flow:** 1. Student starts the assessment for the tier. 2. System selects the questions at random from the question bank of that tier. 3. Student answers the questions and submits them. 4. System computes the score on the server. 5. System records the attempt with its score and the time of submission.
- **Alternate flow:** On a score below 80 percent the tier is not marked as passed and a further attempt is offered with a fresh selection of questions. The number of attempts is not limited.
- **Postconditions:** The attempt is recorded, and the tier is marked as passed where the score is at least 80 percent.
- **Requirements:** FR-02.4, FR-02.5, FR-02.6

## UC-02.2 Receive Safety Certificate

- **Actor:** Student
- **Preconditions:** The student has passed all four safety tiers, each with a score of at least 80 percent.
- **Main flow:** 1. System detects that the fourth tier has been passed. 2. System generates a certificate bearing a unique identifier, the four tier scores and the date of issue. 3. System stores the certificate against the student profile. 4. System makes the certificate visible to the student and to the teacher assigned to that student's class.
- **Alternate flow:** No certificate is generated if the score in any tier is below 80 percent.
- **Postconditions:** The certificate exists, is displayed on the student profile, and is recorded in the school wide register.
- **Requirements:** FR-02.7, FR-02.8, FR-02.9, FR-10.5

## UC-03 Perform Virtual Experiment

- **Actor:** Student
- **Preconditions:** The student is authenticated and has passed Safety Tier 1.
- **Main flow:** 1. Student selects an experiment from the library. 2. System verifies that any prerequisite experiment has been completed. 3. System presents the first step. 4. Student completes each step in turn while the AI assistant provides guidance. 5. System records the completion of each step. 6. When all steps are complete the knowledge quiz is unlocked.
- **Alternate flow:** If the AI service does not respond, pre authored guidance is presented and the experiment remains completable. If the student leaves and returns, completed steps are restored.
- **Postconditions:** The experiment is recorded as complete and the knowledge quiz becomes available.
- **Requirements:** FR-03.1 to FR-03.8, NFR-05, NFR-06

## UC-04 AR Mode Session

- **Actor:** Student
- **Preconditions:** The student is authenticated and has selected an AR enabled experiment.
- **Main flow:** 1. Student taps Launch AR. 2. System checks whether the device supports augmented reality. 3. System requests camera permission. 4. System scans for a flat surface and anchors the experiment overlay to it. 5. Student performs the experiment using the rotate, guidance, and exit controls. 6. On exit the system stops the camera stream and releases device resources.
- **Alternate flow:** Where the device is unsupported, permission is refused, or no surface is detected, the system presents the clearly labelled Simulation AR view within one second. The same controls are available and the experiment remains complete.
- **Postconditions:** The experiment is performed and all camera resources are released.
- **Requirements:** FR-05.1 to FR-05.6

## UC-05 Generate AI Readiness Report

- **Actor:** Teacher
- **Preconditions:** The teacher is authenticated and the subject student belongs to a class assigned to that teacher.
- **Main flow:** 1. Teacher opens AI Readiness and selects a student. 2. System verifies the class assignment. 3. System verifies that the student has completed at least three experiments. 4. System computes the six performance dimensions. 5. System submits the dimension data to the AI service. 6. System records the returned classification and presents the report.
- **Alternate flow:** If the student belongs to another class the request is refused and the refusal is logged. If fewer than three experiments have been completed the system explains the data requirement and generates no report. If the AI service does not respond the teacher is offered a retry.
- **Postconditions:** A readiness report is stored and displayed, or the reason for not generating one is shown.
- **Requirements:** FR-08.1 to FR-08.6, FR-09.1

## UC-06 Assign Task

- **Actor:** Teacher
- **Preconditions:** The teacher is authenticated and the target students belong to a class assigned to that teacher.
- **Main flow:** 1. Teacher opens Task Assignment and selects an experiment, a target, and a due date. 2. System validates the due date and the class assignment. 3. System creates the task and one assignment record per targeted student. 4. System notifies each targeted student. 5. System advances the state of each assignment as the student works. 6. Teacher follows the states on the class dashboard.
- **Alternate flow:** A due date that is not in the future is rejected with a validation message. A target outside the teacher assigned class is refused and the refusal is logged.
- **Postconditions:** The task is created, the students are notified, and the states are visible to the teacher.
- **Requirements:** FR-09.1 to FR-09.7

## UC-06.1 Track Task Status

- **Actor:** Teacher
- **Preconditions:** A task has been assigned to one or more students of a class to which the teacher is assigned.
- **Main flow:** 1. Teacher opens the task. 2. System displays the state of the task for each targeted student. 3. System updates the state as Assigned, In Progress, Completed or Overdue as the governing condition occurs.
- **Alternate flow:** The state changes without any teacher action when the student begins the task, completes it, or the due date passes.
- **Postconditions:** The current state of the task is recorded against each targeted student.
- **Requirements:** FR-09.5, FR-09.1

## UC-07 Reset Password

- **Actor:** Student, Teacher, Administrator
- **Preconditions:** The user holds an account and has access to the email address registered against it.
- **Main flow:** 1. User requests a password reset. 2. System issues a single use token and sends it to the registered email address. 3. User opens the link and submits a new password. 4. System validates the token, stores the new password as a salted hash, and invalidates all existing tokens of that user.
- **Alternate flow:** A token that is older than 60 minutes, or that has already been used, is rejected and the user is invited to request a new one.
- **Postconditions:** The password is changed and all previous sessions of that user are ended.
- **Requirements:** FR-01.3, SR-01, SR-05

## UC-08 Browse Experiment Library

- **Actor:** Student
- **Preconditions:** The student is authenticated.
- **Main flow:** 1. Student opens the experiment library. 2. System presents the experiments available to the class of that student. 3. Student applies a filter by difficulty or by topic. 4. System presents the filtered list.
- **Alternate flow:** An experiment that is locked is shown with the reason, which is either the safety tier that has not been passed or the prerequisite experiment that has not been completed.
- **Postconditions:** The student has selected an experiment, or has left the library without selecting one.
- **Requirements:** FR-03.1, FR-03.2, FR-03.3, FR-02.3, FR-10.4

## UC-09 Take Knowledge Quiz

- **Actor:** Student
- **Preconditions:** All steps of the experiment have been completed.
- **Main flow:** 1. Student opens the quiz. 2. System presents the questions. 3. Student submits answers. 4. System grades the submission on the server and shows whether each answer was correct, with an explanation where it was not. 5. On a first score of 80 percent or above the system awards the experience points of that experiment and updates the leaderboard.
- **Alternate flow:** On a score below 80 percent the attempt is recorded and a further attempt is offered with the questions in a different order. No further points are awarded on a repeat pass.
- **Postconditions:** The attempt is recorded and, on a first pass, the reward is awarded.
- **Requirements:** FR-04.1 to FR-04.5, FR-07.2

## UC-10 Earn Rewards

- **Actor:** Student (secondary, the reward is awarded by the system)
- **Preconditions:** The student has completed an experiment with a quiz score of at least 80 percent, or has passed a safety tier.
- **Main flow:** 1. System receives the completion event. 2. System awards experience points according to the difficulty of the experiment or the tier passed. 3. System evaluates the badge criteria and awards any badge that has been earned. 4. System recalculates the rank level from the cumulative experience points.
- **Alternate flow:** Where the experience points of that experiment have already been awarded, no further points are awarded for it.
- **Postconditions:** The points, any badge and the rank level of the student are updated, and none of them is ever reduced.
- **Requirements:** FR-04.3, FR-06.1, FR-06.2, FR-06.3, FR-06.4

## UC-11 Daily Missions and Streaks

- **Actor:** Student
- **Preconditions:** The student is authenticated.
- **Main flow:** 1. System presents three daily missions to the student. 2. Student completes the activity required by a mission. 3. System records the activity and updates the count of consecutive days of activity.
- **Alternate flow:** The missions are replaced at midnight Pakistan Standard Time, whether or not they were completed. A streak badge is awarded when the count of consecutive days reaches 7, 30 or 100.
- **Postconditions:** The mission state and the streak count of the student are recorded.
- **Requirements:** FR-07.3, FR-06.5, FR-06.3

## UC-12 View Leaderboard

- **Actor:** Student, Teacher
- **Preconditions:** The user is authenticated and belongs to, or is assigned to, the class whose leaderboard is requested.
- **Main flow:** 1. User opens the leaderboard of the class. 2. System presents the display name, avatar, rank level and cumulative experience points of each student in that class. 3. System reflects any change in points within 2 seconds of the awarding event.
- **Alternate flow:** No further information about any student is presented, in particular no score, safety history, readiness classification or assessment record.
- **Postconditions:** The leaderboard is displayed and no record is altered.
- **Requirements:** FR-07.1, FR-07.2, SR-08

## UC-13 View Own Readiness Status

- **Actor:** Student
- **Preconditions:** The student is authenticated and a readiness report has been generated for that student.
- **Main flow:** 1. Student opens their readiness status. 2. System presents the six dimension values computed for that student and the classification derived from them.
- **Alternate flow:** Where no report has yet been generated, the system states that the report is not yet available.
- **Postconditions:** The student has seen their own values and classification, and no record is altered.
- **Requirements:** FR-08.6, FR-08.1, FR-08.3, SR-08

## UC-14 Monitor Class

- **Actor:** Teacher
- **Preconditions:** The teacher is authenticated and is formally assigned to the class being monitored.
- **Main flow:** 1. Teacher opens the class view. 2. System presents, for each student of the class, the cumulative experience points, the number of experiments completed, the safety certification status and the most recent activity time. 3. System updates the view within 5 seconds of an event that affects a displayed value.
- **Alternate flow:** A request for a class to which the teacher is not assigned is rejected with an authorisation error.
- **Postconditions:** The class view is displayed and no record is altered.
- **Requirements:** FR-09.1, FR-09.2, FR-09.3, SR-04

## UC-15 Broadcast Notification

- **Actor:** Teacher
- **Preconditions:** The teacher is authenticated and is formally assigned to the class being addressed.
- **Main flow:** 1. Teacher selects the class and the notification type, which is task, reminder, safety alert or announcement. 2. Teacher writes the message. 3. System removes any markup and limits the message to 500 characters. 4. System delivers the notification to every student of the class.
- **Alternate flow:** A message longer than 500 characters is refused until it is shortened. A request for a class to which the teacher is not assigned is rejected.
- **Postconditions:** The notification is recorded and delivered to each targeted student.
- **Requirements:** FR-09.7, FR-09.6, FR-09.1

## UC-16 Review Class Reports

- **Actor:** Teacher
- **Preconditions:** The teacher is authenticated and is formally assigned to the class whose reports are requested.
- **Main flow:** 1. Teacher opens the reports of the class. 2. System presents the progress of the class together with the readiness reports already generated for its students. 3. Teacher may request a new readiness report for an individual student.
- **Alternate flow:** A request for a class to which the teacher is not assigned is rejected with an authorisation error.
- **Postconditions:** The reports are displayed, and any newly requested report is recorded.
- **Requirements:** FR-09.1, FR-08.2, FR-08.5, SR-04

## UC-17 Manage User Accounts

- **Actor:** Administrator
- **Preconditions:** The administrator is authenticated.
- **Main flow:** 1. Administrator creates, edits, suspends or deletes a user account. 2. System applies the change. 3. System writes an audit entry recording the operation, the actor and the time.
- **Alternate flow:** A deleted account is retained in a recoverable state for 30 days before it is removed. No account can be created by any route other than this use case.
- **Postconditions:** The account reflects the change, and an immutable audit entry exists.
- **Requirements:** FR-01.5, FR-01.6, FR-10.8, SR-04

## UC-18 Bulk Import Students

- **Actor:** Administrator
- **Preconditions:** The administrator is authenticated.
- **Main flow:** 1. Administrator opens Student Management and uploads a delimited file of up to 500 rows. 2. System validates every row. 3. System creates the accounts and sends a welcome message to each new student. 4. System writes an audit entry for the operation.
- **Alternate flow:** If any row fails validation no account is created from the file, and the system reports each failing row individually.
- **Postconditions:** The accounts exist and the operation is recorded in the audit log.
- **Requirements:** FR-10.2, FR-10.3, FR-10.8

## UC-19 Manage Classes

- **Actor:** Administrator
- **Preconditions:** The administrator is authenticated.
- **Main flow:** 1. Administrator creates or modifies a class. 2. Administrator assigns students and teachers to that class. 3. System records the assignments.
- **Alternate flow:** A teacher who is not assigned to a class acquires no access to its students, since every teacher action is scoped by this assignment.
- **Postconditions:** The class and its assignments are recorded, and the scope of each assigned teacher is established.
- **Requirements:** FR-10.1, FR-09.1, SR-04

## UC-20 Configure Experiment Library

- **Actor:** Administrator
- **Preconditions:** The administrator is authenticated and the class to be configured exists.
- **Main flow:** 1. Administrator selects a class. 2. Administrator enables or disables an experiment for that class. 3. System applies the change with immediate effect.
- **Alternate flow:** An experiment disabled for a class does not appear in the library of the students of that class, and an experiment already in progress is allowed to finish.
- **Postconditions:** The experiment library visible to the class reflects the change.
- **Requirements:** FR-10.4, FR-03.1

## UC-21 Manage Certificate Registry

- **Actor:** Administrator
- **Preconditions:** The administrator is authenticated.
- **Main flow:** 1. Administrator opens the school wide register of issued safety certificates. 2. System presents every certificate issued within the school. 3. Administrator exports the register. 4. System writes an audit entry recording the export.
- **Alternate flow:** No certificate may be deleted or revoked from the register by any operation.
- **Postconditions:** The register is displayed or exported, and an audit entry exists for any export.
- **Requirements:** FR-10.5, FR-02.8, FR-10.7, FR-10.8

## UC-22 Generate Analytics and Export Reports

- **Actor:** Administrator
- **Preconditions:** The administrator is authenticated.
- **Main flow:** 1. Administrator requests the school analytics. 2. System computes the certification rate, the experiment completion rate and the distribution of readiness classifications. 3. Administrator exports the report in portable document format or as delimited text. 4. System writes an audit entry recording the export.
- **Alternate flow:** An export that cannot be completed within 10 seconds is reported as a failure rather than returned incomplete.
- **Postconditions:** The analytics are displayed, the export file is produced, and an immutable audit entry exists.
- **Requirements:** FR-10.6, FR-10.7, FR-10.8

## UC-25 Receive AI Step Guidance

- **Actor:** Student (primary), AI language service (secondary)
- **Preconditions:** The student is performing an experiment and a step is currently presented.
- **Main flow:** 1. System sends the context of the current step to the AI language service. 2. Service returns guidance for that step. 3. System presents the guidance to the student with avatar narration, within 2 seconds, or within 200 milliseconds where the guidance has been cached.
- **Alternate flow:** Where the service does not respond within the limit, or is unavailable, the pre authored guidance held for that step is presented instead and the experiment remains completable.
- **Postconditions:** Guidance for the current step has been presented by one route or the other.
- **Requirements:** FR-03.7, NFR-06

## UC-26 Verify Student Certificates

- **Actor:** Teacher
- **Preconditions:** The teacher is authenticated and is formally assigned to the class of the student whose certificate is being verified.
- **Main flow:** 1. Teacher opens the profile of a student of an assigned class. 2. System presents the safety certificate of that student, with its unique identifier, the four tier scores and the date of issue.
- **Alternate flow:** Where no certificate has been issued, the system states which tiers remain to be passed. A request for a student outside an assigned class is rejected.
- **Postconditions:** The certificate status of the student has been displayed to the teacher.
- **Requirements:** FR-02.9, FR-02.7, FR-09.1, SR-04

## UC-27 Record Audit Entry

- **Actor:** System (invoked by an administrative operation)
- **Preconditions:** An administrative account operation, a certificate issue or a report export has taken place.
- **Main flow:** 1. System captures the actor, the type of action, the reference of the target and the time of the operation. 2. System writes the entry to the audit log. 3. System retains the entry for no less than 7 years.
- **Alternate flow:** No entry may be modified or deleted after it has been written, by any user or by any operation.
- **Postconditions:** An immutable audit entry exists for the operation that invoked this use case.
- **Requirements:** FR-10.8
