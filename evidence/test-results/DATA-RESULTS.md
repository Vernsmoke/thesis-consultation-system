# Checkpoint 02 — data verification

Build: uncommitted data milestone based on **d43b0df57e49438739decf6205e2a51f1e5cfd79**. [Source fingerprints](data-build.json). Executed 2026-10-29 Asia/Manila; exact UTC file times/build metadata recorded in the fingerprint file and live snapshots. Environment: Windows, PHP 8.2.12, Laravel 12.70.2, MariaDB 10.4.32 at loopback 3306. No Flutter source change or widget-test rerun; the prior Windows Application Control blocker remains open.

Preconditions: first foundation commit verified locally and on GitHub; clean tree before milestone. New database/user names checked absent; fresh app/test credentials generated into ignored local files. Five migrations applied to the new app schema and six fictional users/three consultations/one action item seeded. Tests operate only on `thesis_consultation_week13_test` with its separate scoped account and a frozen clock of 2026-10-29 00:00 UTC.

| Case | Controlled check and expected result | Actual result / evidence |
| --- | --- | --- |
| DATA-SETUP | Apply fresh default + role + consultation migrations | Five succeeded; [migration output](data-migration.txt); [seed output](data-seed.txt) |
| DATA-REL | Models resolve correct seeded student/adviser/panel and action relationships; UTC casts | Passed; [test retest](data-tests-retest.txt) |
| DATA-RESEED | Repeat seed preserves counts, edited agenda, completion and password hash | Passed in isolated test; live app snapshots also match (except observation timestamp) [before](data-live-before-reseed.json), [after](data-live-after-reseed.json) |
| DATA-CRED | Password hash verifies, serialized model omits password, role not mass-assignable | Passed; no generated local secret printed |
| DATA-FK | Nonexistent action assignee rejected | Expected database exception occurred; test passed |
| DATA-SCHEDULE-1 | Confirmed state without schedule rejected | Expected CHECK exception occurred; test passed |
| DATA-SCHEDULE-2 | End before start rejected | Expected CHECK exception occurred; test passed |
| DATA-PANEL | Repeated panel link rejected | Expected duplicate-key exception occurred; test passed |
| DATA-DELETE | Deleting a referenced student rejected | Expected foreign-key exception occurred inside disposable test transaction; no app user deleted |
| DATA-ROLE | Invalid role administrator rejected | Expected enum/strict-mode exception occurred; test passed |
| DATA-ISOLATION | App user cannot read the test database | SQLSTATE 42000/error 1142, no data returned; recorded in both live snapshots |
| DATA-PROVISION | Repeated provisioning refuses existing local configuration without resetting it | See [refusal output](data-provision-refusal.txt); expected exit 1, private-file hashes unchanged |
| DATA-STYLE | Laravel formatter/check succeeds; no whitespace errors | Pint passed; git diff --check passed |
| DATA-SRC | Actual saved constraints visible in VS Code, filename/line numbers | [Real editor capture](../screenshots/source-code/w13-data-schedule-constraints-vscode.jpg); source proof only |

Initial test result: **13 passed, 1 failed (34 assertions)** because the test used nonexistent `assertSameCanonicalizing()`. Changed it to PHPUnit's `assertEqualsCanonicalizing()`. [Initial result](data-tests-initial.txt) is retained. Final run: **14 passed (35 assertions), 1.25 s**, including nine new data tests and five foundation/scaffold checks.

The first read-only inspection script also failed because a formatter-shortened `Kernel::class` reference preceded its import. Moved imports before executable bootstrap code and reran it successfully. [Initial failure output](data-inspector-initial-failure.txt) retained. Live snapshots are actual sanitized app observations, not reconstructed values.

The live data has **6 users, 3 consultations, 1 panel link, 1 action item**. Two students, two advisers, one panel member and one coordinator are fictional fixtures. App snapshot stability after the test run and repeat seeding supports separation; schema reset happens only in the test database. The successful constraints tests do not prove access control or workflow completion.

Limitations: no login, policies, request/confirmation/status/action endpoints, audit-event log, reminders, missed-session command, deadline delivery, summary generation or retry/recovery service has run. Database adverse cases are **not substitutes for the module's required API/browser adverse paths**. Seeded meeting statuses are fixtures, not real meetings or execution evidence. The existing XAMPP replication-state startup warning was not repaired; no broader installation/production-readiness claim.
