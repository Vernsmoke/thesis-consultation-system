# Checkpoint 04 — workflow verification

Build: uncommitted workflow increment based on **d2fe52978020f52051dba888773bdb8a62a0a8c9**. [Source fingerprints and evidence times](workflow-build.json). Date 2026-10-29 Asia/Manila. Windows, PHP 8.2.12, Laravel 12.70.2/Sanctum 4.3.3, MariaDB 10.4.32. No archived settings or old data reused. No new dependency or Flutter source change.

## Results and preconditions

- New migration added follow_ups and nullable action_item_id in consultation_events; earlier audit rows preserved. [Actual migration output](workflow-migration.txt).
- First regression run: **40 passed / 197 assertions / 4.82 seconds**, including eleven new workflow tests and all 29 prior checks. [Untouched terminal output](workflow-tests-initial.txt). No failing execution occurred in this increment; do not manufacture one. Pint/check passes after ordinary formatting, git diff --check passes.
- Isolated tests use frozen UTC 2026-10-29 and fictional seed data in the dedicated MariaDB test database/account. The full request/schedule/outcome/action/follow-up test advances only Laravel's test clock; no machine clock or app data changes.
- Actual HTTP run **5bf695121a72**: **36 requests matched expected statuses**, two initialized fictional past-confirmed fixtures (IDs 5/6), one explicit panel link, one new action, three follow-ups and **seven new audit events**. [Sanitized live requests/responses/audits](../api-tests/workflow-live-initial.json). Password/cookies stay in memory and are not saved. All six clients logged out at the end.
- [Live app snapshot](workflow-app-snapshot.json): 6 users, 6 consultations, 2 panel links, 2 action items, 3 follow-ups, 9 total audit events. Earlier consultation 4/two events retained. The initial three seeded consultations are still fixtures.
- [Actual routes](../api-tests/workflow-routes.txt); [saved implementation in real VS Code](../screenshots/source-code/w13-workflow-completion-vscode.jpg). Source capture is not execution proof.

| Case | Controlled action / expected | Actual |
| --- | --- | --- |
| FLOW-NORMAL | Student requests with agenda, assigned adviser confirms; advance test clock after end, adviser completes/assigns, student completes/follows up | Isolated API test passes; six audit events for six mutations, detail shows one action/one follow-up |
| MEETING-MISSED | Assigned adviser records missed after end, repeated write rejected | Test and actual HTTP pass; second test write 409, no duplicate audit |
| MEETING-STATE | Requested, future-confirmed or already-completed meeting cannot receive another outcome | Isolated 409 checks pass |
| MEETING-AUTH | Students, panel, coordinator and other adviser cannot record outcome | Isolated 403 checks pass; actual student/other adviser forbidden |
| MEETING-INVALID | Invalid status/missing notes | Isolated 422 field checks pass |
| ACTION-ASSIGN | Assigned adviser, completed meeting, valid future UTC deadline; student derived server-side | Test and actual HTTP 201 pass; version 1, no client-selected assignee |
| ACTION-INVALID | Wrong role, noncompleted meeting, bad deadline/description or forged assignee/completion field | Isolated 403/409/422 pass; actual missing fields 422 |
| ACTION-COMPLETE | Owning student only; repeat preserves timestamp, changed=false, no new audit | Isolated and actual HTTP pass; other student/adviser forbidden |
| FOLLOW-UP | Student/assigned adviser may append after completed/missed; panel/coordinator/unassigned actors cannot | Isolated and actual HTTP pass; explicit panel/coordinator can read authorized detail only |
| FOLLOW-UP-INVALID | Missing/short message, forged author, nonterminal state | Isolated 422/409 pass |
| AUDIT-ATOMIC | Throw after audit insert SQL for meeting, assignment, completion and follow-up | Isolated 503 checks pass; each domain change and audit rolled back; no note text in audit schema |
| SAFE-ERROR | Missing session/action record | Isolated 401/404 pass; real CSRF/session support inherited and used by live script |

## Interpretation and limits

The tests support the implemented server permissions, states, validation and transaction behavior. The live exercise proves real cookie/CSRF-authenticated HTTP execution of the added endpoints; its two starting meetings were **directly initialized controlled fixtures**, not real meetings or API-created schedule confirmations. Full creation/confirmation is exercised in the isolated normal-path test and earlier checkpoint's actual consultation 4 evidence. No claim combines separate runs into one fabricated human consultation.

Manual adviser marking as missed is implemented. **Automated missed-session detection, reminders, action-deadline handling, summaries and retry/recovery are not implemented yet.** Action completion is sequentially idempotent; actual competing-process tests remain pending. Action assignment and follow-up creation are ordinary POST creates and are not promised idempotent on client retries. Outcomes are terminal with no correction/reopen/reschedule endpoint in this bounded increment. Append-only follow-up means the API has no note edit/delete route; a privileged database administrator is not prevented from changing data.

The Flutter UI remains the foundation connection screen; no new browser workflow/keyboard/layout claim or widget test pass. Windows compiler-policy blocker, local HTTP limitation, 403/404 existence distinction and local runtime/migration shared DDL privileges remain as previously documented. Activities, SA7, student reflection and defense understanding are still incomplete.
