# AGENTS.md

Guidance for AI agents (and humans) working in this repository.

> **This is a demo repository.** All hostnames, IPs, usernames, and paths
> below are fake placeholders (RFC 5737 documentation IPs, example.com
> domains). Nothing here connects to a real server.

## What this is

A composerized WordPress site:

- **WordPress core** is a Composer dependency (`johnpbloch/wordpress-core`), installed into `cms/`. It is never committed and never edited.
- **Plugins and themes** come from [wpackagist](https://wpackagist.org) via Composer, installed into `wp-content/`. The only hand-authored theme is `wp-content/themes/demo-child/`.
- **Assets** (`demo-child/src/`) compile with webpack via **pnpm** into `demo-child/assets/dist/` (gitignored, built in CI).
- **Config** is environment-driven: `wp-config.php` reads `.env` via phpdotenv. No credentials are ever committed.

Local layout is a subdirectory install (core in `cms/`, `wp-content/` at root, front-controller `index.php`). The deploy pipeline reassembles this into a standard flat docroot before rsyncing — see `.github/workflows/deploy.yml`.

## Commands

| Task | Command |
|---|---|
| Install PHP deps (core + plugins) | `composer install` |
| Install JS deps | `pnpm install` |
| Build assets | `pnpm build` |
| Watch assets | `pnpm dev` |
| Lint JS/SCSS | `pnpm lint` |
| Lint PHP syntax | `find wp-content/themes/demo-child -name '*.php' -exec php -l {} \;` |

Use **pnpm**, never npm or yarn — `packageManager` is pinned and CI uses `--frozen-lockfile`.

## Branch → environment pipeline

| Branch | Environment | URL | Trigger |
|---|---|---|---|
| `develop` | staging | https://staging.example.com | push (automatic) |
| `main` | production | https://www.example.com | push (gated by the `production` GitHub environment) |

Flow: feature branch → PR into `develop` (CI must pass) → verify on staging → PR `develop` into `main` → production deploy.

Never push directly to `main`. Never fast-forward `develop` onto a commit already on `main` — that skips the staging deploy for those changes.

## Server access (FAKE — demo values)

```
Host demo-production
    HostName 203.0.113.10
    User deploy-prod
    Port 22
    IdentityFile ~/.ssh/demo_deploy_key

Host demo-staging
    HostName 203.0.113.20
    User deploy-staging
    Port 22
    IdentityFile ~/.ssh/demo_deploy_key_staging
```

- Production docroot: `/home/deploy-prod/www/example.com/public_html`
- Staging docroot: `/home/deploy-staging/www/staging.example.com/public_html`
- Staging sits behind HTTP basic auth: `demo` / `demo`

The real values in a live project live in GitHub Actions **secrets**
(`SSH_HOST`, `SSH_USER`, `SSH_PATH`, `SSH_PORT`, `SSH_PRIVATE_KEY` and the
`SSH_STAGING_*` equivalents), bound to the `production` and `staging`
GitHub environments — never in the repo.

## Secrets policy

- `.env`, `auth.json`, private keys: gitignored, and the deploy pipeline additionally strips them from the release payload.
- Every PR and every deploy runs **TruffleHog** (verified-credential scan) and CI also runs **Gitleaks** (pattern scan). A verified hit fails the build.
- If you find a real credential in the repo or its history: stop, do not push, rotate the credential first, then scrub history.

## QA testing

### Before opening a PR

1. `composer validate --strict` passes.
2. `php -l` clean on every authored PHP file.
3. `pnpm lint && pnpm build` succeeds — a broken webpack build fails the deploy.
4. Spin up locally (Lando/wp-env/valet), copy `.env.example` → `.env`, fill salts, verify the site boots with no PHP notices in `wp-content/debug.log`.

### After a staging deploy (before promoting to main)

1. **Deploy check**: the `Deploy staging` run is green; the rsync step completed.
2. **Smoke over SSH** (Cloudflare-style bot protection can 403 curl, so verify on the box, not over HTTP):
   ```
   ssh demo-staging "cd ~/www/staging.example.com/public_html && wp core verify-checksums && wp option get home"
   ```
3. **Browser QA** (real browser, staging URL):
   - Homepage renders with styles (confirms `assets/dist/` shipped).
   - Log in to `/wp-admin`; no plugin/theme errors on the dashboard.
   - One representative page of each type: post, page, archive, 404.
   - Any form submits successfully.
4. **Regression check on excluded paths**: uploads still present (rsync `--delete` must never touch `wp-content/uploads/`), server `wp-config.php` untouched.
5. **Accessibility spot check**: `npx @axe-core/cli https://staging.example.com --tags wcag2a,wcag2aa`.

### After a production deploy

Repeat the SSH smoke check against `demo-production`, then browser-verify the homepage and one recent post. If anything is wrong, redeploy the previous commit (`git revert` + push to `main`) — the pipeline is the rollback mechanism; never hot-edit files on the server.

## Hard rules for agents

- Never commit `.env`, `auth.json`, keys, or anything under `cms/` or `wp-content/vendor/`.
- Never edit Composer-installed plugin/theme code; changes go in `demo-child` or a custom plugin.
- Never edit files directly on a server; every change flows through git and the pipeline.
- Deployment workflow changes: modify `deploy.yml` (shared) rather than duplicating steps into the per-environment callers.
