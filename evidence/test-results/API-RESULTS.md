# Checkpoint 03 — authentication and request/schedule evidence

Build: uncommitted API milestone based on **27960ba0bee39bc40b003090df61e9782570f895**, verified locally and on GitHub before edits. [Source fingerprints and evidence timestamps](api-build.json). Execution date 2026-10-29 Asia/Manila. Windows, PHP 8.2.12, Laravel 12.70.2, Sanctum 4.3.3, MariaDB 10.4.32, loopback. No secrets or archived settings reused.

## Actual results

- Two new migrations succeeded: consultation_events and standard Sanctum token table. [Migration output](api-migration.txt).
- Final isolated MariaDB regression: **29 tests passed, 111 assertions, 3.83 seconds**. [Final output](api-tests-final-with-audit.txt). Fifteen new API cases plus fourteen prior checks. Laravel Pint passes after standard import formatting.
- Actual HTTP curl/session exercise: **24 requests matched expected statuses**, including real CSRF validation, retained one fictional consultation (ID 4), exactly two persisted audit rows. [Sanitized live result](../api-tests/api-live-retest-3.json), run ab4832621cd3, 11:45:50–11:45:56 UTC (19:45:50–19:45:56 Manila).
- [Current app snapshot](api-app-snapshot.json) now has six users, **four consultations**, one panel link and one action item. The fourth consultation came from the actual successful HTTP workflow, not reseeding. Its recorded schedule is controlled fictional data, not real availability.
- [Actual routes](../api-tests/api-routes.txt), [safe extracted DB-error log lines](../logs/api-database-errors.txt), [actual saved policy in VS Code](../screenshots/source-code/w13-api-record-policy-vscode.jpg).

| Case | Preconditions / expected | Observed evidence |
| --- | --- | --- |
| AUTH-LOGIN | Seeded fictional account and correct private password; server supplies role; no password/token in response | Feature tests pass; live student/adviser/panel/coordinator login 200 |
| AUTH-401 | No session or invalid password -> 401 | Feature tests; actual anonymous GET /me 401 |
| AUTH-CSRF | Stateful write without X-XSRF-TOKEN -> 419; bootstrap -> 204 | Actual live request sequence, CSRF middleware enabled |
| AUTH-LIMIT | More than six login attempts per minute -> 429 | Isolated feature test (not a load test) |
| AUTH-LOGOUT | Logout invalidates current session; next identity read -> 401 | Both feature test and actual HTTP |
| READ-SCOPE | Student own, adviser assigned, panel explicitly linked, coordinator all; unauthorized detail -> 403; missing -> 404 | Feature tests; actual other-adviser/panel reads forbidden and coordinator read allowed |
| REQUEST-201 | Student request, valid adviser/agenda -> requested/version 0, actor+correlation audit | Feature test and live consultation 4 |
| REQUEST-422 | Missing agenda, nonadviser assignee or spoofed student/status rejected without new rows | Feature tests; live missing agenda 422 |
| SCHEDULE-200 | Assigned adviser, future explicit UTC start/end -> confirmed/version 1 and second audit | Feature test and actual HTTP |
| SCHEDULE-403 | Student, unassigned adviser or coordinator cannot confirm | Feature tests; actual student/coordinator 403 |
| SCHEDULE-422 | Past, reversed or timezone-ambiguous dates rejected | Isolated feature tests |
| SCHEDULE-409 | Overlap with confirmed adviser interval or repeated confirmation rejected without duplicate audit | Feature tests; actual repeated confirmation 409 |
| FAILURE-503 | Injected database failure returns safe JSON, no created consultation | Isolated test using proxied DB manager; no app-service shutdown |
| AUDIT-ROLLBACK | Inject audit insert failure after actual SQL; neither consultation nor audit persists | Isolated MariaDB transaction test |

These are API checks. No new Flutter login/workflow screen or browser permission-state evidence is claimed. The actual HTTP unavailable-service episode below is separate from required future automation transient/permanent failure demonstrations.

## Preserved failures, corrections and recovery

1. [Initial suite](api-tests-initial.txt): 25 passed, 3 failed, 94 assertions. A new consultation model lacked its database-default schedule_version when the audit row was inserted, producing 503. Explicit version 0 fixed the actual application bug. Two test issues also fixed: clearing session password-hash state when switching actors, and proxying the real DB manager rather than constructing an uninitialized partial mock. [First corrected run](api-tests-retest-1.txt): 28 passed/108 assertions; [regression after safe log metadata](api-tests-final.txt): same result.
2. [Initial actual HTTP run](../api-tests/api-live-initial.json) and [first retest](../api-tests/api-live-retest.json): login unexpectedly 503. CLI data connection succeeded; safe server log recorded database authentication code 1045. Original API processes were still present during the first guarded restart attempt; only inspected fresh-project API processes were subsequently stopped. No database/password/security repair or old configuration reuse.
3. [Second live retest](../api-tests/api-live-retest-2.json): direct router launched from backend/ instead of public/, causing a non-JSON invalid response. Corrected server working directory. This is a harness/setup error, not successful API behavior.
4. [Third live retest](../api-tests/api-live-retest-3.json): all 24 expected outcomes succeeded against one direct server from public/. Failed runs remain retained; none created a consultation before stopping. Session cookies and generated demo password are omitted from every record.
5. Added a real database audit-failure rollback test and bounded the adviser directory to 100. Final suite **29/111** passed. Formatter then adjusted test imports only and its check passed. No redundant live rerun/new fixture was needed for these changes.

## Limits and remaining work

Feature-test CSRF is bypassed by Laravel; actual HTTP exercise is the CSRF evidence. It used curl, not the Flutter UI. Overlap and duplicate confirmation tests are sequential; a competing-process race test remains unexecuted. Notifications, automation outbox/retry/deadline/summary work and a complete correlation log are pending. Local HTTP does not prove HTTPS. Existence-disclosing 403/404 distinction and local shared runtime/migration DDL rights remain documented limitations. Widget tests remain blocked by Windows Application Control; no machine repair attempted.
