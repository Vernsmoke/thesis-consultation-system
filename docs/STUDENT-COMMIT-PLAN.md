# Student commit and weekly version plan

Run these in PowerShell after reviewing the completed working tree. The assistant has created no commits/tags/pushes and has not staged files. Current branch main, existing origin https://github.com/Vernsmoke/thesis-consultation-week13.git. Runtime base cfbacef. No local or live remote tags were present when inspected; check again immediately before tagging.

The reported teacher rule requires identifiable weekly versions. Annotated `week-13` is our proposed mechanism/name, not a confirmed teacher requirement. The existing repository name remains unchanged; the optional clearer name is deferred.

Verification applies to the completed revision working tree and unchanged runtime:64 backend tests/325 assertions, Flutter analysis clean, matching existing checkpoint08 compiled-web hash, seven retained effects and new actual read-only/invalid-input/manual captures. It is not a claim each intermediate documentation commit was independently tested. Review [verification and limitations](REVISION-VERIFICATION.md).

## Group1 repository/version transition plan

Changes: document the one-repository rule, preserved Week14 state and guarded student commands. Defense: a weekly tag names an immutable-by-convention source checkpoint while main continues; the existing Week14 work is reconciled later, not merged into Week13. Read the transition/this plan; these are documentation-only changes with no independent runtime test claim.

```powershell
Set-Location 'C:\Users\Laure\Documents\ChatGPT\IPT\week-13'
git status
git branch --show-current
git remote -v
git add -- docs/REPOSITORY-TRANSITION.md docs/STUDENT-COMMIT-PLAN.md
git status
git diff --staged --stat
git diff --staged
git commit -m "Document continuing repository and weekly version transition"
```

Press Q if a pager opens. Review all staged names; do not include unrelated files. Root AGENTS.md/PROJECT-PROGRESS.md/PROJECT-CHANGE-PLAN.md are shared internal records outside this repository and are not staged by these commands.

## Group2 user manual and audited Week13 evidence

Changes: numbered manual/actual images, current README/checklist/report/API status, requirement audit, fresh regression/analysis/read-only evidence and indexed metadata. Defense: instructions name actual role controls and results, date old screenshots honestly, and distinguish a queued recovery/receiver receipt/human approval. No Week14 cryptography/TLS files or application code are changed.

```powershell
Set-Location 'C:\Users\Laure\Documents\ChatGPT\IPT\week-13'
git add -- README.md CHANGELOG.md SUBMISSION-CHECKLIST.md docs/USER-MANUAL.md docs/REVISION-VERIFICATION.md docs/WEEK-13-REPORT.md docs/API-CONTRACT.md docs/FINAL-REVIEW.md docs/ISSUES.md docs/LAB-LOG.md evidence/README.md evidence/screenshots/application/manual evidence/test-results/revision-app-before.json evidence/test-results/revision-app-before-authorized.json evidence/test-results/revision-app-after.json evidence/test-results/revision-backend-regression.txt evidence/test-results/revision-flutter-analyze.txt evidence/test-results/revision-browser-checks.json evidence/test-results/revision-verification.json evidence/test-results/revision-content-audit.json evidence/api-tests/revision-retained-effects.json evidence/api-tests/revision-retained-effects-raw.json
git status
git diff --staged --stat
git diff --staged
git diff --staged --name-only
git commit -m "Add illustrated user manual and audit Week 13 automation evidence"
git status
git log -3 --oneline
```

Stage only the listed changes; private .env files/receipt ledger/dependencies/builds, root modules/archive/planning and local reflection/Word/ZIP artifacts must remain excluded. If a listed path is absent or another change appears, stop and inspect instead of blindly adding everything. Do not manufacture an empty commit if you already committed a group.

## Proposed week13 tag and pushes

Execute the following as ONE block. It stops on a dirty tree, wrong branch, existing local OR remote tag, failed remote query or failed Git operation. Existing tags are never moved/overwritten. If the name already exists, inspect it and choose an explicitly reviewed new revision name instead; do not add -f or delete an existing tag. This is student execution only.

```powershell
& {
    Set-Location 'C:\Users\Laure\Documents\ChatGPT\IPT\week-13'
    if ((git branch --show-current) -ne 'main') { throw 'Inspect the current branch before tagging.' }
    if (git status --porcelain) { throw 'Review and commit remaining files before tagging.' }
    $weeklyTag = 'week-13'
    $localTag = @(git tag --list $weeklyTag)
    if ($LASTEXITCODE -ne 0) { throw 'Local tag query failed.' }
    if ($localTag.Count -gt 0) { throw 'Tag exists locally. Leave it unchanged and inspect it.' }
    $remoteTag = @(git ls-remote --tags origin "refs/tags/$weeklyTag")
    if ($LASTEXITCODE -ne 0) { throw 'Remote tag query failed. Do not infer the tag is absent.' }
    if ($remoteTag.Count -gt 0) { throw 'Tag exists remotely. Leave it unchanged and inspect it.' }
    git tag -a $weeklyTag -m "Week 13 automation submission with verified illustrated user manual"
    if ($LASTEXITCODE -ne 0) { throw 'Tag creation failed.' }
    git show --no-patch --format=fuller $weeklyTag
    git push origin main
    if ($LASTEXITCODE -ne 0) { throw 'Branch push failed. Inspect before continuing.' }
    git push origin "refs/tags/$weeklyTag"
    if ($LASTEXITCODE -ne 0) { throw 'Tag push failed. Inspect before retrying; do not recreate or force it.' }
    git ls-remote --tags origin "refs/tags/$weeklyTag" "refs/tags/$weeklyTag^{}"
    git status
}
```

The peeled remote tag commit (`^{}`) should equal `git rev-parse HEAD`. A successful branch push alone does not publish a tag. No PR, repository rename, remote change, folder move or new weekly repository is required by this update.

## Revised source ZIP after the reviewed tag exists

The previous personalized Word/source bundle remains a genuine historical cfbacef artifact; it does not contain the new manual. Preserve it. After the student tag above is reviewed, optionally produce a separate revised source archive for a file-based submission. This is an organization choice, not a claimed mandatory teacher ZIP format. This command refuses to overwrite an existing output.

```powershell
& {
    Set-Location 'C:\Users\Laure\Documents\ChatGPT\IPT\week-13'
    $sourceZip = 'C:\Users\Laure\Documents\ChatGPT\IPT\submissions\week-13\Week-13-Revised-Source.zip'
    if (Test-Path -LiteralPath $sourceZip) { throw 'Output exists. Preserve it and choose a reviewed revision filename.' }
    git rev-parse --verify 'refs/tags/week-13^{commit}'
    if ($LASTEXITCODE -ne 0) { throw 'Reviewed week-13 tag is required before archive creation.' }
    git -c core.autocrlf=false archive --format=zip --prefix=thesis-consultation-week13/ "--output=$sourceZip" week-13
    if ($LASTEXITCODE -ne 0) { throw 'Archive failed.' }
    Get-FileHash -Algorithm SHA256 -LiteralPath $sourceZip
}
```

Review the final source/tag, personal reflection/disclosure and exact upload format/destination before actual submission. Report the new final commit/tag to the next chat. Later Week14 reconciliation requires its own explicit student request and updated manual/evidence; the separate unfinished work remains untouched now.
