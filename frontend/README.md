# Flutter Web client

Project29 Thesis Consultation Reminder System, Week13. Actual session login, role workflows, notifications and coordinator review/recovery.

See [setup, screen flow and source explanation](../docs/FLUTTER.md) and [recorded browser/test results](../evidence/test-results/FLUTTER-RESULTS.md). Use the workspace root README for Laravel/MariaDB/receiver setup. Never compile a private password or service key into this client.

```powershell
flutter pub get --offline
flutter analyze
flutter build web --dart-define=API_BASE_URL=http://127.0.0.1:8000/api
php -S 127.0.0.1:8080 -t build/web
```

Use the existing fictional accounts/private demo password. The exact configured browser origin is http://127.0.0.1:8080. Browser cookie credentials and CSRF header are required; no client bearer key is used. Backend permissions remain authoritative. All dates are explicitly Manila (UTC+8).

Analysis and release build passed. Five new timezone/API/widget tests are authored, but automated Flutter execution is unfinished: browser runner stalled with zero tests executed and prior VM policy blocker remains. Actual browser scenarios and backend regression are separate recorded evidence. Do not claim `flutter test` passed or automatically change Windows security settings.
