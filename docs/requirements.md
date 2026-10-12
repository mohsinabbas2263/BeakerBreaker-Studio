# Requirements

Source of truth: Chapter 3 of the FYP report. **Do not renumber these IDs.**
Every commit message, test name and code comment that implements a requirement cites its ID.

Priority: **M** mandatory, **D** desirable, **O** optional.

## Authentication and Account Management

| ID | Requirement | Priority |
|---|---|---|
| `FR-01.1` | The system shall provide role-based login for Student, Teacher and Administrator, and shall direct each role to its own dashboard. | M |
| `FR-01.2` | The system shall authenticate requests using signed access tokens valid for 24 hours from issue. | M |
| `FR-01.3` | The system shall provide email-based password reset using single-use tokens that shall expire 60 minutes after issue. | M |
| `FR-01.4` | The system shall reject further login attempts from an address after 5 failed attempts within 15 minutes. | M |
| `FR-01.5` | The system shall permit only the Administrator to create, edit, suspend and delete user accounts, and shall retain a deleted account in a recoverable state for 30 days. | M |
| `FR-01.6` | The system shall not provide any means by which a person may create an account without administrator action. | M |

## Safety Certification

| ID | Requirement | Priority |
|---|---|---|
| `FR-02.1` | The system shall present safety instruction and assessment in four sequential tiers: Basic Lab Rules, Chemical Handling, Fire and Heat Safety, and First Aid and Emergency. | M |
| `FR-02.2` | The system shall make a tier available to a student only after the preceding tier has been passed with a score of at least 80 percent. | M |
| `FR-02.3` | The system shall prevent a student from opening any experiment until Tier 1 has been passed. | M |
| `FR-02.4` | The system shall select tier assessment questions at random from the question bank of that tier on each attempt. | M |
| `FR-02.5` | The system shall compute every assessment score on the server and shall not accept a score computed by the client. | M |
| `FR-02.6` | The system shall permit a student to reattempt a failed tier assessment without limit. | M |
| `FR-02.7` | The system shall issue a digital safety certificate bearing a unique identifier, the four tier scores and the date of issue, once all four tiers have been passed. | M |
| `FR-02.8` | The system shall not issue a certificate to a student whose score in any tier is below 80 percent, and shall not delete or revoke an issued certificate. | M |
| `FR-02.9` | The system shall display an issued certificate on the student profile and to the teacher assigned to that student’s class. | M |

## Experiment Management

| ID | Requirement | Priority |
|---|---|---|
| `FR-03.1` | The system shall provide a library of no fewer than 30 chemistry experiments aligned to the Class 9 and Class 10 curriculum. | M |
| `FR-03.2` | The system shall permit a student to filter the experiment library by difficulty and by topic. | D |
| `FR-03.3` | The system shall prevent a student from opening an experiment whose prerequisite experiment has not been completed, and shall display the reason. | M |
| `FR-03.4` | The system shall present experiment steps in their declared order and shall require each step to be completed before the next is presented. | M |
| `FR-03.5` | The system shall record the completion of each step to persistent storage at the time of completion, and shall restore completed steps when a student resumes an incomplete experiment. | M |
| `FR-03.6` | The system shall display the governing chemical equation and an explanatory note for each experiment. | M |
| `FR-03.7` | The system shall present contextual AI guidance for the current step within 2 seconds, or within 200 milliseconds where that guidance has been cached. | M |
| `FR-03.8` | The system shall permit a student to repeat a completed experiment without limit. | M |

## Knowledge Assessment

| ID | Requirement | Priority |
|---|---|---|
| `FR-04.1` | The system shall make an experiment’s knowledge quiz available only after all steps of that experiment have been completed. | M |
| `FR-04.2` | The system shall indicate whether each submitted answer is correct or incorrect immediately, and shall present an explanatory note where the answer is incorrect. | M |
| `FR-04.3` | The system shall award the experience points of an experiment once only, upon the first score of at least 80 percent. | M |
| `FR-04.4` | The system shall record every quiz attempt with its score, the answers submitted and the time of submission. | M |
| `FR-04.5` | The system shall permit a student to reattempt a failed quiz, presenting the questions in a different order on each attempt. | M |

## Augmented Reality Mode

