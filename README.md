# CloudBlog

A lightweight blog CMS built with Astro + Cloudflare Pages + GitHub content storage.

## One-click Deploy

[![Deploy to Cloudflare](https://deploy.workers.cloudflare.com/button)](https://deploy.workers.cloudflare.com/?url=https://github.com/inyhow/cloudblog)

> If the button flow is unavailable in your account, use manual setup in `docs/deploy.md`.

## Features

- Astro-based frontend and Cloudflare Pages deployment
- GitHub repository as content store (posts, pages, images, settings)
- Admin panel with visual post editor
- Post workflow: draft/review/scheduled/published/recycle
- Category templates and customizable theme CSS
- Google Analytics / Search Console / AdSense config
- Role-based access control: admin / editor / author

## Quick Start

```bash
npm install
cp .env.example .env
npm run dev
```

Open: `http://localhost:4321/admin`

## Environment Variables

Core:

- `ADMIN_USERS_JSON` (recommended)
- `ADMIN_PASSWORD` (legacy fallback)
- `GITHUB_OWNER`
- `GITHUB_REPO`
- `GITHUB_BRANCH`
- `GITHUB_TOKEN`

Optional:

- `GITHUB_RAW_PREFIX`
- `GITHUB_CDN_PREFIX`

`ADMIN_USERS_JSON` example:

```json
[
  { "username": "owner", "password": "strong-pass-1", "role": "admin" },
  { "username": "editor1", "password": "strong-pass-2", "role": "editor" },
  { "username": "author1", "password": "strong-pass-3", "role": "author" }
]
```

## Documentation

- [Project Guide](./docs/project.md)
- [Deployment Guide](./docs/deploy.md)
- [Preflight Checklist](./docs/preflight.md)

## License

MIT
