# wp-composer-demo

A demo of a fully **composerized WordPress** project with a **webpack + pnpm** asset pipeline and **CI/CD with secret scanning** to staging and production.

Everything environment-specific in this repo (hosts, IPs, users) is a **fake placeholder** — see [AGENTS.md](AGENTS.md).

## Highlights

- **WordPress core as a dependency** — `johnpbloch/wordpress-core` installs into `cms/`; plugins/themes come from [wpackagist](https://wpackagist.org). Nothing vendored, nothing hand-copied.
- **Environment-driven config** — `wp-config.php` reads `.env` (phpdotenv). Zero credentials in git.
- **pnpm + webpack** — theme SCSS/JS in `wp-content/themes/demo-child/src/`, compiled to `assets/dist/` (gitignored; built in CI).
- **Reusable deploy pipeline** — `develop` → staging, `main` → production. Both call one shared `deploy.yml`, so build steps can't drift between environments. Deploys rsync a reassembled flat-docroot release and are gated by GitHub environments.
- **Secret scanning** — TruffleHog (verified credentials) on every PR *and* every deploy, plus Gitleaks pattern scanning in CI.

## Quick start

```bash
composer install          # WordPress core → cms/, plugins/themes → wp-content/
pnpm install
pnpm build                # theme assets → wp-content/themes/demo-child/assets/dist/
cp .env.example .env      # then fill in DB creds + salts
```

Point a local PHP server (Lando, wp-env, Valet…) at the repo root.

## Layout

```
├── cms/                  WordPress core (Composer-installed, gitignored)
├── wp-content/
│   ├── themes/demo-child/   the one hand-authored theme (src → webpack → assets/dist)
│   ├── plugins/             Composer-installed (gitignored)
│   └── vendor/              Composer vendor dir (gitignored)
├── wp-config.php         env-driven config (no secrets)
├── index.php             front controller → cms/
├── webpack.config.js
└── .github/workflows/    ci.yml + deploy-{staging,production}.yml → deploy.yml
```

## CI/CD

| Workflow | Trigger | What it does |
|---|---|---|
| `ci.yml` | every PR | PHP lint, composer validate, pnpm lint + build, TruffleHog + Gitleaks |
| `deploy-staging.yml` | push to `develop` | shared pipeline → staging |
| `deploy-production.yml` | push to `main` | shared pipeline → production (environment-gated) |
| `deploy.yml` | called by the two above | secret scan → build release → rsync deploy |

See [AGENTS.md](AGENTS.md) for the full QA checklist and operating rules.
