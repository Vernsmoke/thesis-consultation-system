# Checkpoint 05 — durable four-kind automation scans

Ready for student review/commit, based on verified remote commit **4a51934**. Nothing staged, committed or pushed by the assistant.

Implemented: scheduled/manual scan, durable unique work identities, all four eligibility rules, explicit automated missed inference/audit, coordinator review ownership, bounded capacity and safe structured logs. **Not implemented: delivery, bounded retry, receipt reconciliation or manual delivery recovery.**

## Verify

```powershell
Set-Location 'C:\Users\Laure\Documents\ChatGPT\IPT\week-13\backend'
php artisan test
php vendor/bin/pint --test
php artisan schedule:list
php tools/inspect-data.php
```

Expected suite: **50 pass / 242 assertions**. Current app counts: 6 users, 8 consultations, 3 actions, 3 follow-ups, 2 panel links, 10 audit events and **11 pending / 0 delivered automation records**. The actual scan/scheduled-repeat evidence is already saved. Do not recreate live fixtures or start a persistent scheduler before delivery/revalidation is ready.

Read [automation boundaries/limitations](AUTOMATION.md) and [actual results](../evidence/test-results/SCAN-RESULTS.md). No notification service was launched or message sent. The current browser is still the foundation screen; no new workflow UI evidence is claimed.

## Student commit/push

```powershell
Set-Location 'C:\Users\Laure\Documents\ChatGPT\IPT\week-13'
git status
git check-ignore backend/.env backend/.env.testing
git add -- README.md CHANGELOG.md SUBMISSION-CHECKLIST.md backend/app backend/config/consultation.php backend/config/logging.php backend/.env.example backend/database/migrations/2026_10_29_000005_create_automation_items.php backend/routes/console.php backend/tests/Feature/AutomationScanTest.php backend/tools/verify-scan.php backend/tools/inspect-data.php docs evidence
git status
git diff --staged --stat
git diff --staged
git diff --staged --name-only
git commit -m "Add durable scheduled consultation automation scans"
git push origin codex/week13-foundation
git status
git log -5 --oneline
```

Review for private environment/session files and generated dependencies; none belongs in the staged names. Do not stage parent/internal planning or archive.

## Defense explanation

“The scheduled scan finds eligible records and writes durable work, rather than sending messages inside the database transaction. A unique identity prevents repeated scans from duplicating the same work. Automatic missed detection is labeled as an inference requiring human review, with no fabricated human actor. Failure before commit rolls back the scan. Failure of logging after commit is reported differently because the work already exists.”

Student questions: Why is pending work not proof of notification delivery? How do versions and Manila dates affect uniqueness? Why is actor_id null for automated detection? What happens if more than the configured batch is eligible? What can the maintainer verify when logging is unavailable? Which delivery guarantees still need tests?

After the student's commit, verify actual history/status and build the bounded local notification service/worker, finite retries, permanent failure routing and authorized recovery. Full Flutter workflow, activities/SA7, final evidence and student reflection/defense remain unfinished.
