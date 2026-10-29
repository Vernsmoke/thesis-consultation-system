# Changelog

## Uncommitted bounded delivery/recovery — 2026-10-29

- Verified clean student scan commit 0c8a274 locally/live remote before implementation.
- Added protected loopback PHP receiver, durable idempotent receipts, controlled failure modes, bounded Laravel worker, current-source validation, leased/fenced claims and persistent attempt journal.
- Added finite retries/backoff, coordinator review/recovery API, history and three-cycle limit; scoped recipient notifications. Scanner date parameter now accepts CarbonInterface, resolving the observed editor warning.
- Final 63 tests/314 assertions and Pint pass. Actual four-kind acceptance, transient/timeout/lost-ack/interruption-boundary reconciliation, three-attempt exhaustion and coordinator recovery recorded; failures and fixes preserved.
- Current 17 receiver-accepted/1 unresolved needs_review; actual VS Code retry capture, documentation/evidence and handover updated. Full Flutter/activities/SA7/student verification remain pending.

Earlier sections retain their checkpoint-time descriptions; the scan increment below is now committed as 0c8a274 by the student.

## Uncommitted automation scan foundation — 2026-10-29

- Verified student workflow commit 4a51934 locally/on remote before edits.
- Added durable unique work records, minute-scheduled scans for reminders/elapsed meetings/overdue actions/Manila daily summaries, coordinator review ownership and bounded-capacity failures.
- Added explicitly automated missed audit with null human actor and human-review basis; state/work/audit share transaction. Invalid sources route to needs_review without wrong-recipient work.
- Added safe JSON logs/correlation and honest distinction between pre-commit storage failure and post-commit logging failure. Reserved delivery fields are not an implemented worker.
- Final suite 50 tests/242 assertions passes; live scan created 11 pending rows, repeat/scheduled invocation created zero duplicates. All attempts zero/no receipts, zero delivered notifications.
- Saved actual scanner source in VS Code and updated evidence/handover. Delivery/retry/recovery, full Flutter UI and assessments pending.

## Uncommitted remaining workflow API — 2026-10-29

- Verified student API commit d2fe529 locally/on remote and clean starting tree.
- Added adviser meeting outcomes after the end, adviser action assignment, owning-student idempotent completion and participant follow-up notes with explicit state/field/record checks.
- Added follow-ups/action audit linkage and a small shared audit writer; domain changes roll back when audit writing fails. Earlier rows/events retained.
- First regression: 40 tests/197 assertions passed; actual HTTP exercise: 36 expected results, two disclosed fictional starting fixtures (5/6), seven new events, no duplicate completion event.
- Captured actual saved completion control in VS Code; updated API contract, evidence/checklist and handover. No frontend change, automation or real consultation/notification claim.

## Uncommitted API milestone — 2026-10-29

- Verified student data commit 27960ba locally/on remote before changes.
- Added Sanctum 4.3.3 cookie-session login/logout, CSRF integration, login throttling, policies/scoped reads, student request creation and assigned-adviser schedule confirmation.
- Added minimal transactional consultation events/correlation IDs, UTC validation, recorded-adviser overlap checks, repeated-confirmation rejection and safe database-error responses/log metadata.
- Final MariaDB suite: 29 tests/111 assertions; actual HTTP session exercise: 24 expected responses, retained consultation 4/two audit events. Captured actual saved policy in VS Code.
- Retained initial test failures and HTTP configuration/router failures, then corrected/retested. Single API server now runs from backend/public; no database/security/SDK repair.
- Remaining workflow mutations, Flutter login/screens, four automations, full evidence and activities/SA7 pending.

## Uncommitted data foundation — 2026-10-29

- Verified the student's foundation commit d43b0df locally and on GitHub before changes.
- Provisioned fresh database-scoped app/test accounts and schemas on loopback XAMPP MariaDB; generated secrets only in ignored files; no archived data/settings used.
- Added user roles, consultations, panel links, action items and relationships, indexed schedule/deadline fields and checked database invariants.
- Replaced generic seeder with repeatable fictional Project 29 fixtures; hashed demo passwords and preserved existing edits on repeat seeding.
- Added isolated MariaDB tests with a pre-reset connection guard. Final suite: 14 pass/35 assertions; initial assertion-method error and inspector import failure retained with corrected results.
- Verified denied cross-database read and unchanged live seed snapshots; captured actual migration constraints in VS Code.
- Authentication/workflow APIs and all four automations remain pending; frontend unchanged.

## Uncommitted foundation — 2026-10-29

- Prepared a source-mapped Week 13 checklist and bounded Project 29 design before application implementation.
- Recorded tool/service inspection and the student's reported approval, remote and deadline.
- Created independent codex/week13-foundation repository with the student's new origin; no commits/pushes performed.
- Created fresh Laravel 12.70.2 and Flutter 3.47.8 web-only scaffolds with dependency lockfiles.
- Added public project-liveness endpoint, exact local CORS origin, private configuration example and Flutter connection feedback.
- Verified five backend tests (eight assertions), Laravel formatter, Flutter analysis, web compilation and actual browser/API connection; saved real VS Code and desktop/narrow browser captures.
- Preserved failed CORS test interpretation and corrected retest. Flutter widget tests blocked by Windows Application Control; no security/SDK repair applied.
- Consultation data, MySQL setup, role authorization, all four automations, activities and SA7 remain pending.
