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
