# Week 13 automation — scan checkpoint

The scan was implemented at checkpoint 05 (now student commit **0c8a274**). This document preserves that milestone's scan-specific results. Checkpoint 06 implements the [bounded delivery/recovery contract](DELIVERY.md), with [actual delivery evidence](../evidence/test-results/DELIVERY-RESULTS.md). Statements below about a future worker and 11 pending rows describe the historical scan checkpoint; the current snapshot is 17 receiver-accepted / 1 needs_review. Pending rows still never prove delivery.

## Trigger and ownership

```powershell
Set-Location 'C:\Users\Laure\Documents\ChatGPT\IPT\week-13\backend'
php artisan consultations:scan
php artisan schedule:list
```

The command is registered every minute with a two-minute scheduler overlap lock. A coordinator row lock also serializes scanner transactions. Actual `schedule:run` execution was verified once; no continuous scheduler or operating-system scheduled task was installed. The current API/static Flutter/database services are unchanged. Do not start a continuous scan merely to regenerate evidence; the next delivery checkpoint must first implement and test safe processing of pending work.

The first coordinator by ID is the prototype's named review owner. This is an implementation choice, not university staffing policy. Without a coordinator, the command exits nonzero with `missing_review_owner` and commits no scan changes. Student/assigned adviser are ordinary recipients; elapsed-session and daily summary work also address the review coordinator. Panel members receive no automatic notices in this bounded design; they retain authorized read access to consultation progress.

| Kind | Eligibility and work identity | Output |
| --- | --- | --- |
| reminder | Confirmed, start strictly future and within configured 24 hours; consultation + schedule_version + recipient | One pending row per student/adviser |
| missed_session | Confirmed, end <= current UTC time, valid student/adviser roles; consultation + schedule_version + recipient | Atomic missed status + explicitly automated audit + student/adviser/coordinator work requiring human review |
| action_deadline | Open action due <= now, completed parent, valid owning student; action + deadline_version + recipient | One pending row per student/adviser; action itself not marked completed or otherwise penalized |
| progress_summary | Adviser with consultations; adviser + Asia/Manila date + recipient | Adviser/coordinator daily snapshot of stored state counts and open overdue actions |

Keys are SHA-256 identities stored under a unique database index. Stable work correlation IDs distinguish records; a separate run UUID identifies each invocation. Automatic missed work includes its transition correlation ID linking the null-human-actor audit. Dates are stored UTC; daily identity rolls over at Manila midnight, not UTC midnight. A progress summary is an immutable snapshot at the **first available scan of that local day**, not a verified end-of-day report. Later same-day changes do not rewrite it.

## Boundaries and safe failure

`AUTOMATION_SCAN_LIMIT=100` limits candidates per category (validated 1–1000); the command looks ahead one row. More eligible candidates causes `scan_capacity_exceeded` and rolls back the whole scan rather than silently starving rows after a fixed page. This is a bounded classroom arrangement, not a scalable batching implementation. A maintainer must inspect workload before deliberately adjusting capacity; no automatic limit increase. Summary aggregates still depend on stored data volume; performance at large scale has not been measured.

`AUTOMATION_REMINDER_HOURS=24` is validated 1–72. Both settings are sanitized examples in `.env.example`; default values work in the existing local settings without overwriting them. Inputs come from the database, not the browser. Scanner rechecks participant roles and action ownership. Invalid source records create a stable `needs_review` item for the coordinator and preserve the original source state. They are not sent to a misidentified student.

All four categories, work inserts and automatic missed audit/state share one DB transaction, with no HTTP call inside it. Storage/capacity failure rolls back earlier category changes; pending records remain durable after a process ends. Structured JSON logs are emitted only after commit. If logging fails after commit, the command explicitly reports committed work plus unavailable logging and returns nonzero; it never falsely claims a rollback. If logging also fails while reporting a rolled-back scan, safe CLI failure still remains visible.

Log channel `automation` uses Monolog JSON formatting at backend/storage/logs/automation.log. Safe fields include run/correlation/work IDs, kind/status/code and environment/database labels. No agenda, note, account email, password, cookie or raw SQL is logged. Evidence copies contain fictional app and test runs; environment labels distinguish them. Logs support the durable records; they are not the only record of intended work.

## Missed-session interpretation

An elapsed schedule without a recorded outcome is an **automation inference**, not proof that a person failed to attend. The audit uses actor_type=automation and actor_id=null; it does not invent a coordinator action. The payload marks requires_human_review=true and basis=elapsed_without_recorded_outcome. There is no academic penalty, real attendance assertion or actual stakeholder approval.

The current API has no correction/reopen endpoint for an automatically inferred missed outcome. The prototype therefore requires review and has a known false-missed limitation if an outcome was not recorded promptly. Resolve a correction policy with the instructor before broader use; do not silently fabricate completion, edit old evidence, or claim this is production ready. The migration refuses rollback when automated audit history exists rather than relabeling it as human activity. Do not reset the app DB to bypass that guard.

## Durable work and the next checkpoint

automation_items stores source/recipient/review-owner IDs, version, scheduled time, minimal payload, unique identity/correlation, status and timestamps. Pending/needs_review are the only states used by this scanner. Claimed/retry_wait/delivered/cancelled, attempts, claim lease and receipt fields are reserved for the required delivery increment; they are not implemented merely by existing in the schema.

Next worker must revalidate source state, version **and scheduled timestamp**, recipient permissions and payload; cancel obsolete work; claim with a finite lease; call an authenticated bounded local test service with timeout; reconcile receiver idempotency/receipt; record finite attempts/backoff; route permanent/exhausted failures to human review; preserve history during bounded authorized recovery. None of those delivery guarantees is claimed at this checkpoint. No real email/SMS, external endpoint or production record is used.

## Verification and reproducibility

```powershell
Set-Location 'C:\Users\Laure\Documents\ChatGPT\IPT\week-13\backend'
php artisan test
php vendor/bin/pint --test
php tools/inspect-data.php
```

The isolated suite passes **50 tests / 242 assertions**. Actual live scan run **b1239e74abbf** initialized two fictional confirmed meetings (IDs 7/8) and one overdue action (ID 3), then created **11** rows: 4 reminders, 3 missed-session notices, 2 action-deadline notices and 2 summaries. Repeat scan and actual scheduled scan created no duplicates. One automatic missed event was added; all 11 rows have zero attempts/no receipt and remain pending. That is execution evidence for scanning, not delivery.

`tools/verify-scan.php` retains new fixtures and work on each run. Do not rerun it merely for timestamps; routine regression uses the guarded test database. Preserve earlier consultations 4/5/6 and their evidence. [Result map](../evidence/test-results/SCAN-RESULTS.md), [checkpoint commands](CHECKPOINT-05.md), [source capture](../evidence/screenshots/source-code/w13-automation-scan-vscode.jpg).
