# Migrating an older plugin

A checklist for bringing a pre-2026 JCORE plugin (old release action, committed `build/`, wp-env, no update client) up to the Turva conventions. It comes from the jcore-portti migration. Not every plugin needs every step; check what's there first.

## 0. Look before touching anything

- `git status`: note the user's uncommitted work and keep it out of your commits. If a file you're about to replace or delete has local edits, say so in the summary.
- Work on a branch (`chore/modernize-tooling`). The PR is how it reaches `main`, and reaching `main` releases it.
- Run `python3 ~/.claude/skills/jcore-plugin/scripts/scaffold.py . --name "…" --description "…" [--admin] [--blocks] --diff` to see which files are missing and how existing ones differ from the templates. Missing files can then be created by running it again without `--diff`. Existing files are never overwritten, so merge those by hand.
- Run the current linters to get a baseline, so you can tell new failures from old ones.

## 1. Release pipeline

- Delete `.github/workflows/push.yml` (conventional-changelog + `EndBug/add-and-commit` + `CasperWA/push-protected`), `pull.yml` (folded into the check job), `versionSync.js` and `.zipexclude`/`zip_exclude.txt`.
- Add `release.yml` from the template: `slug`, `plugin_file`, `dist_repo`. If the plugin has no `readme.txt` yet, either write one (preferred, since the update popup and Plugin Check need it) or leave out the Plugin Check step and say so.
- Add `.foonver.toml`. With `readme.txt`, use the wordpress changelog format. Otherwise use `changelog = true` into the existing `CHANGELOG.md`, whose older entries are in conventional-changelog format. foonver prepends below them. Suggest a `# Changelog` heading.
- Stop committing `build/`: `git rm -r --cached build`, add `/build/` to `.gitignore`, and drop `build/* merge=ours` from `.gitattributes`. The local `build/` stays on disk.
- `label.yml` (jcore-module-actions PR labeler) is harmless. Keep it unless the user wants it gone.

## 2. Update client

- `composer require jcodigital/jcore-update:^1.7`
- Load `vendor/autoload.php` if readable, and register the updater guarded by `class_exists( UpdateConfig::class )` (see `jcore-update.md`). In a plugin without a `Plugin` class, register it at file scope after the `jcore_plugins_loaded` filter, with a prefixed variable name (`$jcore_<word>_update_config`).
- Add `Tested up to:` (and `Plugin URI`) to the header, and align `Version:` with the other header fields.

## 3. Tooling

- `phpcs.xml` → `phpcs.xml.dist` with PHPCompatibilityWP, text domain, prefixes and the `build`/`render.php` exclusions. Add `lint`/`lint:fix` composer scripts, and drop the explicit `squizlabs/php_codesniffer` and `phpcsutils` requires (wpcs pulls them in).
- `package.json`: `private`, repository fields, `check`/`lint:php`/`i18n`/`playground` scripts, `@wordpress/scripts ^30.27`, the `typescript` pin. Drop `concurrently`/`watch` and the `env:*` scripts.
- Replace `.wp-env.json` with `.wp/blueprint.json` and a `playground` script.
- `.distignore`, `.editorconfig`, `pnpm-workspace.yaml` and the Makefile from the templates. Keep the plugin's pnpm major unless asked; with pnpm 10, write `onlyBuiltDependencies` instead of `allowBuilds`.
- Fix `composer.json` metadata. Old ones often say "JCORE Global Content plugin" for every plugin and `GPL-2.0-only` against a `GPL-2.0-or-later` header.
- Regenerate both lockfiles: `composer update`, then `pnpm install` (interactive prompts can be skipped with `CI=true pnpm install --no-frozen-lockfile`).

## 4. Let the linters find the bugs

Run `pnpm format`, `composer lint`, `pnpm lint:js` and `pnpm lint:css`. Typical findings in old JCORE code:

- **Wrong text domain** (`'jcore'` instead of `'jcore-<word>'`). The strings were never translatable. This is a real bug.
- **React hooks called conditionally** (`useBlockProps()` in two return branches). Hoist them.
- `useSelect` returning a new array/object every call (`|| []` inside the selector). Return the raw value and default it outside.
- Unused variables, `<label>` without a control, missing JSDoc `@param`, `...` instead of `…` in strings.
- `__experimental*` imports: keep them with an eslint-disable comment and a reason if no stable replacement exists.

Don't rename stored identifiers to satisfy a sniff; add a scoped `phpcs:ignore` with a reason instead.

## 5. Commit and PR

Two commits, so the history and the changelog read well:

1. `chore: align tooling and release pipeline with jcore-turva`: config, workflow, lockfiles, updater, untracking `build/`.
2. `fix: …`: the real bugs the linters found, plus the formatting in the same files.

Open the PR with a summary and a "before the first release" checklist: secrets, dist repo, changelog format, Plugin Check. Note that merging releases a new version (patch for chore/fix, minor if there's a feat).

## 6. Verify

`composer validate && pnpm check && pnpm build`, plus the dist simulation from `release.md`, and `foonver auto --dry-run` to confirm version-sync finds the header (and the define, if there is one).
