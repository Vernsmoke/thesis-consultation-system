# Thesis Consultation Reminder System — Week 13

Project 29, PFEBSIT 002. Individual classroom prototype using Flutter Web, Laravel and XAMPP MariaDB through Laravel's MySQL driver.

Status: **foundation ready for the student's first commit, with widget tests blocked by Windows Application Control**. Laravel health checks, Flutter analysis/web build and actual browser connection succeeded. Consultation features, database setup, automation, full workflow evidence, activities and SA7 are unfinished. See [requirements/status](SUBMISSION-CHECKLIST.md), [bounded design](docs/DESIGN.md), [laboratory log](docs/LAB-LOG.md) and [evidence index](evidence/README.md).

The student reports teacher assignment/approval. The official registry was not independently inspected. Use only fictional data. The student personally reviews, verifies, commits, pushes and defends the work. AI-assisted code and documentation must be reviewed and acknowledged under institutional policy; preparation is not evidence of student understanding.

Repository: https://github.com/Vernsmoke/thesis-consultation-week13.git. This is a fresh history. The archived generic application is not a dependency or submission artifact.

## Planned workflow

Request + agenda -> adviser schedule confirmation -> meeting status -> action items -> follow-up. Required automations: consultation reminders, missed-session handling, action-item deadlines and progress summaries, with bounded retries and coordinator review/recovery.

## Foundation setup

Run commands in PowerShell. Verified installed tools and exact versions are recorded in the lab log. Laravel 12 is selected for installed PHP 8.2 compatibility. Flutter project targets Web only. Generated dependency/build directories and local environment settings must stay out of Git. Lockfiles belong in Git.

For a fresh checkout, install backend dependencies and create a new private key:

```powershell
Set-Location 'C:\Users\Laure\Documents\ChatGPT\IPT\week-13\backend'
composer install --no-interaction
Copy-Item .env.example .env
php artisan key:generate
php artisan test --compact
php artisan serve --host=127.0.0.1 --port=8000 --no-reload
```

`Copy-Item` above is for a fresh checkout only. In the current workspace `.env` and a newly generated key already exist: do not overwrite them. The health endpoint needs no database connection. File cache/sessions allow this foundation check while database setup is pending. Queue configuration is not a running automation worker.

In a second PowerShell window, install and build the Web client, then serve it:

```powershell
Set-Location 'C:\Users\Laure\Documents\ChatGPT\IPT\week-13\frontend'
flutter pub get
flutter analyze
flutter build web --dart-define=API_BASE_URL=http://127.0.0.1:8000/api
php -S 127.0.0.1:8080 -t build/web
```

Open `http://127.0.0.1:8080` and select **Check connection**. It should display **Connected to the Week 13 Laravel API.** Use `127.0.0.1`, not `localhost`, because the configured CORS origin is exact. The frontend `.env.example` documents a public compile-time option; Flutter does not load that file automatically. No Node/Vite build is required: Flutter provides the frontend; Laravel's default frontend resources are unused scaffold files.

Alternative development command: `flutter run -d web-server --web-hostname=127.0.0.1 --web-port=8080 --dart-define=API_BASE_URL=http://127.0.0.1:8000/api`. This command has not been verified in this fresh build. Do not start another process on an occupied port. At the first checkpoint the fresh backend and static Flutter server remain running on 8000 and 8080; inspect services before restarting. Ctrl+C stops a server in its owning terminal.

`flutter test` was attempted but Windows Application Control blocked `dartaotruntime.exe`; no widget test pass is claimed. Analysis, web compilation and real browser verification are separate successful checks. No SDK/security-policy repair was applied. See [recorded results and limits](evidence/test-results/FOUNDATION-RESULTS.md).

MySQL connection, dedicated database creation and fictional seeding are pending; do not use an archived database or credentials. `.env.example` contains a proposed new database/user name and an empty password, not a working login. No production connection or real notification delivery is authorized. Existing framework user factories/seeders are untouched scaffold examples and have not been run; Project 29 roles/data seeders are pending.

## Student checkpoint and next milestone

See [first commit commands and defense explanation](docs/CHECKPOINT-01.md). No commit or push has been performed by the assistant. After the student commits, verify history/status before building consultation data and authorization. The shared parent `PROJECT-PROGRESS.md` holds the handover for another chat.

## Reference documentation

- [Laravel 12 installation](https://laravel.com/docs/12.x/installation)
- [Laravel 12 runtime requirements](https://laravel.com/docs/12.x/deployment#server-requirements)
- [Flutter Web setup](https://docs.flutter.dev/platform-integration/web/setup)
- [Flutter Web builds](https://docs.flutter.dev/platform-integration/web/building)

Assignment sources remain in the parent IPT workspace: the original Weeks 13–18 student module and the 35 individual project options guide, common rules and Project 29. They are not copied wholesale into this repository.
