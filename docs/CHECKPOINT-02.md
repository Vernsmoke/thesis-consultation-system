# Checkpoint 02 — consultation data foundation

First commit `d43b0df` was verified locally and on the new remote. This next increment is ready for student review/commit; no commit or push has been performed by the assistant.

Added role and consultation schema, panel links, action items, Eloquent models, repeatable fictional seeding and isolated MariaDB tests. Fresh app/test accounts have privileges on their own databases only. Generated passwords remain in ignored private files. The Flutter screen is unchanged.

## Verify and understand

```powershell
Set-Location 'C:\Users\Laure\Documents\ChatGPT\IPT\week-13\backend'
php artisan migrate:status
php artisan test
php vendor/bin/pint --test
php tools/inspect-data.php
```

Expected: five migrations recorded; **14 tests pass, 35 assertions**; six fictional users, three consultations, one panel link and one action item; cross-database isolation outcome pass/error 1142. Actual results, initial failures and retests are in [DATA-RESULTS.md](../evidence/test-results/DATA-RESULTS.md).

Do not repeat provisioning or reset the app database. [DATABASE.md](DATABASE.md) explains fresh-checkout setup, private configuration and test isolation. MariaDB remains running on loopback port 3306; the foundation servers remain at 8000/8080. Inspect existing processes before starting another instance. Widget tests remain blocked by the previously recorded Windows machine policy; no frontend changes were made.

## Student commit/push commands

```powershell
Set-Location 'C:\Users\Laure\Documents\ChatGPT\IPT\week-13'
git status
git check-ignore backend/.env backend/.env.testing
git add -- .gitignore README.md CHANGELOG.md SUBMISSION-CHECKLIST.md backend/.env.example backend/.env.testing.example backend/app/Models backend/config/consultation.php backend/database backend/phpunit.xml backend/tests backend/tools docs/DATABASE.md docs/CHECKPOINT-02.md docs/DESIGN.md docs/ISSUES.md docs/LAB-LOG.md evidence
git status
git diff --staged --stat
git diff --staged
git diff --staged --name-only
git commit -m "Add consultation data model and isolated MariaDB tests"
git push origin codex/week13-foundation
git status
git log -2 --oneline
```

Review that `.env` and `.env.testing` are absent from staged names; only their sanitized examples belong there. Do not stage parent planning records or archive. No force push is needed. Stop if verification fails rather than labeling it a pass.

## Defense explanation and questions

“A migration versions the database structure so another developer can recreate it. Foreign keys keep records linked to existing users and consultations. The panel table prevents the same panel member being linked twice. CHECK constraints reject incomplete or reversed confirmed schedules. Seed data is fictional and repeatable, and seeding again preserves edited records. Tests use a separate database/account so rebuilding test tables cannot erase the running demonstration.”

Explain personally: Why do we store UTC and display Manila time? Why is a role column not authorization? Which constraints cannot verify that an assignee is the consultation's student? Why must passwords be hashed and omitted from logs? Which files must never be committed? What evidence distinguishes a seeded completed meeting from an actual executed workflow?

Next milestone after the student's commit: Laravel authentication, server-side role/record authorization and consultation request/schedule workflow APIs. Continue within Week 13; all four automations and the required assessments remain pending.