| ID | Requirement | Priority |
|---|---|---|
| `FR-05.1` | The system shall provide augmented reality presentation for no fewer than 4 experiments. | D |
| `FR-05.2` | The system shall request camera permission before initiating augmented reality presentation. | M |
| `FR-05.3` | The system shall detect a horizontal surface within 3 seconds on a supported device under normal indoor lighting, and shall anchor the experiment overlay to that surface. | D |
| `FR-05.4` | The system shall present a clearly labelled simulated presentation within 1 second where the device is unsupported, camera permission is refused, or no surface is detected. | M |
| `FR-05.5` | The system shall provide the same experiment controls in the simulated presentation as in the live presentation. | M |
| `FR-05.6` | The system shall release all camera and motion sensor resources when augmented reality presentation ends. | M |

## Gamification, Leaderboard and Missions

| ID | Requirement | Priority |
|---|---|---|
| `FR-06.1` | The system shall award experience points on completion of an experiment according to its difficulty, and on the passing of a safety tier. | D |
| `FR-06.2` | The system shall not award experience points for the speed at which a student completes an experiment or a quiz. | M |
| `FR-06.3` | The system shall provide no fewer than 60 distinct badges, each of which shall be awarded to a given student no more than once. | D |
| `FR-06.4` | The system shall assign a student to one of 8 rank levels according to cumulative experience points, and shall not reduce a student’s points or rank level under any operation. | D |
| `FR-06.5` | The system shall record consecutive days of activity and shall award a badge at 7, 30 and 100 consecutive days. | O |
| `FR-07.1` | The system shall present a class leaderboard showing only display name, avatar, rank level and cumulative experience points. | D |
| `FR-07.2` | The system shall reflect a change in experience points on the leaderboard within 2 seconds of the awarding event. | D |
| `FR-07.3` | The system shall present 3 daily missions to each student, replacing them at midnight Pakistan Standard Time. | O |

## AI Readiness Reporting

| ID | Requirement | Priority |
|---|---|---|
| `FR-08.1` | The system shall compute six performance dimensions for each student: procedural accuracy, safety compliance, reagent management, time efficiency, conceptual understanding and error recovery. | M |
| `FR-08.2` | The system shall generate a readiness report on a teacher’s request and shall present it within 5 seconds. | M |
| `FR-08.3` | The system shall classify the subject of a readiness report as Not Ready, Approaching Readiness, or Ready for Real Lab. | M |
| `FR-08.4` | The system shall decline to generate a report for a student who has completed fewer than 3 experiments, and shall state the reason. | M |
| `FR-08.5` | The system shall record each generated report with the teacher, the student, the six dimension values, the classification and the time of generation. | M |
| `FR-08.6` | The system shall present to a student their own dimension values and classification, and shall not present those of any other student. | M |

## Teacher Class Management

| ID | Requirement | Priority |
|---|---|---|
| `FR-09.1` | The system shall restrict every teacher action to students of a class to which that teacher is formally assigned, and shall reject any other request. | M |
| `FR-09.2` | The system shall present to a teacher, for each student of an assigned class, the cumulative experience points, the number of experiments completed, the safety certification status and the most recent activity time. | M |
| `FR-09.3` | The system shall update the teacher class view within 5 seconds of an event affecting a displayed value. | D |
| `FR-09.4` | The system shall permit a teacher to assign an experiment task to an individual student, a group, or an entire assigned class, with a due date, and shall reject a due date that is not in the future. | M |
| `FR-09.5` | The system shall record the state of each task assignment as Assigned, In Progress, Completed or Overdue, and shall change that state without teacher intervention when the governing condition occurs. | M |
| `FR-09.6` | The system shall deliver notification of a newly assigned task to each targeted student within 60 seconds. | M |
| `FR-09.7` | The system shall permit a teacher to broadcast a notification of type task, reminder, safety alert or announcement to an assigned class, limited to 500 characters with markup removed. | D |

## Administration and Reporting

