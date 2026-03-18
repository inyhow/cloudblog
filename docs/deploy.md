# Cloudflare Pages Deployment & Secrets

## 1) Do not commit secrets

Keep real credentials only in local `.env` and Cloudflare environment variables.

Ignored files include:

- `.env`
- `.env.local`
- `.env.development.local`
- `.env.test.local`
- `.env.production.local`

Only commit `.env.example`.

## 2) Configure Cloudflare Pages variables

In Cloudflare Dashboard:

1. Open your Pages project.
2. Go to `Settings` -> `Environment variables`.
3. Add these variables for both `Production` and `Preview` as needed:

- `ADMIN_PASSWORD`
- `ADMIN_USERS_JSON` (recommended, RBAC)
- `GITHUB_TOKEN`
- `GITHUB_OWNER`
- `GITHUB_REPO`
- `GITHUB_BRANCH`
- `GITHUB_RAW_PREFIX` (optional)
- `GITHUB_CDN_PREFIX` (optional, recommended)

Mark sensitive values as encrypted/secret in Cloudflare UI when available.

## Recommended Setup: Split Code Repo and Content Repo

For this project, the recommended production layout is:

- Code repo: `inyhow/cloudblog`
- Content repo: `inyhow/cloudblog_content`

Cloudflare Pages should be connected to the code repo only.
All runtime content operations should point to the content repo via the variables below.

Example values:

```env
GITHUB_OWNER=inyhow
GITHUB_REPO=cloudblog_content
GITHUB_BRANCH=main
GITHUB_RAW_PREFIX=https://raw.githubusercontent.com/inyhow/cloudblog_content/main
GITHUB_CDN_PREFIX=https://cdn.jsdelivr.net/gh/inyhow/cloudblog_content@main
```

This setup prevents article publishing from triggering full site rebuilds.

`ADMIN_USERS_JSON` format:

```json
[
  { "username": "owner", "password": "strong-pass-1", "role": "admin" },
  { "username": "editor1", "password": "strong-pass-2", "role": "editor" },
  { "username": "author1", "password": "strong-pass-3", "role": "author" }
]
```

If `ADMIN_USERS_JSON` is not set, system falls back to legacy `ADMIN_PASSWORD` with username `admin` and role `admin`.

## 3) Build settings

- Build command: `npm run build`
- Output directory: `dist`

## 4) Security checklist

- Use a dedicated GitHub token with minimal repo scope.
- Rotate `GITHUB_TOKEN` and `ADMIN_PASSWORD` regularly.
- Never expose server env vars in client code.
- If a secret was leaked, revoke immediately and issue a new one.

## 5) Preflight checklist

Before each release, run: [Preflight Checklist](./preflight.md)
