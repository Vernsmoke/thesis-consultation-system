# Week 13 demonstration and maintenance

This is an operator guide for the controlled classroom prototype, not a production deployment/UAT record. The instructor accepts the assessment; the fictional coordinator account does not provide actual stakeholder approval. The student must use their own private login and explain the work themselves.

## Eight minute demonstration

1. Open http://127.0.0.1:8080. Explain Flutter Web, Laravel and scoped XAMPP MariaDB, then sign in as an existing fictional student. Never show `.env`, passwords, cookies or private service headers on screen.
2. Show permitted consultations, required-field feedback and denied consultation1 for student.one. For a new request demonstration, use a clearly fictional agenda and retain its returned ID. Creation adds data: do not run it merely to refresh screenshots.
3. Sign in as the assigned adviser. Show recorded future consultation16 at30 October09:00–09:30 Manila and explain01:00–01:30UTC. A new confirmation needs a future interval that does not overlap recorded adviser consultations; it does not establish actual adviser availability. Do not move the computer clock to close a future meeting.
4. Show completed fictional consultation7, action4 and follow-up4. Explain that this different elapsed fixture was used for the outcome/completion segment. Read-only panel access can be shown on linked consultation2.
5. As student, show notification17 and its timeout then receipt reconciliation. As coordinator, show work19's422, recorded recovery and accepted cycle2 receipt. Work15 remains exhausted needs_review; do not reset it or repeatedly click recovery. An obsolete source must be reviewed, not silently rewritten.
6. Show all four kinds: reminder19, inferred-missed7, deadline8 and dated summary22. Summary22 is pending and must be described that way. Explain inference versus attendance proof and first-scan snapshot versus end-of-day totals.
7. Run the isolated logging-fault test or full backend suite in a terminal. Explain the retained before-failure and after-retest. Use the source screenshot or actual saved controller to show separate durable recovery and log status.
8. Explain one limitation and the next authorized verification: unexecuted Flutter automated suite, continuous scheduler, concurrency/real interruption or ledger restore. State the AI assistance and the exact component the student can independently explain/modify. Do not claim a personal reflection or viva has already happened.

```powershell
Set-Location 'C:\Users\Laure\Documents\ChatGPT\IPT\week-13\backend'
php artisan test --filter=recovery_reports_log_outage
php artisan test
php tools/inspect-ui-evidence.php 16 7 19
php tools/summarize-evidence.php
```

The first two commands use the guarded isolated test database. The inspector reads retained app/receiver data. The summarizer reads saved evidence only; it is not a current queue measurement. Current UI may change naturally after new authorized commands; keep later evidence separate rather than overwriting earlier results.

## Dependency unavailable fallback

If a local service is unavailable during review, acknowledge the failed live demonstration. Use evidence/README.md, FLUTTER-RESULTS.md and DELIVERY-RESULTS.md as the navigation path. Open the actual saved screenshot at full resolution, then its paired JSON/log/audit record and source file. For example, work17's retained first timeout and matching receiver receipt are execution evidence, while the saved source screenshot alone is not. Explain the build/date and limitation of each record. Do not present a saved image as the current running state.

Do not repair Windows policy, change credentials, delete databases/ledgers, disable security settings or install tools during a defense without appropriate authorization. A blocked automated Flutter suite remains blocked even when the release build and19 selected browser checks passed. The new log warning branch is verified at the API with an injected logger fault; a real browser/filesystem logging-outage demonstration is not claimed.

## Current service commands

Inspect actual ports and process commands before starting anything. Existing secrets and schemas are already configured in this workspace; no provisioning/reset is required. Commands below assume a missing service, not permission to create duplicate listeners.

```powershell
# API terminal
Set-Location 'C:\Users\Laure\Documents\ChatGPT\IPT\week-13\backend\public'
php -S 127.0.0.1:8000 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php

# Flutter static build terminal
Set-Location 'C:\Users\Laure\Documents\ChatGPT\IPT\week-13\frontend'
php -S 127.0.0.1:8080 -t build/web

# Controlled receiver terminal
Set-Location 'C:\Users\Laure\Documents\ChatGPT\IPT\week-13\notification-service'
php -S 127.0.0.1:8090 -t public public/index.php
```

MariaDB uses the existing XAMPP configuration and loopback3306. The API router requires backend/public as its working directory. Preserve the private receiver ledger. The temporary ui-failure-router is a controlled read-only stand-in, never routine startup. No persistent scheduler was left running.

## Automation operations

The scan and worker are registered each minute, but require an actual operator/scheduler process. A manual scan can now infer missed for additional old fixtures whose ends have elapsed. Review the DB state and intend those changes before invoking it. `automation:work --id=ID` processes a specific existing record; it is not a source of fake receipt evidence. Batch processing honors due times and current-source checks. The receiver accepts minimal data only and does not send email.

Do not rerun the fixture-generating verify scripts, recovery preparation, provisioning, migrate:fresh, old app seeders, rollback or key initializer to produce a cleaner demonstration. Keep failed work/attempts and prior snapshots. DELIVERY.md describes diagnosis and bounded recovery; the authoritative filename is docs/DELIVERY.md.

## Maintenance responsibilities

| Trigger | Owner and action | Evidence to retain |
| --- | --- | --- |
| Before an intended demonstration | Student operator inspects services, app/test connection isolation, due work and private configuration | Safe process metadata and named build/test result |
| Permanent/exhausted work | Coordinator reviews source, receipt uncertainty and failure history; maintainer corrects dependency; coordinator records bounded recovery reason | Work/correlation, attempt outcomes and actual recovery actor/cycle |
| Recovery log_status unavailable | Maintainer checks protected log path/storage while coordinator uses durable DB audit; do not resubmit recovery just to change telemetry | Safe error status and retained recovery row; no keys/reason text in routine logs |
| Source/configuration change | Student or authorized maintainer reviews small diff and runs relevant isolated cases/regression; rebuild Flutter when client changes | Failed result, fix, retest, source fingerprint and updated contract |
| Dependency update or credential rotation | Deliberate authorized change, review installed-version guidance and lockfiles; coordinate both private service settings | Version/diff and safe verification, never credential values |
| Before broader deployment | Authorized maintainer resolves transport, privilege separation, scheduler ownership, queue-age monitoring, missed correction and protected ledger restore | New measured evidence; no present readiness claim |

The report's indicators are a starting point for maintenance, not a health dashboard. In future controlled operation, monitor pending/retry/review counts, oldest eligible age, attempt failure rate and log availability together. A low queue count does not prove timely acceptance or user reading. Sustained outages may justify a dependency-level circuit breaker; current per-item budgets are not one.

## Student walkthrough questions

- Which writes derive actor/assignee from the session rather than client fields?
- Why must a clock-driven scan exist even if a request produces an immediate API response?
- Trace one unique work identity through a lost response and the matching receipt.
- Explain the30-second lease, late-result check and three-attempt/three-cycle limits.
- Why does a logging outage after commit return a successful recovery plus an outage flag?
- What do19 browser observations prove that a build alone cannot, and what do they still leave untested?
- Which file can the student explain and change independently? Demonstrate that contribution instead of inventing it in a report.

The student's personal answers belong in the separate local reflection document, outside GitHub.
