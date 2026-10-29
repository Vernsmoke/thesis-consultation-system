# Checkpoint 05 scan verification

Build: uncommitted scan foundation based on **4a51934b36f9010f22989ac1cbdd797e895db725**. [Source hashes/evidence times](scan-build.json). Executed 2026-10-29 Asia/Manila; Windows/PHP 8.2.12/Laravel 12.70.2/MariaDB 10.4.32. No archive/private historical settings used, no new dependency, no frontend change.

- [Migration](scan-migration.txt) succeeded; ninth app migration preserves earlier audit rows and labels future automatic actors explicitly.
- First suite: **50 pass / 242 assertions / 5.09 s**; [original output](scan-tests-initial.txt). Subsequent tests after correlation/log reporting/type-annotation refinements retained. [Final source run](scan-tests-final-source.txt): **50 / 242 / 5.21 s**. Pint and whitespace checks pass. No unexpected failing execution occurred in this increment; expected injected failures are test cases, not invented failed results.
- [Actual live command exercise](scan-live-initial.json), run **b1239e74abbf**, used two initialized fictional confirmed meetings **7/8** and overdue action **3**. First command created **11 work rows** and detected **one elapsed meeting**; repeat created **0**. Actual `schedule:run` output records consultations:scan DONE in 632.40 ms; work count remained 11. [Schedule definition](scan-schedule-list.txt) is supporting configuration, not the only execution proof.
- Actual kinds: **4 reminder / 3 missed_session / 2 action_deadline / 2 progress_summary**. All pending, zero attempts, no receipts. **Zero delivered notifications.** No HTTP notification service call or continuous scheduler run occurred.
- [Safe live snapshot](scan-app-snapshot.json): 6 users, 8 consultations, 2 panel links, 3 actions, 3 follow-ups, 10 audit events, 11 pending work. Earlier records retained. Automated missed event has actor_type=automation/actor_id=null, review flag/basis and transition correlation; no fake coordinator contribution.
- [Actual JSON log copy](../logs/automation-scan.jsonl) includes explicitly labeled testing and local runs. [Actual saved scanner in VS Code](../screenshots/source-code/w13-automation-scan-vscode.jpg) shows implementation; commands/tests/logs above prove execution.

| Case | Expected | Actual evidence |
| --- | --- | --- |
| SCAN-FOUR | Eligible reminder, elapsed meeting, overdue action and daily summary produce correct recipients/minimal payload | Isolated test: 9 rows (2/3/2/2); live current dataset: 11 (4/3/2/2) |
| SCAN-REPEAT | Stable identities, no duplicate records/events on repeated scan | Isolated and actual repeat/scheduled repeat pass |
| SCAN-WINDOW | Completed/out-of-window meetings and completed/future actions skipped | Isolated tests pass |
| SCAN-TIMEZONE | New daily identities at Manila midnight (16:00 UTC) | Isolated controlled-clock test passes; computer clock untouched |
| SCAN-INVALID | Invalid source roles -> stable coordinator needs_review record, source unchanged | Isolated test passes; no wrong-recipient pending work |
| SCAN-OWNER | Missing coordinator -> visible nonzero result/no changes | Isolated command test passes |
| SCAN-CAPACITY | Category exceeds lookahead cap -> whole transaction rollback | Isolated command test passes; prior missed/work/audit changes absent |
| SCAN-STORAGE | Inject late summary insert failure -> whole transaction rollback | Isolated MariaDB test passes |
| SCAN-LOG | Log failure after commit -> nonzero visible warning, durable work retained | Isolated test passes, correctly distinguishes commit from rollback |
| SCAN-UNIQUE | Duplicate deduplication_key with a different correlation ID rejected by DB | Isolated test passes; validates the identity index specifically |

The final query-builder annotation/readability changes preserve the scan rules and were regression-tested without creating new live fixtures. JSON lines and payloads contain no agenda, note, password, token/cookie or account email. Private credential scan is recorded by checkpoint review; the original private environment files remain ignored.

Limits: pending work is not service success; delivery/retry/backoff/timeout/claim recovery, stale-work cancellation, receipts, permanent delivery-failure routing/recovery APIs and Flutter automation views are not implemented. Invalid-source needs_review is not a demonstrated permanent HTTP delivery failure. One actual scheduled invocation does not prove a persistent operating-system scheduler or operational uptime. Large workloads above cap stop for human action rather than automatically paginate; aggregate performance unmeasured. False-missed inference/correction policy remains a documented prototype limitation. Activities/SA7/reflection/defense are incomplete.
