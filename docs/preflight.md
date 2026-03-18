# CloudBlog Preflight Checklist

Use this checklist before and after each production deployment.

## A. Environment & Secrets

- [ ] `ADMIN_PASSWORD` is set in Cloudflare project variables (Production).
- [ ] `GITHUB_TOKEN` is set and valid (fine-grained PAT recommended).
- [ ] `GITHUB_OWNER` / `GITHUB_REPO` / `GITHUB_BRANCH` match target repo.
- [ ] Cloudflare Pages is connected to the code repo, not the content repo.
- [ ] `GITHUB_REPO` points to the content repo, not the code repo.
- [ ] `GITHUB_BRANCH` has no hidden newline or extra spaces.
- [ ] Optional `GITHUB_CDN_PREFIX` is configured if CDN image links are required.
- [ ] `.env` is NOT committed to Git.

## B. GitHub Token Permissions

- [ ] Token can read repository contents.
- [ ] Token can write repository contents (`cloudblog/posts`, `cloudblog/pages`, `cloudblog/images`, `cloudblog/settings.json`).
- [ ] GitHub API requests include `User-Agent` header (already handled in code).

## C. Cloudflare Build/Deploy Settings

- [ ] Build command: `npm run build`
- [ ] Output directory: `dist`
- [ ] Correct framework target and runtime bindings are configured.
- [ ] If using session KV, `SESSION` binding exists in Wrangler/Cloudflare config.

## D. Pre-Release Functional Smoke Test

1. Admin login
- [ ] `/admin` login works with `ADMIN_PASSWORD`.

2. Health check
- [ ] `/api/admin/health` returns `{"ok": true, ...}`.

3. Content write path
- [ ] Create a test post in Admin and save successfully.
- [ ] Post file appears in GitHub repo under `cloudblog/posts/*.md`.
- [ ] Post appears on homepage and `/blog` (if published/scheduled reached time).

4. Image upload path
- [ ] Upload image in editor succeeds.
- [ ] Saved URL is valid and opens publicly.

5. Settings write path
- [ ] Update one setting and save successfully.
- [ ] `cloudblog/settings.json` is updated in GitHub.

## E. Post-Deploy Verification

- [ ] Homepage loads without `500`.
- [ ] `/admin/posts` loads and lists content.
- [ ] `/api/admin/posts` returns authorized data when logged in.
- [ ] Category page loads (e.g. `/category/<slug>`).
- [ ] No critical errors in Cloudflare runtime logs.

## F. Fast Rollback Plan

- [ ] Keep previous successful deployment version in Cloudflare Pages.
- [ ] If runtime failures occur, rollback to previous version first.
- [ ] Then fix in Git, redeploy, and retest section D.

## G. Typical Failure Signatures

- `GitHub list dir failed: 403`
  - Usually token permissions are insufficient OR malformed env variable.
- `Buffer is not defined`
  - Runtime-incompatible code path in edge environment.
- Login works but save is empty
  - API returned error; inspect Network response and Cloudflare logs.

## H. 5-Minute Debug Order

1. Check `/api/admin/health`.
2. Check Cloudflare env variables for typo/newline.
3. Re-test write with a minimal POST payload.
4. Verify file appears in GitHub repo.
5. Check Cloudflare logs and rollback if needed.
