# Week 13 API — request and schedule milestone

Checkpoint 07 update: [Flutter integration](FLUTTER.md) now implements the cookie/CSRF client, role workflows and coordinator recovery with [actual browser evidence](../evidence/test-results/FLUTTER-RESULTS.md). Four-kind scans and delivery are implemented, and ten migrations applied. Earlier references below to future Flutter, unimplemented automatic missed detection, eight migrations and old counts describe their checkpoint-time implementation; use the current Flutter/delivery result maps for present status. Domain/automation endpoints themselves are unchanged by this frontend increment.

Session/request/schedule APIs were implemented at checkpoint 03. Checkpoint 04 adds manual meeting outcomes, action assignment/completion and follow-up. Checkpoint 06 adds the automation routes below. The bounded Flutter workflow is implemented. This document describes implemented routes.

## Automation routes added at checkpoint 06

All require existing Sanctum cookie authentication; POST also requires the established CSRF header. Panel is denied (403). Student/adviser lists contain their own authorized recipient work; coordinator can review all. The existing consultation permissions still apply to detail. See [delivery contract/runbook](DELIVERY.md).

| Method/path | Result |
| --- | --- |
| GET /api/automation | Paginated 20 visible rows, latest first |
| GET /api/notifications | Paginated 20 delivered rows for current recipient only |
| GET /api/automation/{id} | data + safe attempts; coordinator also gets recovery history/reasons |
| POST /api/automation/{id}/recover | Coordinator only; JSON reason string 10–500 characters; 200 queues next cycle, not delivered |

Responses hide claim_token/deduplication_key. Attempt detail contains cycle, number, times, outcome, code, HTTP status, duration and receipt. Recovery 422 means invalid reason; 409 means delivered/non-review/obsolete source or cycle limit. Missing IDs return 404, inaccessible existing records 403; this known existence disclosure is unchanged. At most three cycles, three attempts each; original work identity/total history retained. Permanent/exhausted failures remain assigned to review_owner_id; GET is how the coordinator discovers them. No background push/email to the coordinator is claimed. The receipt endpoint for the controlled service is separate from the browser API and requires a private shared key unavailable to Flutter.

## Authentication decision and local run

Laravel Sanctum **4.3.3** supplies the standard first-party SPA cookie/session flow. The browser does not store a private API key or long-lived bearer token. Laravel session regeneration protects login from session fixation; logout invalidates the session and rotates its CSRF token. Session lifetime is 120 minutes with the existing file-session configuration, HttpOnly session cookie and SameSite=Lax. Local HTTP means Secure transport/cookie protection is not demonstrated; Week 14 must address HTTPS. No API token is issued by this application. Sanctum's standard token table is installed so framework fallback handling has its expected schema; it is unused by the supported login flow.

Stateful hosts default to `127.0.0.1:8080,127.0.0.1:8000`. CORS permits exactly the configured `FRONTEND_URL` and credentials; wildcard origins are not enabled. Clients send Accept: application/json and the configured Origin. The client first GETs `/sanctum/csrf-cookie`, keeps cookies, and sends the URL-decoded XSRF-TOKEN cookie as X-XSRF-TOKEN on writes. The session cookie stays HttpOnly. A missing/invalid CSRF token returns 419. Stateful clients must retain cookies across requests. A stateless login cannot start a session and returns a safe 419 rather than a diagnostic exception.

The future Flutter integration must use a browser HTTP client with credentials enabled and send the CSRF header. It is **not implemented by the existing foundation screen**. Cookie flow was exercised through real HTTP using PHP curl; actual Flutter login/authorization screens remain pending.

The current API uses one direct PHP development server. Run from its required public working directory, after inspecting existing processes/ports:

```powershell
Set-Location 'C:\Users\Laure\Documents\ChatGPT\IPT\week-13\backend\public'
php -S 127.0.0.1:8000 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
```

Do not run the router from backend/; it expects public/index.php. Stop an existing verified API process before starting a replacement. During verification, the older Artisan/no-reload server returned a safe 503 with database authentication error 1045 while current CLI DB inspection succeeded. After removing the inspected duplicate API processes and running one direct server from public/, the full live flow passed. The first direct attempt used the wrong working directory and returned a non-JSON failure; its failed record is retained. This does not justify a database/password/system repair; none was performed. Use one server and reload it after private configuration changes.

For a fresh checkout after installing locked dependencies and configuring fresh app/test databases as described in DATABASE.md, run `php artisan migrate --no-interaction`. There are now eight migrations including consultation_events, Sanctum's table and follow-ups/action audit. Do not recreate databases or reset live fixtures.

