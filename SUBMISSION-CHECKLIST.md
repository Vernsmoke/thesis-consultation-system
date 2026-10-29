# Week 13 requirements and evidence checklist

Project 29: Thesis Consultation Reminder System. Updated 2026-10-29 (Asia/Manila).

Sources: original module, complete Module 13 including Flutter foundation, five topic checkpoints, guided procedure, activities, assessment and reflection; project guide, common rules, assessment mapping and Project 29. The source DOCX files remain in the parent workspace. Filenames and milestone boundaries below are organization choices, not teacher requirements.

Status: **prepared** means a reviewable plan exists, **pending** means the implementation or evidence does not yet exist, **complete** means the specified output was actually verified. A design does not prove execution.

| ID | Requirement and origin | Status | Artifact / planned proof |
| --- | --- | --- | --- |
| GOV-01 | One individual approved/registered project; no production data (guide) | Student reports assigned and approved; official registry not inspected | [Scope and authority](docs/DESIGN.md#scope-and-authority); student must retain instructor confirmation |
| GOV-02 | Fresh Week 13 history, separate folder, student commits/pushes (student) | Commits through 0c8a274 verified; delivery increment uncommitted | Student alone commits/pushes; [checkpoint 06](docs/CHECKPOINT-06.md) |
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
| DES-05 | Failure taxonomy, retry policy and recovery runbook (topic 3) | Implemented/verified backend; UI pending | [Delivery contract/runbook](docs/DELIVERY.md) |
| DES-06 | Protected configuration and security controls (topic 4) | Backend controls/evidence verified; report/UI pending | [Delivery evidence](evidence/test-results/DELIVERY-RESULTS.md), private configuration/ignore checks |
| DES-07 | Reliability and maintainability report supported by measurements (topic 5) | Pending | Weekly report, counts/timings, error/retry rates, queue depth, limitations |
| WF-01 | Consultation request with agenda; assigned adviser confirms schedule (Project 29) | API/audit verified; Flutter demonstration pending | [API results](evidence/test-results/API-RESULTS.md); actual consultation 4 and two audit records |
| WF-02 | Meeting status, action items with deadlines and follow-up (Project 29) | API/audit verified; Flutter workflow pending | [Workflow results](evidence/test-results/WORKFLOW-RESULTS.md); manual missed outcome is not automated handling |
| AUTO-01 | Consultation reminders | Scan and receiver acceptance verified; Flutter pending | [Delivery evidence](evidence/test-results/DELIVERY-RESULTS.md), 10 accepted reminders |
| AUTO-02 | Missed-session handling | Automatic inference/audit and 3 accepted notices verified; human review UI/correction policy pending | [Scan](evidence/test-results/SCAN-RESULTS.md), [delivery](evidence/test-results/DELIVERY-RESULTS.md); not attendance proof |
| AUTO-03 | Action-item deadlines | Scan and 2 receiver acceptances verified; UI pending | [Delivery evidence](evidence/test-results/DELIVERY-RESULTS.md) |
| AUTO-04 | Progress summaries | Daily snapshot/dedup and 2 receiver acceptances verified; UI pending | [Delivery evidence](evidence/test-results/DELIVERY-RESULTS.md) |
| AUTO-05 | Validate queued records, bounded service call, auditable outcome, responsible role notification | Backend verified with controlled receiver; notification/review UI pending | [Contract/runbook](docs/DELIVERY.md), scoped notifications API and actual receipts |
| REL-01 | Bounded retries, timeout, transient recovery, permanent review | Backend verified; UI/report pending | [Actual retry/timeout/exhaustion/recovery](evidence/test-results/DELIVERY-RESULTS.md) |
| REL-02 | Duplicate protection and interruption recovery, finite retries | Backend tests/actual service verified; interruption boundary simulated, actual kill/concurrency unverified | [Delivery results](evidence/test-results/DELIVERY-RESULTS.md); Activity 2 interpretation pending |
| SEC-01 | Authentication, authorization, safe errors, validation | Workflow and automation/recovery API verified; UI pending | [API contract](docs/API-CONTRACT.md), 63-test suite and live permission cases |
| SEC-02 | Protected secrets, least privilege, controlled data, safe logs/correlation | Backend prototype controls verified; full report/dependency review pending | Ignored private settings, [safe logs](evidence/logs/automation-delivery.jsonl); runtime DDL privilege limitation disclosed |
| WEB-01 | Flutter Web source with minimum workflow navigation and reusable widgets | Pending | Source and real browser/API demonstration |
| WEB-02 | Client validation agrees with authoritative server validation | Pending | Invalid form and API results |
| WEB-03 | Loading, success, empty, validation, authentication, authorization, network and server failure states | Pending | Real browser captures plus repeatable checks |
| WEB-04 | Desktop, tablet and narrow layout; keyboard, labels, focus, contrast/status feedback | Pending | Actual viewport captures and usability record |
| TEST-01 | Success plus at least four adverse paths | API/service normal/invalid/unauthorized/unavailable/recovery executed; browser pending | [Delivery results](evidence/test-results/DELIVERY-RESULTS.md), prior workflow/API results |
| TEST-02 | Expected/actual/build/environment/time/data/evidence | Recorded for backend increments; full UI verification pending | [Evidence index](evidence/README.md), source hashes and dated records |
| TEST-03 | Predict controlled failure; preserve failed run; justified improvement, retest and regression | Pending | Activity 2 and retained original/retest records |
| ACT-01 | Three contrasting office workflows, compare at least two patterns for each against latency/volume/reliability/security/staffing | Pending | Main weekly report Activity 1, matrix, diagram and reproducible proof |
| ACT-02 | Controlled failure injection proves no duplicate processing, endless retries or silent data loss | Pending | Main weekly report Activity 2, source/config, metrics, logs, reruns |
| ACT-03 | Each activity: assumptions/constraints/dependencies/acceptance role, adverse case, limitations, next improvement and one-page interpretation separating fact/conclusion/recommendation | Pending | Two clearly labeled one-page interpretations in main report |
| SA7-01 | SA7 case plus lab portfolio: source, example config, diagram, logs, failure evidence, recovery notes, maintainability | Pending | Main report and linked supporting artifacts |
| SA7-02 | Concept explanation/control selection (15%); guided Flutter/API build (35%); adverse test (25%); report/viva (25%) | Pending | Assessment mapping in report; student oral review remains human work |
| DOC-01 | Reproducible source/setup/recovery/limitations | Current backend documented; final report/frontend/maintainability pending | README, [delivery guide](docs/DELIVERY.md), manifests/migrations/seeders |
| EVD-01 | Actual saved-source VS Code screenshots and actual running Flutter browser screenshots; no secrets | Foundation captures complete; required workflow/failure captures pending | [Index](evidence/README.md); connected screen, narrow layout and actual VS Code health route |
| EVD-02 | Safe API traces, logs, timings and indexed evidence | Backend delivery/workflow evidence complete; UI/report pending | [Evidence index](evidence/README.md) |
| DEMO-01 | Short controlled demonstration and backup path if dependency unavailable | Pending | Demo script and preserved execution evidence |
| REF-01 | Four reflection prompts: influential decision/evidence, hard failure, independent contribution, next verification/out-of-scope work | Pending student input | Main report; never fabricate personal understanding |
| INT-01 | Acknowledge AI, third-party code/libraries/sources; student can reproduce, explain and defend | Pending student review | Report references/AI acknowledgment and walkthrough questions |
| FINAL-01 | Technical requirements verified, documentation aligned, honest limitations, no generated dependencies/secrets in submission | Pending | Final requirement audit |
| FINAL-02 | Student commits/pushes, verifies remote and actual submission; instructor review | Pending | Git history/remote evidence and student confirmation |

Backend workflow, scans and bounded controlled delivery/recovery are verified to the linked scope. Week 13 remains incomplete: full Flutter/browser workflow, activities/SA7, final documentation, student reflection/review and commit/submission steps remain pending. Flutter widget tests remain blocked. Later-week work has not started.
