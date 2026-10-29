# Foundation verification record

Recorded 2026-10-29 Asia/Manila. Build: **uncommitted foundation**, branch `codex/week13-foundation`; [source SHA-256 fingerprints](foundation-build.json). No commit hash existed at execution time. Environment: Windows, PHP 8.2.12, Laravel 12.70.2, Flutter 3.47.8/Dart 3.13.7, loopback HTTP. Database not connected, no fictional workflow rows created yet. CLI/widget tests use framework-controlled test requests; the browser check uses the real API.

| Check / preconditions | Expected | Actual | Result / evidence |
| --- | --- | --- | --- |
| `php artisan test --compact --no-ansi`; fresh ignored environment, no DB needed | Default scaffold and health tests succeed | Initial run: 4 passed, 1 failed, 7 assertions; unexpected Access-Control-Allow-Origin header in unlisted-origin test | Failed assertion preserved below; no false pass |
| Correct CORS test expectation; rerun same command | Fixed configured origin, never wildcard or untrusted origin; exact public health JSON | 5 passed, 8 assertions, 0.46 s | Pass; [terminal output](foundation-backend-retest.txt). Two tests are default scaffold examples; three target health/CORS |
| Laravel Pint check | Standard formatting | Initial check identified four scaffold files; formatter applied standard changes; subsequent check passed | Pass; no business logic change |
| `flutter analyze` | No static issues | No issues found (3.7 s) | Pass, recorded from actual terminal output |
| `flutter test` | Execute two widget checks using MockClient (health success and safe 503/retry feedback) | Compiler execution blocked by Windows Application Control before tests completed | **Blocked**, not passed; no coverage claim |
| `flutter build web` | Compile fresh Web client | Built build/web in 39.9 s; Wasm dry run also reported success | Pass, recorded from actual terminal output; Wasm browser runtime untested |
| FND-01 actual `GET /api/health`, fresh server on 8000 | 200 and exact Project 29 metadata, no secrets | 200, correct project/week/foundation and local CORS header, 359 ms | Pass; [timestamped request/response](../api-tests/foundation-health-response.json) |
| FND-UI actual browser at 127.0.0.1:8080, API up | Button sends request; displays project connection success | Connected to the Week 13 Laravel API | Pass; [normal viewport](../screenshots/application/w13-foundation-api-connected.jpg) |
| FND-UI-N same screen at 390x844 | Wrapped readable content and operable connection button | Content visible without clipping; connected state retained | Foundation layout pass; [narrow capture](../screenshots/application/w13-foundation-narrow-390.jpg) |
| FND-SRC actual saved api.php in VS Code | Filename/path, readable health route and line numbers | Visible saved route lines 1–11 | Captured; [real VS Code](../screenshots/source-code/w13-foundation-health-vscode.jpg). Source capture does not prove execution |

Initial CORS failure excerpt, transcribed from actual terminal output (not an untouched raw log):

```text
FAILED Tests\Feature\HealthTest > unlisted origin is not granted browser access
Unexpected header [Access-Control-Allow-Origin] is present on response.
Failed asserting that true is false.
Tests: 1 failed, 4 passed (7 assertions)
Duration: 0.45s
```

Interpretation: the installed single-origin CORS implementation deliberately emits the configured allowed origin. For `Origin: https://untrusted.example`, a response allowing `http://127.0.0.1:8080` still fails the browser's origin match. The test expectation was wrong. CORS is not authentication or authorization; those controls remain unimplemented.

Widget-test blocker excerpt, transcribed from actual terminal output:

```text
loading .../frontend/test/widget_test.dart [E]
An Application Control policy or security policy has blocked execution.
ProcessException: An Application Control policy has blocked this file
.../dart-sdk/bin/dartaotruntime.exe
```

No Windows security setting, SDK binary or exception was changed. The stalled test process was stopped. The following web build succeeded, so compilation and actual-browser integration have separate evidence. No widget test result may be inferred from that success. The blocked command can be reproduced with `flutter test`; resolving any machine policy requires the device owner's approved process, not an automatic workaround.

Limitations: no workflow, authentication/authorization, MySQL, reminder, missed-session, deadline, progress summary, retry/recovery, service failure-injection or full responsive/usability verification exists yet. The 359 ms measurement is one liveness request, not a performance evaluation. Future tests must preserve their own expected/actual outcomes, timestamps, build identity and failures.
