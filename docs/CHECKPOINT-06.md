# Checkpoint 06 — bounded delivery and recovery

Ready for student review/commit, based on verified student scan commit **0c8a274**. Assistant has staged, committed and pushed nothing.

Implemented: authenticated local receiver and idempotent receipts, current-source validation, durable claimed/attempt history, finite timeout/retries/backoff, expired-lease reconciliation and late-result fencing, coordinator review/recorded bounded recovery, scoped delivered-notification reads. [Contract/setup/runbook](DELIVERY.md), [actual evidence and limitations](../evidence/test-results/DELIVERY-RESULTS.md).

Verified: **63 tests / 314 assertions**, Pint, actual service acceptance for four kinds, 503 recovery, lost acknowledgment/timeout/one-receipt reconciliation, three-attempt exhaustion, actual authorized/denied recovery. Interruption boundary is simulated, not a killed-process claim. Current **17 receiver-accepted / 1 intentionally needs_review**. Frontend is still the connection screen. No new workflow browser screenshot, real messaging or student understanding is claimed.

## Review and verify

```powershell
Set-Location 'C:\Users\Laure\Documents\ChatGPT\IPT\week-13\backend'
php artisan test
php vendor/bin/pint --test
php artisan schedule:list
```

Do not rerun provisioning, old fixture scripts, receiver configuration initialization, app migrations rollback/reset or `verify-delivery.php recover`. Existing evidence and secrets must be retained. The shared parent progress record describes running services; inspect actual ports before relaunching. The receiver 8090 is already running; no persistent scheduler is running. Read source, result map and private setup instructions before changing a service.

## Student commit and push

```powershell
Set-Location 'C:\Users\Laure\Documents\ChatGPT\IPT\week-13'
git status
git check-ignore backend/.env backend/.env.testing notification-service/.env notification-service/storage/receiver.sqlite
git add -- .gitignore README.md CHANGELOG.md SUBMISSION-CHECKLIST.md backend/.env.example backend/app/Models/AutomationItem.php backend/app/Services/AutomationScanner.php backend/app/Services/AutomationEligibility.php backend/app/Services/AutomationDelivery.php backend/app/Console/Commands/DeliverAutomation.php backend/app/Http/Controllers/AutomationController.php backend/app/Policies/AutomationItemPolicy.php backend/config/consultation.php backend/routes/api.php backend/routes/console.php backend/database/migrations/2026_10_29_000006_create_delivery_history.php backend/tests/Feature/AutomationDeliveryTest.php backend/tools/configure-notifications.php backend/tools/verify-delivery.php notification-service/public/index.php notification-service/.env.example notification-service/storage/.gitignore notification-service/README.md docs evidence
git status
git diff --staged --stat
git diff --staged
git diff --staged --name-only
git commit -m "Add bounded notification delivery and coordinator recovery"
git push origin codex/week13-foundation
git status
git log -6 --oneline
```

Private `.env` files, SQLite ledger, generated dependencies/builds, parent planning and archive must not appear in staged names. The complete commands are intentional so the reviewable source and preserved failed/retest evidence enter the same milestone. No credential values are needed in the commit.

## Explain it to the instructor

“The scan creates work; the worker reserves an attempt, calls a bounded service outside the database transaction, and records the receipt. A stable identity lets retries find the same receipt after a lost response. Three failures stop for coordinator review. Recovery records the actor and reason, keeps history and has a total cycle limit. A delivered flag means this controlled receiver accepted the notification.”

Student walkthrough: trace work 17's timeout and matching receipt; explain why its second HTTP response is 200. Trace work 15's three attempts and explain why a fourth call is blocked. Trace work 14's failed cycle and coordinator recovery row. Explain why active leases are skipped and late results fenced. Distinguish simulated interruption from a real process kill, accepted receipt from read acknowledgment, and a recovery API response from delivery.

After the student handles this checkpoint, verify actual local/remote history and begin the Flutter cookie/CSRF login, consultation workflow and notification/review UI. Then capture real responsive/keyboard/error/recovery browser evidence and complete activities/SA7/lab report/student reflection. Week 13 is not complete or submitted.
