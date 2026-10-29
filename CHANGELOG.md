# Changelog

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
