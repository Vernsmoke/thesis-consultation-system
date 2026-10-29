# Evidence index

Only evidence generated from the fresh Project 29 build belongs here. Never copy archived evidence or private settings.

| Location | Purpose | Current status |
| --- | --- | --- |
| test-results/ | Foundation result record, actual backend retest output and source fingerprints | [Foundation results](test-results/FOUNDATION-RESULTS.md); widget tests blocked; workflow results pending |
| api-tests/ | Sanitized requests/responses and reproduction commands | [FND-01 actual health response](api-tests/foundation-health-response.json); full API tests pending |
| logs/ | Safe structured automation logs and service receipts | Pending |
| screenshots/source-code/ | Actual saved files in VS Code, filename and line numbers visible | [FND-SRC health route](screenshots/source-code/w13-foundation-health-vscode.jpg); automation source pending |
| screenshots/application/ | Actual running Flutter workflow and error/responsive states | [FND-UI connected](screenshots/application/w13-foundation-api-connected.jpg), [FND-UI-N 390x844](screenshots/application/w13-foundation-narrow-390.jpg); full workflow/adverse states pending |

Record requirement/case ID, build/commit (or explicit uncommitted build/hash), environment, timestamp, fictional data and preconditions, expected outcome, actual outcome, pass/fail and artifact path for each test. Keep failed runs and subsequent retests. Source screenshots prove implementation only; pair retry/recovery claims with execution logs/tests and browser outcomes.

## Checkpoint 02 data evidence

- [Data result map](test-results/DATA-RESULTS.md): expected/actual cases, original failures, retests and limits.
- [14-test terminal output](test-results/data-tests-retest.txt), [initial assertion failure](test-results/data-tests-initial.txt), [initial inspector failure](test-results/data-inspector-initial-failure.txt).
- [Actual migration output](test-results/data-migration.txt), [migration status](test-results/data-migration-status.txt), [seeding](test-results/data-seed.txt), [safe repeat-provision refusal](test-results/data-provision-refusal.txt).
- [Live before-repeat snapshot](test-results/data-live-before-reseed.json), [after-repeat snapshot](test-results/data-live-after-reseed.json): stable fixture metadata/counts and denied cross-database access.
- [Base commit/source fingerprints and file timestamps](test-results/data-build.json).
- [Actual saved constraints in VS Code](screenshots/source-code/w13-data-schedule-constraints-vscode.jpg): implementation proof; database tests above provide execution proof.

No new browser-workflow capture is claimed for a schema-only increment. The existing Flutter foundation screen remains unchanged. Data tests do not replace required authenticated API/browser adverse cases or automation failure/recovery evidence.
