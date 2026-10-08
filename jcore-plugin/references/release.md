# Release pipeline

## Contents

- How a release happens
- What the publish workflow does (and what it needs)
- One-time setup for a new plugin
- Simulating the release locally
- Debugging a release that didn't happen

## How a release happens

`.github/workflows/release.yml` (template in `templates/.github/workflows/release.yml`) has three jobs:

1. **check** runs on every PR and every push to `main`. It installs, runs `composer lint`, `pnpm lint:js`, `pnpm lint:css`, `pnpm build` and `pnpm i18n`. It then assembles `dist/<slug>` with `rsync --exclude-from=.distignore` after `composer install --no-dev`, and runs `WordPress/plugin-check-action` on that, excluding `vendor`. Plugin Check needs a valid `readme.txt`.
2. **release** runs only on push to `main`, after check. `foonly/foonver` with `command: auto` reads the commits since the last `v*` tag:
   - `BREAKING CHANGE:` or `type!:` → major
   - any `feat` → minor
   - anything else, including `chore`, `docs` and `ci` → patch

   It syncs the version into the files in `.foonver.toml` `version-sync` (the main PHP file and `readme.txt`; `package.json` is its own version file), writes the changelog into `readme.txt` under `== Changelog ==` (wordpress format), commits `vX.Y.Z [skip ci]`, tags and pushes. Its outputs (`is_bumped`, `version`, `release_notes`) feed the next job.
3. **publish** calls `JCO-Digital/jcore-update/.github/workflows/reusable-plugin-publish.yml@main` with `slug`, `version`, `release_notes`, `plugin_file`, `dist_repo` and `slack_channel`.

Consequences worth telling users:

- **Every merge to `main` ships to every site.** Merge through PRs; squash or write good subjects. The commit subject becomes the changelog line site admins see.
- The `[skip ci]` suffix on the release commit stops the workflow from triggering itself. Don't use `[skip ci]` on normal commits; they would never release.
- Merge commits (`Merge pull request #…`) are fine. foonver ignores what it can't parse and still bumps patch.

## What the publish workflow does (and what it needs)

Read `jcore-update/.github/workflows/reusable-plugin-publish.yml` for the source of truth. In order:

1. Checks out tag `v<version>`, sets up Node 24, pnpm (from `packageManager`) and PHP 8.2 with composer and wp-cli.
2. Runs **`make ci`**. The plugin's Makefile must have a `ci` target that installs (`composer install --no-dev`), builds and runs i18n. That's why the Makefile exists.
3. **Scopes jcore-update** if `vendor/jcodigital/jcore-update` exists. php-scoper prefixes the library's namespace (`JcoreUpdate_1_7_1\Jcore\Update\…`) and rewrites references in the plugin's own PHP files. It scans every `*.php` except `vendor/`, `tests/`, `node_modules/`, `.git/`, `release/` and `build/`. Then it remaps the PSR-4 prefix in `installed.json` and dumps the autoloader. Without scoping, two JCORE plugins with different library versions on one site would share whichever copy loaded first. The consequence for plugin code is that jcore-update classes should be referenced with `use` + `::class` or literal fully qualified names in scanned files, never assembled from strings, and never from `build/`.
4. **Zips.** It prefers `.zipexclude`, then `zip_exclude.txt`, then `.distignore`. With only `.distignore`, it rsyncs into `<slug>/` and zips that, so the archive has the usual top-level folder. Modern plugins delete `.zipexclude` and keep a single anchored `.distignore`.
5. **Reads metadata** by grepping `Requires at least:`, `Tested up to:` and `Requires PHP:` from the main plugin file. All three must be in the header.
6. **Creates the GitHub release** with the zip attached.
7. **POSTs to `https://update.jcore.fi/v1/versions`** with slug, version, the download URL, changelog and requirements. This uses the `UPDATE_API_KEY` secret.
8. **Pushes to the dist repo**, if `dist_repo` and `DIST_REPO_SSH_KEY` are set. It restores the unscoped sources, rsyncs everything except `vendor/` (honouring `.distignore`, but always keeping `composer.json`), writes `.distignore` as the dist repo's `.gitignore`, commits, tags and pushes. Composer-managed sites install from there, which is why `composer.json` must ship and `.distignore` keeps `/composer.json` commented out.
9. **Posts to Slack**, if `slack_channel` and `SLACK_TOKEN` are set.

## One-time setup for a new plugin

The user (not you) has to:

- Create `JCO-Digital/<slug>` on GitHub and add the secrets `UPDATE_API_KEY`, `DIST_REPO_SSH_KEY` and `SLACK_BOT_TOKEN`. Org-level secrets may already cover these, so ask.
- Create the dist repo `git@git.jcore.fi:/srv/git/<slug>.git`, or drop `dist_repo` from the workflow if the plugin won't be installed through Composer.
- Make sure the slug is known to update.jcore.fi if the service requires registration.
- Protect `main` so foonver's push works (it pushes with the workflow token, so the branch rules must allow it).

foonver reads the current version from `package.json` (`0.1.0` in the template) and bumps from there. A first merge containing a `feat` releases **0.2.0**; one with only fixes or chores releases 0.1.1. To preview any release, run `foonver auto --dry-run` locally. It lists the bump, every file and match it will sync, and whether it found the changelog marker.

## Simulating the release locally

What would ship:

```bash
composer install --no-dev --no-interaction --prefer-dist && pnpm build
rm -rf /tmp/dist && mkdir -p /tmp/dist/<slug>
rsync -a --exclude=.git/ --exclude=.distignore --exclude-from=.distignore ./ /tmp/dist/<slug>/
find /tmp/dist/<slug> -maxdepth 2 | sort
composer install   # restore dev tools afterwards
```

Expect the main file, `uninstall.php`, `readme.txt`, `composer.json`, `includes/`, `views/`, `build/`, `languages/`, `vendor/autoload.php`, `vendor/composer/` and `vendor/jcodigital/jcore-update/`. Nothing from `src/`, `node_modules/`, lockfiles, `package.json`, dotfiles, `README.md` or dev-only vendor packages (phpcs, wpcs) should appear. If any of those show up, `.distignore` is wrong or vendor still has dev deps.

## Debugging a release that didn't happen

| Symptom | Likely cause |
| --- | --- |
| No new tag after merge | check job failed (lint, build, Plugin Check), or the only commits since the last tag are foonver's own `[skip ci]` commits |
| Tag exists, no GitHub release | publish job failed. Look at `make ci` (missing target, pnpm/composer failure) or the scoping step |
| Release exists, sites don't see it | the POST to update.jcore.fi failed (`UPDATE_API_KEY`), or it's a major bump and sites are waiting for the admin to click "Allow upgrade to vN.x" |
| Fatal "Class Jcore\Update\… not found" on a site | a reference to the library that php-scoper couldn't rewrite (string-built class name, file under `build/`) |
| Update popup is empty or says "Plugin not found" | `readme.txt` missing or malformed. The library parses it for the popup |
| Wrong version in header after release | the main file's version line doesn't match `Version:` / `define( '…_VERSION', 'x' )` form, so version-sync missed it |
