# wp-composer-demo

A Composer-managed, version-controlled WordPress install — a public **demo** of the setup used on production sites: WordPress core and plugins via Composer, a webpack + **pnpm** asset pipeline, local development on [Lando](https://lando.dev), and GitHub Actions CI/CD to staging and production with secret scanning.

> Every hostname, IP, username, and credential in this repo is a **fake placeholder** (RFC 5737 IPs, example.com domains). See [AGENTS.md](AGENTS.md) for agent/QA operating rules.

## Project layout

WordPress core is installed **into a `cms/` subdirectory** while the site is served from the repo root and `wp-content` lives at the root. This keeps core cleanly separated from project code.

```
.
├── index.php            # front controller → requires cms/wp-blog-header.php
├── wp-config.php        # reads all config from .env (no secrets committed)
├── cms/                 # WordPress core — installed by Composer (gitignored)
├── wp-content/          # themes, plugins, mu-plugins, and vendor/ (Composer libraries)
│   ├── themes/demo-child/   # the site theme (child of Twenty Twenty-Five); src → webpack → assets/dist
│   ├── plugins/             # composer-managed (gitignored) + any git-committed premium plugins
│   └── vendor/              # Composer vendor dir (gitignored, rebuilt on install)
├── composer.json        # WP core → cms/, plugins/themes → wp-content/ (see installer-paths)
├── package.json         # pnpm + webpack asset pipeline
├── webpack.config.js
├── .lando.yml           # local dev stack (nginx + PHP 8.2, MySQL, Node 20, MailHog)
└── .github/workflows/   # CI + deploy pipeline
```

### Composer-powered WordPress

Everything available from the [WordPress.org repo](https://wordpress.org/extend) is pulled in via Composer (through [wpackagist](https://wpackagist.org)). On a real site, premium plugins come from their vendors' Composer repositories; premium plugins with **no** Composer channel are committed to git directly (see [AGENTS.md](AGENTS.md#plugin-management-three-sources-three-update-paths)). WordPress core installs to `cms/` via `johnpbloch/wordpress-core`.

Composer repositories a production version of this setup typically uses:

| Repository | URL | Provides |
|---|---|---|
| wpackagist | `https://wpackagist.org` | free .org plugins/themes (the only one this demo needs) |
| gravity | `https://composer.gravity.io` | Gravity Forms |
| my-yoast | `https://my.yoast.com/packages/` | Yoast SEO Premium |
| wp-migrate | `https://composer.deliciousbrains.com` | WP Migrate DB Pro |

`config.vendor-dir` is set to `wp-content/vendor`, and `extra.installer-paths` routes packages by type: `wordpress-plugin` → `wp-content/plugins/{$name}`, `wordpress-theme` → `wp-content/themes/{$name}`, `wordpress-muplugin` → `wp-content/mu-plugins/{$name}`.

## Local development with Lando

Lando is a Docker-based local dev environment. Install it first: <https://docs.lando.dev/getting-started/installation.html>, and make sure Docker Desktop is running.

The Lando stack (`.lando.yml`): **nginx + PHP 8.2**, **MySQL**, **Node 20**, and **MailHog** (catches all outgoing mail — essential for form QA). Local URL: `https://wp-composer-demo.lndo.site` (mail UI at `https://mail.wp-composer-demo.lndo.site`).

### First-time setup

```bash
# 1. Clone into a new directory (the trailing `.` matters).
mkdir -p ~/Sites/wp-composer-demo && cd ~/Sites/wp-composer-demo
git clone -b develop git@github.com:mgratch/wp-composer-demo.git .

# 2. Create your local env file and fill it in (see "Environment variables" below).
cp .env.example .env
#    - generate fresh salts: https://api.wordpress.org/secret-key/1.1/salt/
#    - the DB_* defaults already match the Lando database service

# 3. (Real sites only) copy auth.json.example → auth.json and fill in the
#    premium-repo credentials. This demo uses only public repositories, so
#    the step is optional here.

# 4. Start Lando.
lando start

# 5. Install WordPress core (→ cms/), plugins, and themes.
lando composer install --prefer-dist

# 6. Build theme assets (webpack via pnpm).
lando pnpm install
lando pnpm build

# 7. Fresh, empty install:
lando wp core install \
  --url=https://wp-composer-demo.lndo.site \
  --title="WP Composer Demo" \
  --admin_user=admin --admin_password=changeme --admin_email=admin@example.com \
  --skip-email
lando wp theme activate demo-child
```

### auth.json

Composer needs HTTP-basic credentials for premium repositories. Copy [`auth.json.example`](auth.json.example) to `auth.json` in the repo root and fill in real values (it is **gitignored** — never commit it; the secret-scanning workflows exist to catch exactly this class of mistake).

In CI, the whole file is written from the `AUTHJSON` GitHub secret, and the deploy pipeline strips it from the release payload so it never reaches a docroot.

## Configuration & constants

`wp-config.php` is committed and contains **no secrets** — it reads everything from `.env` via [vlucas/phpdotenv](https://github.com/vlucas/phpdotenv), autoloaded from `wp-content/vendor/autoload.php`. The following are **required** (WordPress will not boot without them): `WP_HOME`, `WP_DIR`, the `DB_*` values, and the eight auth salts.

| Constant | Source (`.env`) | Notes |
|---|---|---|
| `WP_HOME` | `WP_HOME` | public site root, e.g. `https://wp-composer-demo.lndo.site` |
| `WP_SITEURL` | `WP_HOME` + `/` + `WP_DIR` | WordPress core URL — the `cms/` subdirectory |
| `WP_CONTENT_URL` | `WP_HOME` + `/wp-content` | content served from the root, not from `cms/` |
| `WP_CONTENT_DIR` | `__DIR__ . '/wp-content'` | content directory at the repo root |
| `DB_*` / `$table_prefix` | same-named vars | database connection |
| `AUTH_KEY` … `NONCE_SALT` (8) | same-named vars | authentication keys & salts — generate fresh per environment |
| `WP_DEBUG` `WP_DEBUG_LOG` `WP_DEBUG_DISPLAY` `SCRIPT_DEBUG` `DISALLOW_FILE_EDIT` | same-named vars | booleans |
| `WP_ENVIRONMENT_TYPE` | `WP_ENVIRONMENT_TYPE` | `local` / `staging` / `production` |
| `ABSPATH` | `__DIR__ . '/'` | repo root |

`.env` is not committed; [`.env.example`](.env.example) is the template.

## Assets & build

Theme SCSS/JS live in `wp-content/themes/demo-child/src/` and compile with **webpack** to `demo-child/assets/dist/` (gitignored — CI builds it fresh for every release):

```bash
pnpm install
pnpm build     # production build
pnpm dev       # watch mode
pnpm lint      # eslint + stylelint
```

Use **pnpm** only — `packageManager` is pinned in `package.json` and CI installs with `--frozen-lockfile`, so an npm- or yarn-generated lockfile will fail the build.

## Deployments

Deployment is fully automated with GitHub Actions. Four workflow files in `.github/workflows/`:

| File | Trigger | Result |
|---|---|---|
| `ci.yml` | pull request to `develop` or `main` | PHP lint, composer validate, pnpm lint + build, TruffleHog + Gitleaks |
| `deploy-staging.yml` | push to `develop` | deploy to **staging** (`https://staging.example.com`) |
| `deploy-production.yml` | push to `main` | deploy to **production** (`https://www.example.com`) |
| `deploy.yml` | called by the two above | the shared build-and-deploy pipeline |

### How it works

`deploy-staging.yml` and `deploy-production.yml` are thin callers — they only choose the environment, the URL, and the SSH secrets, then hand off to the reusable `deploy.yml`. Keeping the build logic in one place means staging and production can't drift apart. (The environment is passed as an *input*, so the GitHub environment being gated and the secrets being used can never disagree — a class of bug where a "production" workflow silently runs under the staging environment.)

The reusable pipeline runs three jobs:

1. **Secrets scan** — TruffleHog fails the run if a verified secret is committed.
2. **Build** — sets up PHP 8.2 + Node 20 + pnpm, runs `composer install --no-dev --optimize-autoloader`, builds theme assets, strips build cruft (`node_modules`, `src/`, tests, any stray `.env`/keys/`auth.json`), then reassembles the subdirectory layout into a **standard flat WordPress docroot** inside `cms/`.
3. **Deploy** — `rsync -az --delete` of that docroot to the server, excluding `uploads/`, caches, `.env`, `auth.json`, the server's own `wp-config.php`, and server-managed drop-ins. (In this demo, the rsync step skips itself with a notice because no real server or SSH secrets exist.)

Only one deploy runs per environment at a time (`concurrency`), so two pushes can't race into the same docroot.

### Required GitHub configuration (on a real site)

**Secrets** (Settings → Secrets and variables → Actions):

| Secret | Used by |
|---|---|
| `SSH_HOST` `SSH_USER` `SSH_PATH` `SSH_PORT` `SSH_PRIVATE_KEY` | production connection |
| `SSH_STAGING_HOST` `SSH_STAGING_USER` `SSH_STAGING_PATH` `SSH_STAGING_PORT` `SSH_STAGING_PRIVATE_KEY` | staging connection |
| `AUTHJSON` | contents of `auth.json` for `composer install` |

**Environments** (Settings → Environments): create `staging` and `production`, and add a **required reviewer** to `production` so a push to `main` waits for approval before deploying.

This repo also has GitHub-native **secret scanning + push protection** enabled (Settings → Code security).

### Branch flow

`develop` → auto-deploys to staging. Merge `develop` → `main` (via PR) → deploys to production after review. Verify on staging before promoting — the full QA checklist (forms, logged-in flows via WP-CLI magic links, staged dependency updates) lives in [AGENTS.md](AGENTS.md).

## Lando commands

| Command | Description |
|---|---|
| `lando start` / `stop` / `restart` / `rebuild` | manage the environment |
| `lando composer <cmd>` | run Composer (installs core to `cms/`, plugins/themes to `wp-content/`) |
| `lando wp <cmd>` | WP-CLI |
| `lando pnpm <cmd>` | pnpm (via corepack in the Node 20 service) |
| `lando logs` | view logs |

MailHog catches every email the site sends — open `https://mail.wp-composer-demo.lndo.site` to verify form notifications during QA without spamming anyone.
