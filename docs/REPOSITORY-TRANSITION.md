# One repository and weekly submission versions

Revised teacher rules reported 30 October 2026: one continuing repository for Weeks13–18, identifiable weekly submission versions, and a repository user manual with numbered procedures and actual application screenshots for major functions. Tags are our proposed mechanism, not a reported teacher mandate.

## Inspected starting point

- Week13: main at cfbacefdef66950d2a2fd71c705258d0099f2e9c; clean tree; eight genuine commits; origin https://github.com/Vernsmoke/thesis-consultation-week13.git. No local tags at initial inspection; live remote tags must also be checked before student tagging.
- Week14: separate Git folder with NO HEAD/commits, no index entries and all project files untracked. Existing origin https://github.com/Vernsmoke/thesis-consultation-week14.git. It contains AES-GCM envelope/replay/HTTPS middleware, local TLS tooling, separate databases/configuration and evidence. It has not been incorporated into Week13. Earlier Week14 completion/browser limitations remain in shared progress.
- Public source/Git inventory saved privately before changes to verify Week14 preservation. This update does not move folders, delete repositories, rename GitHub, change remotes, rewrite history or import security code/settings/evidence.

## Proposed transition

1. Complete this Week13-only audit/manual revision in the existing week-13 checkout. Commit related changes in reviewable groups. Name the tested runtime source/build separately from document revisions.
2. Student creates an annotated week-13 tag at the final reviewed commit, only after checking local and live remote refs. Push the branch and that specific tag. Never force/move a weekly tag. If a submitted version needs correction later, retain it and choose a distinct reviewed revision tag.
3. Continue in the SAME repository for later weeks. Each subsequent student-opened chat checks the previous tag and reads its full module. No new weekly remotes or fresh-history copies.
4. Only after Week13 is frozen and Week14 reconciliation is requested: compare preserved Week14 against its declared Week13 baseline cfbacef. Bring in reviewed Week14-only changes deliberately, merge any manual changes, retain original evidence labels, regenerate evidence for changed behavior, and verify isolation/secrets. Do not copy its .git, private .env, keys, ledgers, generated dependencies or trust settings blindly. No merge/reconciliation is performed by this plan.
5. Preserve existing separate Week14 folder/remote until the student authorizes any later cleanup. Optional repository rename thesis-consultation-system is deferred; existing URL remains the continuing proposal.

## What a tag preserves

A commit records source/documents in Git. An annotated tag is a named pointer with a message to that specific commit. Opening week-13 on GitHub or downloading its source gives that version even after main advances to Week14. It does not contain ignored credentials, private database state, real human acceptance or local Word artifacts. Keep weekly evidence in the tagged tree and keep local submission packages identified by source commit. Pushing main alone does not publish a tag.

## Evidence discipline

Historical screenshots are retained as originally captured, including old folder/branch labels. A matching unchanged source hash permits identifying them as historical evidence of the same runtime code; it does not change their capture date. New/changed behavior requires new execution evidence. Current manual contains a per-function screenshot map and links to the revision verification record. HTTPS/encryption/replay work belongs to Week14, not week-13.