## Implemented endpoints

All business paths below use base `http://127.0.0.1:8000/api`. The CSRF-cookie bootstrap is outside /api. The health route remains public liveness only.

| Method/path | Request / result | Permission |
| --- | --- | --- |
| GET /health | 200 public project metadata; no DB readiness promise | Public |
| POST /login | email + password; 200 data {id,name,role}; no password/token | Valid fictional account and CSRF/session; six attempts per minute per IP, then 429 |
| GET /me | 200 data {id,name,role} | Authenticated |
| POST /logout | 200 message; session invalidated | Authenticated + CSRF |
| GET /advisers | 200 data [{id,name}]; at most first 100 by name | Student only; directory is fictional, not verified availability |
| GET /consultations?page=1 | Laravel pagination, 20 records/page, newest ID first | Student own; adviser assigned; panel explicit links; coordinator all |
| GET /consultations/{id} | 200 data consultation + limited participant names/roles + action_items | Same record permission |
| POST /consultations | adviser_id + agenda; 201 data + correlation_id | Student only; student_id comes from authenticated user |
| PATCH /consultations/{id}/schedule | starts_at + ends_at; 200 data + correlation_id | Assigned adviser only, requested state only |
| PATCH /consultations/{id}/status | status completed/missed + meeting_notes; 200 data + correlation_id | Assigned adviser; confirmed meeting after its scheduled end |
| POST /consultations/{id}/action-items | description + due_at; 201 data + correlation_id | Assigned adviser; completed meeting |
| PATCH /action-items/{id}/complete | No body required; 200 data + changed + correlation_id | Assigned student who also owns the consultation |
| POST /consultations/{id}/follow-ups | message; 201 data + correlation_id | Owning student or assigned adviser; completed/missed meeting |

Responses include IDs, references, relevant agenda/status/schedule and permitted participant details. Consultation participant relationships select id/name/role; they do not expose passwords/hashes or account emails. GET /me and login expose only id/name/role. No user-directory endpoint exposes arbitrary accounts.

Request agenda: required string, 10–2000 characters. adviser_id must reference a user whose server role is adviser. A supplied student_id, status, role or schedule fields are prohibited on creation. Unrecognized extra fields are not mass-assigned. The server generates the reference and requested status/version 0.

Schedule values use exact UTC format `YYYY-MM-DDTHH:mm:ssZ`, e.g. `2026-11-05T01:00:00Z`. starts_at must be in the future; ends_at must be later. The client converts Manila display/input to UTC. The adviser and consultation rows are locked in a transaction; an existing confirmed interval for that adviser must not overlap (existing start < proposed end AND existing end > proposed start). Adjacent intervals are allowed. This checks the prototype's recorded consultations, **not actual adviser availability** or external calendars. Same-adviser lock serialization is implemented; an actual competing-process race test remains pending.

A second confirmation returns 409 and does not increment schedule_version or add another audit event. Rescheduling is not part of this endpoint. It does not send a reminder or create notification work yet. Student conflicts across different advisers and real panel calendars are not checked by this bounded implementation.

## Meeting, action and follow-up rules (checkpoint 04)

The assigned adviser can move a confirmed consultation to completed or missed only once, after ends_at. Required meeting_notes is a string of 10–2000 characters. Invalid target statuses/notes return 422; premature or repeated outcomes return 409. There is no terminal-outcome correction/reopen endpoint. These are bounded prototype choices, not university attendance policies. Automatic missed-session detection remains unimplemented.

Only the assigned adviser can create an action item after completed status. description is 10–500 characters; due_at is an explicit future UTC timestamp using the schedule format. assigned_to_id, completed_at and deadline_version are prohibited client fields: the server uses the consultation's student, null completion and version 1. Noncompleted meetings return 409. This endpoint does not yet send deadline notices.

Only the assigned student who owns the linked consultation can complete its action item. The consultation and item are locked in that order, permission/state rechecked, and completion time comes from the server. The first call returns changed=true and an audit correlation ID. Repeat calls preserve the original timestamp, return changed=false/correlation_id=null, and create no audit event. This is the idempotent completion guarantee; ordinary action creation POSTs are not idempotent on retries.

After completed or missed status, the owning student and assigned adviser may append a follow-up message (10–2000 characters); the server supplies author_id and rejects a supplied author_id. Read-only panel/coordinator roles cannot append. Authorized detail responses include action_items and ordered follow_ups with limited author id/name/role. No note edit/delete endpoint exists. Detail currently returns the linked note/action collection for the bounded classroom data; large-history pagination is not evaluated. Follow-up POST creates another note on each valid call; no duplicate-submission guarantee is claimed.

