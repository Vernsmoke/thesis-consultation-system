# Checkpoint 07 results

Base student commit `297f26607e9e42b629b6d4911d4224bd12fbd5e5`; uncommitted Flutter increment. Actual exercise crossed midnight: **Oct29 23:52 through Oct30 00:10 Asia/Manila**, corresponding UTC timestamps in `flutter-browser-checks.json`. Source/build fingerprints are in `flutter-build.json`. No archived source/settings/evidence, app reset or clock change.

## Checks and preserved failures

| Check | Actual result | Artifact |
| --- | --- | --- |
| Locked offline package resolution | Success; existing web1.1.2 now direct dependency, no version upgrade | flutter-workflow-pub.txt, pubspec.lock diff |
| Initial static analysis | Failed: obsolete foundation tests used removed client parameter, six style findings | flutter-workflow-analyze-initial.txt |
| Final static analysis | No issues found after new meaningful tests/style fixes | flutter-workflow-analyze.txt |
| Web release build | Success,37.4s; normal Wasm dry run and font tree shaking | flutter-workflow-build.txt |
| Automated Flutter browser suite | **Unfinished: stalled loading, zero tests executed**, stopped after >12min; no pass claimed | flutter-workflow-browser-tests.txt, flutter-browser-test-blocker.txt |
| Existing backend regression | **63 pass /314 assertions**,6.57s, isolated testDB | flutter-backend-regression.txt |
| Live database effect checks | **7 checks pass**, actual retained states/audit/one receipt | ../api-tests/flutter-ui-effects.json |
| Actual UI scenarios | **19 recorded checks pass** to selected scope, screenshots below | flutter-browser-checks.json |

The browser test attempt does not resolve the earlier VM compiler security-policy blocker. No SDK/security configuration was altered. The first restarted PHP listeners were unreachable from host clients while inside the restricted process sandbox. Only those inspected project processes were replaced outside it; actual HTTP/browser then worked. Initial in-app browser tabs showed connection errors before restart. No database repair was applied.

AX setValue did not propagate Flutter input and rapid role-switch fills needed focus/state verification. An attempted student action button lookup occurred while the previous adviser account had been reselected; state inspection caught it, no unauthorized write occurred, and correct student login then passed. A title locator also missed Flutter's merged semantics; the actual detail rendered correctly. These are automation harness issues, not fabricated product failure claims. The final view and DB journal confirm the actual actions.

## Actual browser cases and screenshots

All images under `../screenshots/application/` are captures of the compiled running Flutter app. Screenshot names below omit that prefix. Expected and observed outcomes/time are also recorded per WEB ID in the JSON. Preconditions: existing fictional accounts, private cookie/CSRF session, retained app database and receiver ledger. No headers/password values saved.

| Case | Expected and observed | Screenshot(s) |
| --- | --- | --- |
| WEB-01/02 | Blank email/password validation; wrong credentials generic rejection and clear password | w13-login-validation.jpg; w13-login-rejected.jpg |
| WEB-03 | Real student cookie/CSRF login and14 permitted starting consultations, no coordinator menu | w13-student-consultations-desktop.jpg |
| WEB-04 | Missing adviser/agenda rejected; one request16 saved | w13-request-validation.jpg; w13-request-created.jpg |
| WEB-05 | Student opening another student's consultation1 denied, no private detail | w13-forbidden-record.jpg |
| WEB-06 | Own work17 accepted receipt with timeout5019ms then HTTP200 retry; narrow390x844 wraps content | w13-notification-history-narrow-390.jpg |
| WEB-07 | Keyboard Tab focuses Open ID, Enter opens; reversed times rejected, adviser confirms16 at Oct30 09:00–09:30 Manila; premature outcome absent | w13-schedule-order-validation.jpg; w13-schedule-confirmed.jpg |
| WEB-08 | Adviser closes retained elapsed fixture7, assigns action4 and follow-up4 | w13-adviser-outcome-action-followup.jpg |
| WEB-09 | Owning student completes action4; button removed, timestamp retained | w13-student-action-completed.jpg |
| WEB-10 | Coordinator page2 of22 records shows final2, Next disabled | JSON observation; pagination uses real API data |
| WEB-11 | Work19 permanent422 review -> recorded coordinator recovery -> pending cycle2 -> actual bounded worker receipt; original failure retained | w13-permanent-failure-review.jpg; w13-recovery-queued.jpg; w13-recovery-delivered.jpg |
| WEB-12 | Dated Manila Oct30 summary counts show pending/no attempts; tablet768x1024 | w13-progress-summary-tablet-768.jpg |
| WEB-13 | Missed-session inference explicitly needs human review; deadline notice links correct action/source | w13-missed-session-notice.jpg; w13-action-deadline-notice.jpg |
| WEB-14 | Panel reads explicitly linked consultation2 with read-only label and no mutations/automation menu | w13-panel-readonly.jpg |
| WEB-15 | Unassigned adviser2 sees zero records and clear empty state | w13-empty-adviser.jpg |
| WEB-16 | Actual API stopped -> readable network error/Refresh enabled | w13-api-network-failure.jpg |
| WEB-17 | Read-only stand-in delays3s: loading/disabled controls then safe503 feedback | w13-api-loading-controlled.jpg; w13-api-controlled-503.jpg |
| WEB-18 | Read-only stand-in delays12s -> ten-second client timeout/uncertainty message | w13-api-timeout.jpg |
| WEB-19 | Actual Laravel restored, session retained, normal empty read/error cleared | w13-api-restored.jpg |

