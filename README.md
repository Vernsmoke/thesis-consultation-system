# Thesis Consultation Reminder System — Week 13

Project 29, PFEBSIT 002. Individual classroom prototype using Flutter Web, Laravel and XAMPP MariaDB through Laravel's MySQL driver.

Status: **Week13 revision ready for review and student commits/tagging**, based on committed runtime `cfbacef`. This repository is proposed as the **single continuing repository for Weeks13–18** under the student's reported revised teacher rules. Keep separate weekly chats and identifiable weekly versions. Annotated `week-13` and later tags are our proposed mechanism, not a teacher-specified requirement; no tag has been created by the assistant. Do not rename the repository/change remotes/move folders or mix preserved Week14 security work into this Week13 revision. See [transition plan](docs/REPOSITORY-TRANSITION.md).

Start with the [User Manual](docs/USER-MANUAL.md): numbered procedures, user roles, expected results and actual application screenshots for all major implemented functions. The [revision verification](docs/REVISION-VERIFICATION.md) distinguishes fresh checks from historical evidence and names runtime fingerprints. Latest isolated regression: **64 tests /325 assertions,7.20s**; Flutter analysis clean3.7s. The running web build matches the saved successful checkpoint08 build; runtime source is unchanged. Automated Flutter tests remain unfinished, and the recovery-log warning's real browser outage branch remains unverified.

Read the [SA7/lab report and activities](docs/WEEK-13-REPORT.md), [five topic decisions](docs/DECISIONS.md), [retry/recovery](docs/DELIVERY.md), [API contract](docs/API-CONTRACT.md), [Flutter screen flow](docs/FLUTTER.md), [maintenance/demo](docs/DEMO-AND-MAINTENANCE.md), [checklist](SUBMISSION-CHECKLIST.md), and [student commit/tag commands](docs/STUDENT-COMMIT-PLAN.md). Existing local Word/ZIP artifacts are historical `cfbacef` submissions and predate this manual revision; preserve them and regenerate the final revised source package after the student's reviewed commits/tag. Personal reflection stays outside GitHub and requires student revision/review. Actual upload, instructor acceptance and student understanding are not certified by prepared files.

The student reports teacher assignment/approval. The official registry was not independently inspected. Use only fictional data. The student personally reviews, verifies, commits, pushes and defends the work. AI-assisted code and documentation must be reviewed and acknowledged under institutional policy; preparation is not evidence of student understanding.

Repository: https://github.com/Vernsmoke/thesis-consultation-week13.git. This is a fresh history. The archived generic application is not a dependency or submission artifact.

## Planned workflow

Request + agenda -> adviser schedule confirmation -> meeting status -> action items -> follow-up. Required automations: consultation reminders, missed-session handling, action-item deadlines and progress summaries, with bounded retries and coordinator review/recovery.

## Foundation setup

Run commands in PowerShell. Verified installed tools and exact versions are recorded in the lab log. Laravel 12 is selected for installed PHP 8.2 compatibility. Flutter project targets Web only. Generated dependency/build directories and local environment settings must stay out of Git. Lockfiles belong in Git.

For a fresh checkout on an empty controlled XAMPP environment with the documented database/account names unused, install dependencies, generate a private key and follow the database setup. This is already done in the current workspace:

```powershell
Set-Location 'C:\Users\Laure\Documents\ChatGPT\IPT\week-13\backend'
composer install --no-interaction
Copy-Item .env.example .env
php artisan key:generate
php tools/provision-local.php
php artisan migrate --no-interaction
php artisan db:seed --no-interaction
php artisan test --compact
Set-Location public
php -S 127.0.0.1:8000 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
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

Open `http://127.0.0.1:8080` and **Sign in** with an existing fictional account and the private DEMO_PASSWORD configured in backend/.env. Never publish that value. [Flutter guide](docs/FLUTTER.md) explains accounts, screen flow and permissions. Use `127.0.0.1`, not `localhost`, because CORS is exact. The frontend `.env.example` documents a public compile-time option, not a file Flutter loads automatically. No Node/Vite build is required; Laravel's default frontend resources are unused scaffold files. The earlier connection-only screen was replaced; its historical screenshots remain valid foundation evidence.

Alternative development command: `flutter run -d web-server --web-hostname=127.0.0.1 --web-port=8080 --dart-define=API_BASE_URL=http://127.0.0.1:8000/api`. This command has not been verified in this fresh build. Do not start another process on an occupied port. At checkpoint 03, the API uses one direct PHP server from backend/public on 8000; the static Flutter server remains on 8080, MariaDB on 3306. Inspect services before restarting. Ctrl+C stops a server in its owning terminal. Previous HTTP failures and server working-directory correction are documented in the API results.

`flutter test` was attempted but Windows Application Control blocked `dartaotruntime.exe`; no widget test pass is claimed. Analysis, web compilation and real browser verification are separate successful checks. No SDK/security-policy repair was applied. See [recorded results and limits](evidence/test-results/FOUNDATION-RESULTS.md).

The **current workspace** has a fresh `thesis_consultation_week13` database, a separate `thesis_consultation_week13_test` database and database-scoped accounts. [Database setup/schema guide](docs/DATABASE.md) explains setup for a fresh empty environment, private credentials, fictional seeding and safe test isolation. Do not rerun provisioning in this workspace or reset its app database. `.env.example` and `.env.testing.example` contain empty password fields, not working logins; the actual files stay ignored. No archived database/settings are used, and no production connection or real notification delivery is authorized. The original generic seeder has been replaced by Project 29 fictional fixtures; their seeded statuses are not user-workflow execution evidence.

## Student Git steps and continuing work

Checkpoints01–08 were committed by the student; current base is `cfbacef` on main. Complete the reviewed groups and proposed tag using [current commands](docs/STUDENT-COMMIT-PLAN.md); older checkpoint command files are historical, not instructions to recreate commits. Tests in the revision record apply to the completed documentation working tree and unchanged runtime, not independently to each later student commit.

No persistent scheduler is running. Opening the UI or authorizing recovery does not start a worker. Preserve private .env settings and receiver SQLite ledger; do not reinitialize credentials/reset data. Existing `week-14/` remains separate and untouched for later requested reconciliation after the Week13 version is frozen. The shared root AGENTS/progress records carry revised one-repository instructions. No future week is implemented by this update.

## Reference documentation

- [Laravel 12 installation](https://laravel.com/docs/12.x/installation)
- [Laravel 12 runtime requirements](https://laravel.com/docs/12.x/deployment#server-requirements)
- [Flutter Web setup](https://docs.flutter.dev/platform-integration/web/setup)
- [Flutter Web builds](https://docs.flutter.dev/platform-integration/web/building)

Assignment sources remain in the parent IPT workspace: the original Weeks 13–18 student module and the 35 individual project options guide, common rules and Project 29. They are not copied wholesale into this repository.
