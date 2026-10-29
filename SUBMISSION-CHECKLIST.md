# Week 13 requirements and evidence checklist

Project 29: Thesis Consultation Reminder System. Updated 2026-10-29 (Asia/Manila).

Sources: original module, complete Module 13 including Flutter foundation, five topic checkpoints, guided procedure, activities, assessment and reflection; project guide, common rules, assessment mapping and Project 29. The source DOCX files remain in the parent workspace. Filenames and milestone boundaries below are organization choices, not teacher requirements.

Status: **prepared** means a reviewable plan exists, **pending** means the implementation or evidence does not yet exist, **complete** means the specified output was actually verified. A design does not prove execution.

| ID | Requirement and origin | Status | Artifact / planned proof |
| --- | --- | --- | --- |
| GOV-01 | One individual approved/registered project; no production data (guide) | Student reports assigned and approved; official registry not inspected | [Scope and authority](docs/DESIGN.md#scope-and-authority); student must retain instructor confirmation |
| GOV-02 | Fresh history, separate folder, student commits/pushes | Commits through297f266 verified; Flutter increment ready for student commit | [Checkpoint07](docs/CHECKPOINT-07.md); assistant never commits/pushes |
| PREP-01 | Feature branch, evidence folders, lab log with date, environment, versions, scope (guided part 1) | Complete for foundation | [Lab log](docs/LAB-LOG.md) |
| FND-01 | Fresh scaffold/API connection (implementation milestone, not a separate teacher requirement) | Verified; widget tests blocked | [Results](evidence/test-results/FOUNDATION-RESULTS.md); five backend tests, analysis, web build, real browser connection |
| DATA-01 | Consultation/role/action-item data foundation (implementation milestone supporting workflow) | Committed 27960ba; verified | [Database guide](docs/DATABASE.md), [data evidence](evidence/test-results/DATA-RESULTS.md) |
| API-01 | Session authentication, scoped reads, request/confirmation (implementation milestone supporting workflow) | Committed d2fe529; verified | [API contract](docs/API-CONTRACT.md), [initial API evidence](evidence/test-results/API-RESULTS.md) |
| API-02 | Meeting outcomes/action/follow-up APIs (implementation milestone supporting workflow) | Committed 4a51934; verified | [Workflow evidence](evidence/test-results/WORKFLOW-RESULTS.md) |
| SCAN-01 | Durable bounded four-kind scans | Committed 0c8a274; verified | [Scan results](evidence/test-results/SCAN-RESULTS.md); delivery type refinement regressed |
| DES-01 | Actors, trigger, trusted inputs, boundary, outputs, dependencies, human approval, non-goals (topic 1) | Prepared | [Design](docs/DESIGN.md) |
| DES-02 | Data-flow/sequence diagram marks trust, credentials, sensitive data, logs and failure/approval paths | Prepared | [Design diagram](docs/DESIGN.md#data-flow-and-trust-boundaries) |
| DES-03 | At least two approaches with fit, security, reliability, performance, effort, skills, ownership and verification tradeoffs | Prepared; execution evidence pending | [Decision record](docs/DESIGN.md#decision-record) |
| DES-04 | Pattern matrix and trigger contract; latency, clock/timezone, duplicate, ordering and back-pressure concerns (topic 2) | Prepared | [Trigger contract](docs/DESIGN.md#automation-trigger-contract) |
| DES-05 | Failure taxonomy, retry policy and recovery runbook | Backend and selected UI recovery verified; final report pending | [Delivery runbook](docs/DELIVERY.md), [Flutter results](evidence/test-results/FLUTTER-RESULTS.md) |
| DES-06 | Protected configuration and security controls | Backend/client controls and selected evidence verified; report pending | [Flutter guide](docs/FLUTTER.md), private-value candidate audit |
| DES-07 | Reliability and maintainability report supported by measurements (topic 5) | Pending | Weekly report, counts/timings, error/retry rates, queue depth, limitations |
| WF-01 | Request/agenda and assigned-adviser confirmation | API and actual Flutter path verified | [Flutter results](evidence/test-results/FLUTTER-RESULTS.md); retained future consultation16 |
| WF-02 | Outcome, action deadlines/completion and follow-up | API and selected actual Flutter path verified | [Flutter results](evidence/test-results/FLUTTER-RESULTS.md); elapsed fictional7, action4, follow-up4 |
| AUTO-01 | Consultation reminders | Scan/receiver acceptance and UI receipt/history verified | [Flutter results](evidence/test-results/FLUTTER-RESULTS.md); work19 recovery, one new reminder still pending |
| AUTO-02 | Missed-session handling | Inference/audit/accepted notice/UI review label verified; correction policy unresolved | [Flutter results](evidence/test-results/FLUTTER-RESULTS.md); not attendance proof |
| AUTO-03 | Action-item deadlines | Scan/acceptance and source-linked UI verified | [Deadline notice](evidence/screenshots/application/w13-action-deadline-notice.jpg) |
| AUTO-04 | Progress summaries | Scan/earlier acceptance and dated count UI verified; new snapshots pending | [Tablet snapshot](evidence/screenshots/application/w13-progress-summary-tablet-768.jpg) |
| AUTO-05 | Validate work, bounded service, audit and role notification | Controlled receiver/API/UI path verified; no external messaging | [Delivery](docs/DELIVERY.md), [Flutter](docs/FLUTTER.md) |
| REL-01 | Bounded retries/timeout/transient recovery/permanent review | Backend and selected real-browser review/recovery verified | [Flutter result map](evidence/test-results/FLUTTER-RESULTS.md), earlier delivery evidence |
| REL-02 | Duplicate protection/interruption recovery/finite retries | Tested to recorded scope; real kill/concurrency/continuous endurance unverified | [Delivery results](evidence/test-results/DELIVERY-RESULTS.md); Activity2 interpretation pending |
| SEC-01 | Authentication, authorization, validation and safe errors | API and selected browser cases verified; browser419/full422 coverage unverified | [Browser/effect cases](evidence/test-results/FLUTTER-RESULTS.md) |
| SEC-02 | Protected secrets/least privilege/fictional data/safe logs | Prototype controls verified; report/dependency review pending | [Secret check](evidence/test-results/flutter-secret-check.json), safe logs; runtime DDL privilege limitation disclosed |
| WEB-01 | Flutter Web navigation, workflow and reusable widgets | Implemented; actual API/browser path verified | [Screen/source guide](docs/FLUTTER.md), source and real browser captures |
| WEB-02 | Client validation and authoritative server validation | Selected email/text/date/authorization cases verified; exhaustive mapping unverified | [Case map](evidence/test-results/FLUTTER-RESULTS.md), backend regression |
| WEB-03 | Loading/success/empty/validation/auth/authz/network/server failure/recovery | Selected actual states verified; session419/uncertain committed-write UI not exercised | [19 browser checks](evidence/test-results/flutter-browser-checks.json) |
| WEB-04 | Desktop/tablet/narrow, keyboard/labels/focus/status feedback | Selected1280/768/390 layouts and Tab/Enter verified; full accessibility audit not claimed | [Viewport evidence](evidence/test-results/FLUTTER-RESULTS.md) |
| TEST-01 | Success plus at least four adverse paths | Actual API/service/browser normal/invalid/unauthorized/unavailable/timeout/recovery recorded | [Flutter results](evidence/test-results/FLUTTER-RESULTS.md) and earlier maps |
| TEST-02 | Expected/actual/build/environment/time/data/evidence | Recorded for implemented backend/browser cases; automated Flutter suite unfinished | [Evidence index](evidence/README.md), hashes, case JSON and blocker |
| TEST-03 | Predict controlled failure; preserve failed run; justified improvement, retest and regression | Pending | Activity 2 and retained original/retest records |
| ACT-01 | Three contrasting office workflows, compare at least two patterns for each against latency/volume/reliability/security/staffing | Pending | Main weekly report Activity 1, matrix, diagram and reproducible proof |
| ACT-02 | Controlled failure injection proves no duplicate processing, endless retries or silent data loss | Pending | Main weekly report Activity 2, source/config, metrics, logs, reruns |
| ACT-03 | Each activity: assumptions/constraints/dependencies/acceptance role, adverse case, limitations, next improvement and one-page interpretation separating fact/conclusion/recommendation | Pending | Two clearly labeled one-page interpretations in main report |
| SA7-01 | SA7 case plus lab portfolio: source, example config, diagram, logs, failure evidence, recovery notes, maintainability | Pending | Main report and linked supporting artifacts |
| SA7-02 | Concept explanation/control selection (15%); guided Flutter/API build (35%); adverse test (25%); report/viva (25%) | Pending | Assessment mapping in report; student oral review remains human work |
| DOC-01 | Reproducible source/setup/recovery/limitations | Current backend/frontend documented; final assessment/maintenance report pending | README, [Flutter guide](docs/FLUTTER.md), manifests/migrations/seeders |
| EVD-01 | Actual saved-source VS Code and running-interface screenshots | Complete for selected implemented source/workflow/failure/viewport cases | [Source capture](evidence/screenshots/source-code/w13-flutter-cookie-csrf-vscode.jpg), [browser index](evidence/test-results/FLUTTER-RESULTS.md) |
| EVD-02 | Safe traces/logs/timings/indexed evidence | Backend and selected UI/effect evidence recorded; final report pending | [Evidence index](evidence/README.md) |
| DEMO-01 | Short controlled demonstration and backup path if dependency unavailable | Pending | Demo script and preserved execution evidence |
| REF-01 | Four reflection prompts: influential decision/evidence, hard failure, independent contribution, next verification/out-of-scope work | Pending student input | Main report; never fabricate personal understanding |
| INT-01 | Acknowledge AI, third-party code/libraries/sources; student can reproduce, explain and defend | Pending student review | Report references/AI acknowledgment and walkthrough questions |
| FINAL-01 | Technical requirements verified, documentation aligned, honest limitations, no generated dependencies/secrets in submission | Pending | Final requirement audit |
| FINAL-02 | Student commits/pushes, verifies remote and actual submission; instructor review | Pending | Git history/remote evidence and student confirmation |

Backend and selected Flutter/browser paths are verified to the linked scope. Week13 remains incomplete: activities/SA7, maintainability/demo, final student reflection/review and commit/submission steps remain pending. Automated Flutter execution is unfinished: browser runner stalled, zero executed, prior VM policy blocker unresolved. No future-week work started.