`w13-notification-history-tablet-768.jpg` is an initial resize capture taken before Flutter settled; retain it for transparency, do not use it as the final tablet layout proof. Final tablet evidence is the progress-summary image. Desktop/narrow screenshots do not certify every form at every width or complete accessibility. Role-switch input retry is disclosed above; the signed-in role was verified before each write.

Source evidence: `../screenshots/source-code/w13-flutter-cookie-csrf-vscode.jpg` shows actual saved BrowserClient credentials/CSRF code, filename and line numbers. It proves implementation. Runtime UI/cookie requests and the persisted DB audit provide separate execution evidence.

## Retained data and scope

Before:6 users/15 consultations/3 actions/3 follow-ups/10 audit events/18 work/17 accepted. After: **6 users/16 consultations/4 actions/4 follow-ups/16 audit events/22 work;18 accepted/3 pending/1 needs_review**. Two panel links unchanged. New consultation16 and six new domain audit events are actual browser writes. Elapsed fictional consultation7 was deliberately completed after its real scheduled end; this is not a real meeting or an accelerated completion of future consultation16.

At Manila midnight the actual scan created work19/20 reminders for16 plus21/22 new-day snapshots. Only19 was faulted/recovered/delivered. Work15 remains original exhausted needs_review, cycle1/3 total attempts. New pending20–22 are honestly pending; no continuous scheduler/worker launched. Recovery work19 has one422 failure, one actor6 recovery, one accepted cycle2 attempt and exactly one receiver receipt. Evidence: flutter-recovery-preparation.json, flutter-recovery-cause-fixed.json, flutter-recovery-worker.json and the read-only effects verifier.

Before/after app snapshots: flutter-before-app-snapshot.json, flutter-after-app-snapshot.json. Controlled service replacement identity: flutter-failure-service-before.json; final actual services: flutter-services-final.json. Safe local delivery logs: ../logs/flutter-automation.jsonl. Private values remain excluded; final candidate text scan: flutter-secret-check.json.

## Remaining gaps

Automated Flutter execution remains unfinished. Actual browser419/session-expiry and every server422 branch are not covered by these19 checks. Manual missed-outcome selection is implemented, but the browser exercise selected completed; missed backend behavior and inferred notice were separately verified. Uncertain-write resubmission guard is implemented; the delayed stand-ins exercised reads, not an ambiguous committed write. No full keyboard/screen-reader/contrast audit, real users/UAT/attendance/email, actual process-kill/concurrency test or continuous scheduler endurance claim.

The Flutter milestone is ready for a meaningful student commit with these limits. Week13 remains incomplete until activities/one-page interpretations, SA7/lab report, maintainability/demo, student reflection/contribution/AI acknowledgment, final review and actual submission are addressed. Student alone commits/pushes; nothing is submitted merely because these artifacts exist.
