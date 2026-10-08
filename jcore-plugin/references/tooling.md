# Tooling files

Every config file a JCORE plugin carries, what it's for, and the details that bite. Templates for all of them are in `templates/`. Render them with `scripts/scaffold.py` instead of writing them by hand.

| File | Purpose | Notes |
| --- | --- | --- |
| `package.json` | Scripts and JS dev deps | `private: true`. Version is foonver's source of truth, never edited by hand. `packageManager: pnpm@11.x` (pnpm 10 auto-switches to it). Pin `typescript` to `~6.0.0`: TypeScript 7 crashes the `@typescript-eslint` bundled with `@wordpress/scripts` 30, and a fresh install without the pin picks up 7. `--blocks-manifest` on build/start only when the plugin has blocks. `lint:css` has `--allow-empty-input` for plugins without SCSS. `make-json` is `pnpm --package=@wp-blocks/make-pot@1.6.3 dlx makejson …`, with `--package` before `dlx`. Turva's `pnpm dlx --package=…` form only works on pnpm 11; pnpm 10 reads the flag as the package name. |
| `pnpm-workspace.yaml` | `allowBuilds` for the deps that need install scripts | pnpm 11 syntax. Older plugins still on pnpm 10 use `onlyBuiltDependencies:` (a list) instead. |
| `composer.json` | `jcodigital/jcore-update` + lint deps | `require` has `php >=8.2` and jcore-update. `require-dev` has wpcs, phpcompatibility-wp and the installer plugin. Scripts are `lint` (phpcs) and `lint:fix` (phpcbf). It ships, because the dist repo is a Composer package. |
| `phpcs.xml.dist` | WordPress-Docs + WordPress-Extra (no Yoda) + PHPCompatibilityWP 8.2+ | Text domain and prefix sniffs configured with the plugin's names. `*/render.php` excluded from the prefix sniff. Excludes `vendor`, `node_modules`, `build`, `release`. Older plugins have `phpcs.xml`; rename it to `.dist`. |
| `Makefile` | `make ci` for the publish workflow, plus local shortcuts | `ci: install build i18n`. `install` uses `composer install --no-dev` because it builds what ships. Run `composer install` yourself for linting. |
| `.distignore` | The one list of what doesn't ship | Anchored gitignore syntax (`/src/`, not `src`), because the publish workflow uses it for rsync and as the dist repo's `.gitignore`. Unanchored patterns would also strip `vendor/*/src`. Keep `/composer.json` commented out. |
| `.gitattributes` | LF endings, `export-ignore` for dev files, `-diff` for `.po`/`.pot` | Mirrors `.distignore` for `git archive`. |
| `.gitignore` | `/build/`, `/vendor/`, `/node_modules/`, `/release/`, `/dist/`, generated `languages/*.{pot,mo,json,l10n.php}` | `.po` files are committed. |
| `.editorconfig` | Tabs for code, 2 spaces for json/yml/md | Matches WordPress coding standards. |
| `.foonver.toml` | Release config | `version-sync = ["<slug>.php", "readme.txt"]`, `changelog-format = "wordpress"`, `changelog-file = "readme.txt"`, `changelog-start = "== Changelog =="`. Plugins without `readme.txt` (older ones) use plain `changelog = true` into `CHANGELOG.md`. |
| `.wp/blueprint.json` | WordPress Playground for `pnpm playground` | Logs in as admin, installs Plugin Check, activates the plugin, lands on its screen. Each plugin has its own `--port` so several can run at once. Replaces `.wp-env.json`. |
| `.github/workflows/release.yml` | check → release → publish | See `release.md`. |
| `webpack.config.js` | Only when there are non-block entries | Admin app: `entry: { admin: './src/admin/index.js' }`, plus `...defaultConfig.entry()` if there are blocks too. Older block plugins also alias `media$` to `@jcodigital/jcore-media` breakpoints; keep that if the SCSS imports `media`. |
| `readme.txt` | wordpress.org-format readme | Shipped. It feeds the update popup (jcore-update 1.7+), Plugin Check and foonver's changelog. Header `Stable tag` is synced. Keep the "External services" section declaring update.jcore.fi. |
| `README.md` | Developer docs: requirements, scripts, layout, REST routes, DB tables, filters, releasing | Not shipped. |
| `LICENSE` | GPL-2.0 text | Copy from jcore-turva. |
| main plugin file header | `Plugin URI`, `Version`, `Requires at least: 6.7`, `Tested up to`, `Requires PHP: 8.2`, `Author: J&Co Digital Oy`, `Text Domain`, `Domain Path: /languages` | The publish workflow greps the three requirement lines, so they must all be present. |

## Dependency versions

`@wordpress/scripts` stays on the 30.x line that Turva uses (`^30.27.0`) unless the user asks for a major upgrade. Newer majors change the ESLint config and need their own migration. Check Turva's `package.json` for the current pins before bumping anything; it is the reference.

## Commands CI runs

```bash
composer validate
composer install && pnpm install --frozen-lockfile
composer lint && pnpm lint:js && pnpm lint:css
pnpm build && pnpm i18n
```

`pnpm check` runs the three linters. After changing `package.json`, run `pnpm install` (without `--frozen-lockfile`) and commit the lockfile, or CI fails on the outdated lock.
