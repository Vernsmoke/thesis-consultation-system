# Week 13 API — request and schedule milestone

Implemented at checkpoint 03, based on student commit `27960ba`. Login, scoped consultation reads, student requests and adviser confirmation now work. Meeting-status updates, action-item writes, follow-up and all automation endpoints remain pending. This document describes implemented routes, not proposed future endpoints.

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

For a fresh checkout after installing locked dependencies and configuring fresh app/test databases as described in DATABASE.md, run `php artisan migrate --no-interaction`. There are now seven migrations including consultation_events and Sanctum's table. Do not recreate databases or reset live fixtures.

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

Responses include IDs, references, relevant agenda/status/schedule and permitted participant details. Consultation participant relationships select id/name/role; they do not expose passwords/hashes or account emails. GET /me and login expose only id/name/role. No user-directory endpoint exposes arbitrary accounts.

Request agenda: required string, 10–2000 characters. adviser_id must reference a user whose server role is adviser. A supplied student_id, status, role or schedule fields are prohibited on creation. Unrecognized extra fields are not mass-assigned. The server generates the reference and requested status/version 0.

Schedule values use exact UTC format `YYYY-MM-DDTHH:mm:ssZ`, e.g. `2026-11-05T01:00:00Z`. starts_at must be in the future; ends_at must be later. The client converts Manila display/input to UTC. The adviser and consultation rows are locked in a transaction; an existing confirmed interval for that adviser must not overlap (existing start < proposed end AND existing end > proposed start). Adjacent intervals are allowed. This checks the prototype's recorded consultations, **not actual adviser availability** or external calendars. Same-adviser lock serialization is implemented; an actual competing-process race test remains pending.

A second confirmation returns 409 and does not increment schedule_version or add another audit event. Rescheduling is not part of this endpoint. It does not send a reminder or create notification work yet. Student conflicts across different advisers and real panel calendars are not checked by this bounded implementation.

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

Local HTTP/controlled data only; no real email or production readiness claim. Full workflow, UI state evidence, automation retry/recovery, required activities and SA7 remain unfinished.
