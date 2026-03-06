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
- `GITHUB_TOKEN`
- `GITHUB_OWNER`
- `GITHUB_REPO`
- `GITHUB_BRANCH`
- `GITHUB_RAW_PREFIX` (optional)
- `GITHUB_CDN_PREFIX` (optional, recommended)

Mark sensitive values as encrypted/secret in Cloudflare UI when available.

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