| ID | Requirement | Priority |
|---|---|---|
| `FR-10.1` | The system shall permit the Administrator to create and modify classes and to assign students and teachers to them. | M |
| `FR-10.2` | The system shall accept bulk creation of student accounts from a delimited file of no more than 500 rows, completing within 60 seconds. | D |
| `FR-10.3` | The system shall create no account from a bulk file in which any row fails validation, and shall report each failing row individually. | M |
| `FR-10.4` | The system shall permit the Administrator to enable or disable an experiment for a specified class with immediate effect. | D |
| `FR-10.5` | The system shall maintain a school-wide register of issued safety certificates and shall permit its export. | M |
| `FR-10.6` | The system shall generate school-level analytics comprising the certification rate, the experiment completion rate and the distribution of readiness classifications. | D |
| `FR-10.7` | The system shall export a generated report in portable document format or delimited text within 10 seconds. | D |
| `FR-10.8` | The system shall record every administrative account operation, certificate issue and report export as an entry that shall not subsequently be modified or deleted, and shall retain such entries for no less than 7 years. | M |

## Non-Functional Requirements

| ID | Requirement | Priority |
|---|---|---|
| `NFR-01` | The system shall return a response to a server request within 300 milliseconds at the 95th percentile, excluding requests to the external AI service. |  |
| `NFR-02` | The web portal shall achieve first contentful paint within 3 seconds over a 4 megabit per second connection. |  |
| `NFR-03` | The system shall be hosted on cloud infrastructure and shall be available in no less than 99.5 percent of each calendar month. |  |
| `NFR-04` | The system shall serve 1000 concurrent authenticated users without exceeding the response time stated in NFR-01. |  |
| `NFR-05` | The system shall not lose any recorded student progress, experience points, badge award or certificate as a consequence of a page refresh, a session expiry or a network interruption. |  |
| `NFR-06` | The system shall present pre-authored guidance and shall permit every experiment to be completed when the external AI service is unavailable. |  |
| `NFR-07` | A student who has not previously used the system shall be able to complete login, Tier 1 safety training and a first experiment without assistance from another person. |  |
| `NFR-08` | All web interfaces shall conform to the Web Content Accessibility Guidelines 2.1 at conformance level AA. |  |
| `NFR-09` | The portal shall operate correctly on Chrome 90 and later, Firefox 88 and later, Safari 14 and later, and Edge 90 and later. |  |
| `NFR-10` | Automated test coverage shall be no less than 70 percent overall and 100 percent for authentication, score computation, reward award and safety certification logic. |  |
| `NFR-11` | The system shall operate on a client device having 3 gigabytes of memory and a browser supporting hardware-accelerated 3D rendering. |  |
| `NFR-12` | Augmented reality surface detection shall complete within 3 seconds on at least 5 tested Android models and 2 tested iOS models. |  |

## Security Requirements

| ID | Requirement | Priority |
|---|---|---|
| `SR-01` | The system shall store passwords only as salted hashes produced by an adaptive hashing function with a work factor of no less than 12. |  |
| `SR-02` | The system shall conduct all communication over TLS 1.3 or later. |  |
| `SR-03` | The system shall reject with an authentication error any request to a protected function that does not carry a valid access token. |  |
| `SR-04` | The system shall enforce role-based access control on the server, and shall reject with an authorisation error any request outside the permissions of the caller’s role. |  |
| `SR-05` | The system shall expire a user session after 24 hours of inactivity and shall invalidate all tokens of a user whose password is changed or whose account is suspended. |  |
| `SR-06` | The system shall encrypt student personally identifiable information while stored. |  |
| `SR-07` | The system shall validate every input on the server irrespective of any validation performed by the client. |  |
| `SR-08` | The system shall not present to a student the score, safety history, readiness classification or assessment record of any other student. |  |
| `SR-09` | The system shall not present or generate content relating to the acquisition or handling of hazardous substances outside the virtual environment. |  |
| `SR-10` | The system shall create a recoverable copy of all retained data at intervals not exceeding 24 hours and shall retain each copy for no less than 30 days. |  |

## Rules that cut across everything

These three appear in many requirements at once. Breaking any of them is a defect, not a style choice.

1. **All scoring happens on the server.** A score submitted by a client is never accepted (`FR-02.5`, `SR-07`).
2. **Every experiment stays completable when the AI service is down.** Pre authored guidance is held for every step (`NFR-06`).
3. **No account exists without administrator action.** There is no sign up route (`FR-01.6`).

Two more worth pinning:

- Points and rank level are **never reduced** by any operation, and are never awarded for speed (`FR-06.2`, `FR-06.4`).
- An issued safety certificate and an audit entry are **written once and never changed or deleted** (`FR-02.8`, `FR-10.8`).
