# Activity 2 controlled failure and improvement record

Date30 October2026 Asia/Manila. Base1bf5323b5e13d25b73c57e911310e7799a73bbc7, uncommitted checkpoint08. Final source hashes identify the exact later build. No app record, receiver ledger or OS logging permission was altered by this test.

## Prediction before the change

An injected logging-facade exception occurs only after the coordinator recovery transaction commits. Expected safe state: HTTP200, pending cycle2, unchanged work correlation, attempts0, one durable coordinator recovery row and no service attempt. Expected missing behavior in baseline: the response has no log_status=unavailable, so the final feedback assertion should fail. The test uses the guarded isolated MariaDB testDB, frozen fictional time and no real HTTP calls. It does not require permissions to a real filesystem log path to be changed.

## Observed baseline and change

`activity2-log-outage-before.txt` first exposed the missing field. The refined baseline `activity2-log-outage-before-audit-verified.txt` checks persistence before the final assertion: one test fails after10 assertions, specifically null versus unavailable for log_status. This is a real response-observability gap, not evidence that the transaction lost data.

AutomationController now returns an additive log_status field. Normal success is recorded; a caught operational log exception is unavailable. Recovery still returns200 because it already committed. Flutter's save-result handler preserves the queued result and warns to inspect logging without repeating recovery. No migration, new package, automatic retry or configuration secret was added. The rejected alternative was to throw a generic failure after commit, which could mislead the coordinator about durable state.

## Retest and regression

| Verification | Actual result | Artifact |
| --- | --- | --- |
| Same controlled logger fault after fix |1 pass,10 assertions | activity2-log-outage-after.txt |
| Full isolated backend suite |64 pass,325 assertions,6.66s | final-backend-regression.txt |
| Flutter analysis | No issues,3.8s | final-flutter-analyze.txt |
| Updated release build | Success,36.1s | final-flutter-build.txt |
| Normal recovery response | Existing coordinator recovery test also asserts recorded | AutomationDeliveryTest.php, full regression |

The19 browser scenarios and7 persisted-effect checks are historical checkpoint07 evidence. They were not rerun wholesale for this small change. The new browser log-warning branch under an actual filesystem outage is unverified; its API status branch is tested with an injected logger fault and client code compiles. Automated Flutter execution remains blocked/stalled, zero executed. Do not relabel either gap as a pass.

## Repeatable isolated command

```powershell
Set-Location 'C:\Users\Laure\Documents\ChatGPT\IPT\week-13\backend'
php artisan test --filter=recovery_reports_log_outage
php artisan test
```

The [main report](WEEK-13-REPORT.md) includes the controlled receiver fault matrix, both feasible approaches, metrics, acceptance role, residual risks and the separate one-page Activity2 interpretation in the local Word report. Safe examples/logs and earlier normal/transient/permanent cases remain in evidence. This records work by AI-assisted tooling, not the student's independent defense or human acceptance.
