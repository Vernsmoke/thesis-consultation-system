# Checkpoint 01 — fresh foundation

Ready for student review/commit with a recorded widget-test blocker. This checkpoint proves fresh framework setup, public Laravel liveness and real Flutter/API connectivity. It does not complete Week 13.

## Review and verification

Read the [checklist](../SUBMISSION-CHECKLIST.md), [design](DESIGN.md) and [actual verification results](../evidence/test-results/FOUNDATION-RESULTS.md). The backend suite passed five tests/eight assertions; Flutter analysis and web build passed; actual browser connected and narrow foundation layout was captured. Widget tests were blocked by Windows Application Control. No machine security repair was made.

Both fresh loopback servers remain running at the checkpoint. Open `http://127.0.0.1:8080`, press Check connection and inspect the message. Read the health route in VS Code. Backend health is liveness only; it does not test database or automation readiness.

To rerun checks in normal PowerShell (the blocked widget command is listed separately):

```powershell
Set-Location 'C:\Users\Laure\Documents\ChatGPT\IPT\week-13\backend'
php artisan test --compact
php vendor/bin/pint --test
Set-Location '..\frontend'
flutter analyze
flutter build web
# Known machine-policy blocker; a pass has NOT been recorded:
# flutter test
```

## Commit and push — student executes

The branch is `codex/week13-foundation`; origin is the student's new repository. No old remote/history was imported. The remote returned no refs before initialization. No files are staged yet. Directory staging below names only the intended weekly source/docs/evidence; ignore rules exclude local settings/dependencies/builds. Review the actual staged content before committing.

```powershell
Set-Location 'C:\Users\Laure\Documents\ChatGPT\IPT\week-13'
git status
git branch --show-current
git remote -v
git check-ignore backend/.env backend/vendor/autoload.php frontend/build/web/main.dart.js frontend/.dart_tool/package_config.json
git add -- .gitignore README.md CHANGELOG.md SUBMISSION-CHECKLIST.md backend frontend docs evidence
git status
git diff --staged --stat
git diff --staged
git diff --staged --name-only
git commit -m "Establish Project 29 Flutter and Laravel foundation"
git push -u origin codex/week13-foundation
git status
git log -1 --oneline
```

If review reveals an unintended file, unstage it with `git restore --staged -- <path>` after the first commit exists; before an initial commit use `git rm --cached -- <path>` (this removes only its index entry). Do not force-push if the remote changed; inspect first. Do not stage the parent workspace or archive. Parent planning/progress records are separate from this weekly submission and intentionally not included by these commands.

## Explain it to the teacher

“I started a new Project 29 repository and defined the consultation boundary before adding features. Flutter is the browser client. A stateful widget holds loading and connection feedback; its asynchronous HTTP call requests Laravel's public health endpoint. Laravel returns safe project metadata and permits the configured browser origin. This proves connectivity only. Database records, permissions and reliable automation are the next increments. The widget-test compiler was blocked by the machine policy, which I recorded rather than claiming a pass.”

Student walkthrough questions: Where is the URL configured? Why is a browser client unable to keep a secret? Why is CORS different from authentication? Which check proves a real API response, and which screenshot only shows source? What does a health response leave unverified? The student must answer personally; prepared notes do not demonstrate understanding.

## Resume point

After the student commits, verify actual history/status and remote before later edits. Next meaningful milestone: fresh dedicated XAMPP database setup, consultation/action-item schema and fictional roles/data, then server-side record permissions and workflow APIs. Read shared PROJECT-PROGRESS.md; do not continue archived instructions. Keep remaining code, automation, activity and evidence requirements pending until executed.
