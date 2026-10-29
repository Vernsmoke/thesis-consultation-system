# Checkpoint 07 — Flutter workflow and browser evidence

Ready for student review/commit, based on verified pushed commit **297f266**. Nothing staged, committed or pushed by assistant.

Implemented: credentialed cookie/CSRF login/restore/logout, role navigation, paginated lists/detail/ID lookup, request/agenda, adviser schedule/outcome/action/follow-up, student action completion, accepted notifications, coordinator review/recovery history/form, Manila date conversion, loading/error/empty/success feedback and guarded form submission. [Source/screen explanation](FLUTTER.md), [actual results and remaining limits](../evidence/test-results/FLUTTER-RESULTS.md).

Verified: final Flutter analysis clean and web build successful; backend63 tests/314 assertions;19 actual browser cases and7 persisted-effect checks; real VS Code source screenshot and actual desktop/tablet/narrow/browser failure/recovery captures. **Automated Flutter tests have no pass result**: browser runner stalled at loading, zero executed; prior VM policy blocker remains. No security repair applied.

Current retained app:16 consultations/4 actions/4 follow-ups/16 audit events/22 automation items; **18 accepted/3 pending/1 needs_review**. Consultation16 is future confirmed; elapsed fixture7 was completed during controlled UI testing; work19 recovered with one receipt. Earlier exhausted15 preserved. Do not reset app or rerun live fixture scripts for timestamps.

## Student verification

```powershell
Set-Location 'C:\Users\Laure\Documents\ChatGPT\IPT\week-13\frontend'
flutter analyze
Set-Location '..\backend'
php tools/inspect-ui-evidence.php 16 7 19
```

The release build, guarded backend regression and browser exercise are already recorded; do not repeat service fault drills casually. The real API is restored on8000, Flutter8080, receiver8090 and MariaDB3306. No fault router or continuous scheduler remains running. Read setup in FLUTTER.md/DELIVERY.md before launching anything; inspect actual ports first. Private credentials are already configured and must not be regenerated.

## Student review, commit and push

```powershell
Set-Location 'C:\Users\Laure\Documents\ChatGPT\IPT\week-13'
git status
git check-ignore backend/.env backend/.env.testing notification-service/.env notification-service/storage/receiver.sqlite
git add -- README.md CHANGELOG.md SUBMISSION-CHECKLIST.md frontend/lib frontend/test/widget_test.dart frontend/pubspec.yaml frontend/pubspec.lock frontend/README.md backend/tools/prepare-ui-recovery.php backend/tools/inspect-ui-evidence.php backend/tools/ui-failure-router.php docs evidence
git status
git diff --staged --stat
git diff --staged
git diff --staged --name-only
git commit -m "Integrate Flutter consultation workflow and browser evidence"
git push origin codex/week13-foundation
git status
git log -7 --oneline
```

Review staged names: no private .env, receiver SQLite ledger, generated dependencies/build, parent records or archive. Keep the genuine failed/retest evidence; it must not be relabeled as a pass. The assistant performs no Git commit/push.

## Explain and demonstrate

“Flutter displays permitted records and collects input. Laravel decides the current user's role and permissions on every request. Cookies preserve the session, while the public CSRF cookie supplies the write header. Manila inputs convert to UTC without depending on my computer's timezone. A timeout can leave an uncertain write, so the form requires a refresh before another submission. Recovery is recorded and queued; the worker receipt proves acceptance by this controlled service.”

Walkthrough questions: Which state changes cause setState/rebuild? Why is a hidden button insufficient authorization? Why is the CSRF cookie readable while the session cookie stays HttpOnly? Trace the09:00 Manila schedule to01:00 UTC in the DB. Trace work19 from422 to recovery to receipt. Explain the19 browser checks versus the unexecuted automated Flutter suite. Identify the pending work and why no real consultation/email/human acceptance is claimed.

Pause here for the student's commit. One planned checkpoint remains afterward: activities/one-page interpretations, SA7/lab report and maintainability/demo, final audit, student's reflection/contribution/AI acknowledgment and defense review. Final submission and unresolved test limitations must remain honest; no future-week work starts automatically.