ConsultationEvent is now a small shared audit writer used by both controllers, keeping audit fields consistent without a separate service layer. Action events include the action_item_id; follow-up events identify the consultation/actor and correlation. Note bodies/descriptions stay out of the audit payload. All four new writes include their audit in the same transaction. The audit-failure test checks rollback of meeting state, action assignment, action completion and follow-up creation.

Eight migrations are now applied. Existing rows/events from earlier checkpoints were preserved. Real checkpoint-04 HTTP evidence used two explicit past-confirmed fixtures (IDs 5/6), then executed actual outcome/action/follow-up calls. This initialization is disclosed; it is not real-meeting evidence. The current app snapshot has 6 consultations, 2 actions, 3 follow-ups and 9 total audit events. See [workflow result map](../evidence/test-results/WORKFLOW-RESULTS.md).

## Errors and audit

| Status | Meaning |
| --- | --- |
| 401 | Missing/invalid session, or generic invalid credentials |
| 403 | Authenticated role/record is forbidden |
| 404 | Record does not exist |
| 409 | State already confirmed/nonrequested, or recorded adviser interval conflict |
| 419 | Missing/expired CSRF/session bootstrap |
| 422 | Invalid request fields, adviser role or dates |
| 429 | Login attempt limit exceeded |
| 503 | Safe database/dependency failure; no SQL/trace/credentials in response |

The contract uses 403 for existing inaccessible records and 404 for absent records. This can reveal existence of an ID; record contents remain hidden. Consider a uniform 404 policy during the later hardening review rather than claiming existence is concealed now.

Student request and schedule confirmation each insert a minimal consultation_events row **in the same transaction** as the state change. Fields: consultation/actor IDs, server-generated correlation UUID, event, previous/new status, schedule version and timestamp. No agenda, password, cookie or token is written to that audit row. There is no public event-update/delete endpoint. These are auditable application records, not a tamper-proof store against a privileged database administrator.

Database failures suppress default SQL/binding exception logging and record only a safe event name, SQLSTATE/driver code. Routine raw request bodies are not logged. The current error log does not yet provide the full per-operation correlation/structured automation telemetry required later. A database/audit failure returns safe 503 and rolls back the operation; synthetic audit failure rollback was tested. [Actual evidence](../evidence/test-results/API-RESULTS.md) includes preserved failures and recovery.

## Repeatable checks

```powershell
Set-Location 'C:\Users\Laure\Documents\ChatGPT\IPT\week-13\backend'
php artisan test
php vendor/bin/pint --test
```

These run only against the guarded isolated MariaDB test schema; feature-test CSRF bypass is disclosed. For a separate actual HTTP exercise, `php tools/verify-api.php` reads the private demo password locally, keeps cookies only in memory, emits sanitized JSON, and retains **one new fictional consultation per successful run**. It does not clean up evidence rows. Repeating it at the same proposed time may produce an overlap conflict; inspect retained state instead of deleting history or changing actual availability. Current saved success is `api-live-retest-3.json`, run ab4832621cd3, consultation **4**, with two audit events. Do not rerun it merely to regenerate the same evidence.

## Sources and limits

- [Laravel Sanctum first-party SPA flow](https://laravel.com/docs/12.x/sanctum#spa-authentication)
- [Laravel authorization policies](https://laravel.com/docs/12.x/authorization)
- Installed Sanctum/session middleware and Laravel development router were inspected for actual behavior.

Local HTTP/controlled data only; no real email or production readiness claim. Full Flutter workflow/UI evidence, automation retry/recovery, required activities and SA7 remain unfinished.


## Checkpoint08 update — 2026-10-30

Coordinator recovery success now includes `log_status: recorded|unavailable`. A logging outage after committed recovery still returns200/pending and retains its audit; it does not justify repeating recovery. See ACTIVITY-2-RESULTS.md for the controlled fault and regression.


## Revised Week13 version note

Runtime endpoints remain those of cfbacef; no Week14 HTTPS/envelope/replay endpoints are included. The [User Manual](USER-MANUAL.md) matches current role controls and numbered procedures; [revision verification](REVISION-VERIFICATION.md) names its checked build. Structured automation logging is implemented as documented in DELIVERY.md; older domain-API milestone notes above do not mean automation logs remain unimplemented.
