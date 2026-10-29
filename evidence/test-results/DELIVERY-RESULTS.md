# Checkpoint 06 execution results

2026-10-29 Asia/Manila. Base commit **0c8a2744a8aaa55e2c6737b3ebb56bee872f5578**, uncommitted delivery increment. Source fingerprints: `delivery-build.json`. PHP 8.2.12, Laravel 12.70.2, XAMPP MariaDB 10.4.32; loopback API 8000/receiver 8090. Backend suite uses guarded separate test database; live exercise uses only retained fictional app records. No app reset, deletion, clock change or archived artifacts.

## Repeatable suite

- `delivery-tests-initial.txt`: 60 pass/2 fail; replacing HTTP fakes mid-test retained earlier handlers. Fixed with one sequence/mutable closure.
- `delivery-tests-retest.txt`: 62 pass/1 fail; new all-four-kinds case lacked Consultation import. Import corrected.
- `delivery-tests-final.txt` and `delivery-tests-final-direct.txt`: runner bootstrap blocked before execution in restricted process sandbox. The Composer file existed and loaded; PHP is_readable returned false. No permissions or security settings changed. Authorized runner passed in `delivery-tests-final-authorized.txt`.
- Final formatted source `delivery-tests-final-source.txt`: **63 pass / 314 assertions**, 6.40 seconds. `delivery-style.txt`: Pint passed. Thirteen delivery tests include eligibility/stale cancellation, all four kinds, timeout, bounded retries, active/expired claims, fencing late results, recipient authorization, recovery reasons/history/cycle limit, endpoint/batch restrictions and delivered-notification scoping. No frontend code/test pass is implied.

## Real service cases

Run **2c6a2e49a53f**, UTC 13:08:19–13:11:28 (21:08:19–21:11:28 Manila). `tools/verify-delivery.php` explicitly initialized seven future confirmed fictional meetings, consultation IDs 9–15, work IDs 12–18. These are failure fixtures, not adviser-confirmed human meetings. Initial expected decisions were success=delivered, temporary failures=retry_wait, permanent=needs_review, active claim=no call. Final expected/actual:

| Case / requirement | Actual result | Evidence |
| --- | --- | --- |
| D01 normal acceptance | Work 12 delivered, one attempt/receipt | delivery-live-init.json |
| D02 temporary 503 | Work 13 retry_wait then delivered on attempt 2 | delivery-live-init.json, delivery-live-worker-batch.json, delivery-live-retry-due.json |
| D03 permanent 422 and human action | Work 14 immediately needs_review; actual coordinator API review/reason queues cycle 2; delivered, total 2 attempts, old failure retained | delivery-live-recovery.json |
| D04 unavailable service / exhaustion | Work 15 503 on three attempts, needs_review, zero receipts; repeat makes no fourth attempt | delivery-live-final-attempt.json, delivery-live-verified.json |
| D05 acknowledgment loss | Work 16 receiver accepted then returned 503; retry returned same receipt with HTTP 200; one ledger row | init + verified JSON |
| D06 real timeout | Work 17 accepted then receiver slept 6s; worker timed out at 5019ms; retry HTTP 200 reconciled original receipt, one row | init + verified JSON |
| D07 interruption boundary | Work 18 manually initialized claimed/started, receiver accepted, result deliberately not saved. Active lease skipped; real expiry journals interrupted then attempt 2 reconciles same receipt | init, retry, worker-batch, verified JSON |
| D08 unauthorized recovery | Signed-in student POST ->403 | delivery-live-recovery.json |
| D09 invalid recovery | Coordinator short reason ->422, delivered replay ->409 | delivery-live-recovery.json |
| D10 minimal service/security/idempotency | Unauthenticated ->401; invalid body ->422; same identity/changed recipient ->409; exact repeat ->200 duplicate=true/original receipt | delivery-live-verified.json |
| D11 all four kinds | Existing scan work 1–11 accepted: 4 reminders, 3 inferred missed notices, 2 deadlines, 2 daily snapshots | delivery-live-worker-batch.json + all_work_states in verified JSON |

Every final expected result above passed. Recovery API trace includes **12** expected HTTP statuses (CSRF/login, review, denied/invalid/valid recovery, notification read, logout); no cookies, auth headers or passwords saved. AI-operated fictional coordinator action is a controlled permission test, not stakeholder approval.

The early `delivery-live-retry.json` at 13:08:41 and `delivery-live-before-final.json` at 13:09:59 show future due times correctly skipping work. They are not failed retries. Actual second unavailable attempt occurred 13:09:10, third 13:10:34; earliest backoff is not a promise of immediate execution.

Retained final-probe failures: `delivery-live-verification-initial.json` and `delivery-live-verification-diagnostic.json`. The verification script held a SQLite SELECT cursor while calling the receiver, blocking its write transaction and causing safe 503 receiver_storage_unavailable. Closing each cursor and releasing the verifier connection corrected the harness. `delivery-live-verified.json`: `acceptance=pass`. No existing receipt or application record was cleared to fix it.

## Final snapshot and evidence boundaries

**6 users, 15 consultations, 18 work rows, 25 attempt entries, 1 recorded coordinator recovery. 17 delivered / 1 needs_review; zero pending.** Delivered kinds: 10 reminders, 3 missed notices, 2 deadlines, 2 summaries. Work 15 remains intentionally unresolved after exhaustion. All six accepted new fixture identities have exactly one receiver ledger row; unavailable has none. Twenty-five attempts include one simulated interrupted attempt. Receipt acceptance is the bounded notification output; read acknowledgment, external messaging, actual attendance and human acceptance are not proven.

`delivery-routes.txt` / `delivery-schedule.txt` show registered routes and both minute schedules, not continuous-run reliability. `../logs/automation-delivery.jsonl` contains safe structured local/testing records. `../screenshots/source-code/w13-delivery-retry-vscode.jpg` is the actual saved editor view with file/line numbers. It proves implementation; the JSON/journal/test results prove execution. No new application screenshot is claimed until Flutter workflow UI exists.

Unverified: real competing-worker processes, actual killed worker/reboot, continuous scheduler endurance, backup/restore, real messaging/read receipt, production HTTPS. Full Flutter, activities/SA7/lab write-up, maintainability evaluation, student reflection/AI acknowledgment/defense and submission remain pending. Source type refinement also changes scanner enqueue's date parameter to CarbonInterface, matching the actual existing argument; regression passes.
