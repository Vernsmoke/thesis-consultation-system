# Week 13 requirements and evidence checklist

Project 29: Thesis Consultation Reminder System. Updated 2026-10-29 (Asia/Manila).

Sources: original module, complete Module 13 including Flutter foundation, five topic checkpoints, guided procedure, activities, assessment and reflection; project guide, common rules, assessment mapping and Project 29. The source DOCX files remain in the parent workspace. Filenames and milestone boundaries below are organization choices, not teacher requirements.

Status: **prepared** means a reviewable plan exists, **pending** means the implementation or evidence does not yet exist, **complete** means the specified output was actually verified. A design does not prove execution.

| ID | Requirement and origin | Status | Artifact / planned proof |
| --- | --- | --- | --- |
| GOV-01 | One individual approved/registered project; no production data (guide) | Student reports assigned and approved; official registry not inspected | [Scope and authority](docs/DESIGN.md#scope-and-authority); student must retain instructor confirmation |
| GOV-02 | Fresh Week 13 history, separate folder, student commits/pushes (student) | Independent repository initialized; first student commit/push pending | Branch codex/week13-foundation, new supplied origin; no archived source/evidence used |
| PREP-01 | Feature branch, evidence folders, lab log with date, environment, versions, scope (guided part 1) | Complete for foundation | [Lab log](docs/LAB-LOG.md) |
| FND-01 | Fresh scaffold/API connection (implementation milestone, not a separate teacher requirement) | Verified; widget tests blocked | [Results](evidence/test-results/FOUNDATION-RESULTS.md); five backend tests, analysis, web build, real browser connection |
| DES-01 | Actors, trigger, trusted inputs, boundary, outputs, dependencies, human approval, non-goals (topic 1) | Prepared | [Design](docs/DESIGN.md) |
| DES-02 | Data-flow/sequence diagram marks trust, credentials, sensitive data, logs and failure/approval paths | Prepared | [Design diagram](docs/DESIGN.md#data-flow-and-trust-boundaries) |
| DES-03 | At least two approaches with fit, security, reliability, performance, effort, skills, ownership and verification tradeoffs | Prepared; execution evidence pending | [Decision record](docs/DESIGN.md#decision-record) |
| DES-04 | Pattern matrix and trigger contract; latency, clock/timezone, duplicate, ordering and back-pressure concerns (topic 2) | Prepared | [Trigger contract](docs/DESIGN.md#automation-trigger-contract) |
| DES-05 | Failure taxonomy, retry policy and recovery runbook (topic 3) | Prepared policy; runbook pending implementation | [Retry design](docs/DESIGN.md#failure-and-recovery-policy) |
| DES-06 | Security checklist and protected configuration evidence (topic 4) | Pending | Configuration example, ignore checks, validation/authorization tests, safe logs |
| DES-07 | Reliability and maintainability report supported by measurements (topic 5) | Pending | Weekly report, counts/timings, error/retry rates, queue depth, limitations |
| WF-01 | Consultation request with agenda; assigned adviser confirms schedule (Project 29) | Pending | API tests, Flutter demonstration, state audit |
| WF-02 | Meeting status, action items with deadlines and follow-up (Project 29) | Pending | State-transition, ownership and deadline tests |
| AUTO-01 | Consultation reminders | Pending | Controlled due record, service receipt, structured outcome |
| AUTO-02 | Missed-session handling | Pending | Controlled elapsed session, no duplicate transition, coordinator alert |
| AUTO-03 | Action-item deadline automation | Pending | Due/open vs completed items, notification and duplicate checks |
| AUTO-04 | Progress summaries | Pending | Daily summary with controlled counts, recipient authorization |
| AUTO-05 | Validate queued records, call bounded service, audit result, notify responsible role (guided build) | Pending | Durable work records, service test requests/receipts, logs |
| REL-01 | Bounded retries, timeout, transient recovery, permanent failures routed for human action | Pending | Actual recovered retry, exhausted retry, permanent failure, review/recovery evidence |
| REL-02 | Idempotency/duplicate protection, crash recovery; no endless retries or silent loss | Pending | Duplicate/concurrency and interruption/recovery tests; Activity 2 |
| SEC-01 | Server-side authentication, role/record authorization, safe consistent errors and input validation (common rules/module) | Pending | Unauthenticated 401, forbidden 403, invalid 422 and ownership tests |
| SEC-02 | Secrets outside source/client, least privilege, controlled data, safe logs and correlation IDs | Pending | Fresh local settings, sanitized example, log review, dependency checks |
| WEB-01 | Flutter Web source with minimum workflow navigation and reusable widgets | Pending | Source and real browser/API demonstration |
| WEB-02 | Client validation agrees with authoritative server validation | Pending | Invalid form and API results |
| WEB-03 | Loading, success, empty, validation, authentication, authorization, network and server failure states | Pending | Real browser captures plus repeatable checks |
| WEB-04 | Desktop, tablet and narrow layout; keyboard, labels, focus, contrast/status feedback | Pending | Actual viewport captures and usability record |
| TEST-01 | Measurable success and at least four adverse cases: invalid, unauthorized, dependency failure, recovery | Prepared criteria; tests pending | [Acceptance plan](docs/DESIGN.md#acceptance-and-evidence-plan), API scripts/requests/results |
| TEST-02 | Expected/actual, build ID, environment, date/time, data, preconditions, evidence paths | Pending execution | [Evidence index](evidence/README.md), dated result records |
| TEST-03 | Predict controlled failure; preserve failed run; justified improvement, retest and regression | Pending | Activity 2 and retained original/retest records |
| ACT-01 | Three contrasting office workflows, compare at least two patterns for each against latency/volume/reliability/security/staffing | Pending | Main weekly report Activity 1, matrix, diagram and reproducible proof |
| ACT-02 | Controlled failure injection proves no duplicate processing, endless retries or silent data loss | Pending | Main weekly report Activity 2, source/config, metrics, logs, reruns |
| ACT-03 | Each activity: assumptions/constraints/dependencies/acceptance role, adverse case, limitations, next improvement and one-page interpretation separating fact/conclusion/recommendation | Pending | Two clearly labeled one-page interpretations in main report |
| SA7-01 | SA7 case plus lab portfolio: source, example config, diagram, logs, failure evidence, recovery notes, maintainability | Pending | Main report and linked supporting artifacts |
| SA7-02 | Concept explanation/control selection (15%); guided Flutter/API build (35%); adverse test (25%); report/viva (25%) | Pending | Assessment mapping in report; student oral review remains human work |
| DOC-01 | README setup/run/retest, manifests/lockfiles, migrations/fictional seeders, issue record, limitations, recovery and maintenance match build | Pending | README, CHANGELOG, docs and source |
| EVD-01 | Actual saved-source VS Code screenshots and actual running Flutter browser screenshots; no secrets | Foundation captures complete; required workflow/failure captures pending | [Index](evidence/README.md); connected screen, narrow layout and actual VS Code health route |
| EVD-02 | Safe API requests/responses, logs, timings, descriptive filenames, evidence index | Pending | evidence/api-tests, logs and test-results |
| DEMO-01 | Short controlled demonstration and backup path if dependency unavailable | Pending | Demo script and preserved execution evidence |
| REF-01 | Four reflection prompts: influential decision/evidence, hard failure, independent contribution, next verification/out-of-scope work | Pending student input | Main report; never fabricate personal understanding |
| INT-01 | Acknowledge AI, third-party code/libraries/sources; student can reproduce, explain and defend | Pending student review | Report references/AI acknowledgment and walkthrough questions |
| FINAL-01 | Technical requirements verified, documentation aligned, honest limitations, no generated dependencies/secrets in submission | Pending | Final requirement audit |
| FINAL-02 | Student commits/pushes, verifies remote and actual submission; instructor review | Pending | Git history/remote evidence and student confirmation |

Only the foundation results explicitly linked above have been verified. No consultation workflow, automation, assessment, defense or submission is complete. Flutter widget tests remain blocked. Week 14 encryption/HTTPS, Week 15 hardening, Week 16 deployment evaluation, Week 17 UAT and Week 18 examination remain later-week work.
