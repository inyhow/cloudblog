# Project Guide

CloudBlog is designed for low-cost, Git-centric publishing with a practical admin UI.

## Architecture

- **Frontend**: Astro + Tailwind
- **Runtime**: Cloudflare Pages (server mode)
- **Storage**: GitHub repository via API
- **Comments**: Giscus (optional)

## Recommended Production Topology

- **Code repo**: deploy source for Cloudflare Pages
- **Content repo**: posts, pages, images, settings

Recommended for this project:

- Code repo: `inyhow/cloudblog`
- Content repo: `inyhow/cloudblog_content`

This keeps publishing operations out of the deploy pipeline and makes the site cheaper to operate at scale.
The code repo should not keep runtime content files under `cloudblog/`; those belong in the content repo only.

## Content Storage Layout

- `cloudblog/posts/*.md` - blog posts
- `cloudblog/pages/*.md` - custom pages
- `cloudblog/images/*` - uploaded images
- `cloudblog/settings.json` - site settings
- `cloudblog/theme.css` - custom theme CSS
- `cloudblog/templates/*` - template files

## Admin Modules

- Overview: core KPI snapshot
- Articles: CRUD + workflow + revisions
- Pages: CRUD for static pages
- Templates: template file editor
- Media: uploaded asset list
- Settings: site config, SEO, categories, theme, advanced

## Roles (RBAC)

- `admin`: full access, including settings/template/theme/health/ops
- `editor`: content management + revisions (no system settings)
- `author`: content create/edit and media upload

## Workflow

- `draft` -> `review` -> `published`
- `scheduled` becomes public when scheduled time is reached
- `deleted` is recycle-bin state

## Recommended Release Process

1. Validate env vars and token permissions
2. Run local build: `npm run build`
3. Deploy to preview
4. Run `docs/preflight.md`
5. Promote to production

## Commercialization Notes

For a compact but business-ready setup, prioritize:

1. RBAC and auditability
2. Backup + alerting
3. SEO health checks
4. Analytics/ads reporting
