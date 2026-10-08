---
name: jcore-plugin
description: Conventions and tooling for J&Co Digital's "jcore-*" WordPress plugins (jcore-turva, jcore-maailma, jcore-portti and siblings), which ship through the jcore-update library and update.jcore.fi rather than wordpress.org. Use this skill whenever working in a repo or directory named jcore-*, when the user mentions JCORE, J&Co, jco.fi, jcore-update, foonver, update.jcore.fi or the dist repo, or asks to scaffold a new JCORE plugin, bring an older JCORE plugin up to date ("make it like turva"), add a feature, REST route, admin screen, block or database table to one, cut a release, or debug why a JCORE plugin did not publish or update — even if they don't name the conventions explicitly.
---

# JCORE plugins

J&Co Digital's WordPress plugins share one layout, one toolchain and one release pipeline. **jcore-turva is the reference implementation**: when this skill and Turva disagree, Turva is newer — follow it and tell the user the skill is out of date. Sibling repos usually sit next to each other (e.g. `~/Projects/WordPress/Plugins/jcore-*`) and the update library at `../../Libraries/jcore-update`; read them when you need a real example rather than guessing.

What makes these plugins different from a generic WordPress plugin:

- **Distribution** is private: a release zip on GitHub, registered with `https://update.jcore.fi/v1`, and a Composer "dist" repo on `git.jcore.fi`. Sites update through the bundled `jcodigital/jcore-update` client. Nothing goes to wordpress.org, but the code still follows its guidelines and passes Plugin Check.
- **Versions are never edited by hand.** foonver derives them from Conventional Commit messages on `main` and writes them into the plugin header, `readme.txt` and `package.json`.
- **Build output is not committed.** `build/` and `vendor/` are produced in CI and shipped in the zip.

## Pick the job

| The user wants to… | Do this |
| --- | --- |
| Start a new plugin | [Scaffold](#scaffold-a-new-plugin) |
| Bring an old plugin up to date | [Migrate](#migrate-an-older-plugin) — then read `references/migration.md` |
| Add a feature to an existing one | Follow `references/php-conventions.md` (PHP) and the JS section below |
| Release, or debug a release | `references/release.md` |
| Change update / license behaviour | `references/jcore-update.md` |
| Tweak a config file | `references/tooling.md` explains every file and why it is that way |

## Scaffold a new plugin

The scaffold script renders the current templates (the Turva config files, generalised) so you don't retype a dozen files from memory:

```bash
python3 ~/.claude/skills/jcore-plugin/scripts/scaffold.py <target-dir> \
  --name "JCORE Example" --description "One sentence." [--admin] [--blocks]
```

- The slug comes from the directory name and must be `jcore-<word>`. Namespace (`Jcore\Example`), constant prefix (`JCORE_EXAMPLE`), function prefix (`jcore_example`), text domain and REST namespace (`jcore-example/v1`) are all derived from it — keep them consistent everywhere, because PHPCS checks the prefixes and text domain.
- `--admin` adds a settings page that hosts a React app, a REST controller base and the `src/admin/` entry. `--blocks` adds block registration from `build/blocks-manifest.php`; create blocks under `src/<block>/` (the `wp-block-development` skill covers block.json itself).
- Existing files are never overwritten. Add `--diff` to print what would change instead of writing.

With `--blocks`, the build fails until `src/` has at least one block: `cd src && npx @wordpress/create-block@latest <block> --no-plugin --variant dynamic --namespace jco --textdomain <slug>`, then `pnpm format`. Its `render.php` also needs the small PHPCS fixes in `references/php-conventions.md` → Blocks.

Then: `git init`, add a `LICENSE` (copy Turva's GPL-2.0 text), `pnpm install && composer install`, `pnpm build`, `pnpm check`, and fill in `readme.txt` (it is user-facing: the update popup on sites renders it). Before the first release the GitHub repo needs the secrets and dist repo listed in `references/release.md` — tell the user, you can't create those.

## Migrate an older plugin

Older plugins (pre-2026) commit `build/`, release with `TriPSs/conventional-changelog-action` + `versionSync.js`, use `.wp-env.json`, have no update client or no `readme.txt`. Run the scaffold with `--diff` against the repo to see the gap, then work through `references/migration.md`. The key points:

- Keep the plugin's identity: slug, post type and taxonomy names, option names, block names, existing constants. Renaming any of them breaks live sites.
- Turning on the stricter PHPCS/ESLint rules will surface real bugs (wrong text domains, conditional hooks). Fix them in a separate `fix:` commit from the tooling `chore:` commit so foonver cuts a patch release and the history stays readable.
- Leave the user's uncommitted work alone; mention it.

## Day-to-day rules

These apply to any change in a JCORE plugin. The reasons matter more than the rules — if a situation doesn't fit, reason from them.

1. **Bootstrap stays thin.** The main file defines constants, loads `vendor/autoload.php` if readable, registers the class autoloader, the activation hook, `plugins_loaded → Plugin::instance()->boot()` and the `jcore_plugins_loaded` filter. Everything else is a class under `includes/`, wired in `Plugin::boot()`. Details and templates: `references/php-conventions.md`.
2. **Reference jcore-update only through `use` imports and `::class`** in PHP files outside `build/`, `vendor/` and `tests/`. The publish workflow scopes the library's namespace with php-scoper and rewrites references only where it can see them; a class name built from a string concatenation, or living in `build/`, stays unscoped and fatals on sites that load two plugins.
3. **Guard the updater with `class_exists( UpdateConfig::class )`** so a source checkout without `composer install` still runs.
4. **Every string uses the plugin's own text domain** (`jcore-<word>`), never `jcore`. JS uses `@wordpress/i18n` with the same domain; scripts need `wp_set_script_translations( $handle, $domain, PATH . 'languages' )`.
5. **Prefix everything global**: functions `jcore_<word>_`, constants `JCORE_<WORD>_`, options/transients/meta `jcore_<word>_` (meta that should be hidden starts with `_`), hooks `jcore_<word>_`. Block render templates are excluded from the variable-prefix sniff because their variables are local.
6. **Schema changes go through `Database::maybe_upgrade()`** with a bumped `DB_VERSION`, so they deploy without reactivation. Anything stored must be removed in `uninstall.php`, which must work standalone and on multisite.
7. **Admin screens are React apps** mounted into a bare `<div id="jcore-<word>-app">` and talking to `jcore-<word>/v1` REST routes whose permission callback checks a capability. Enqueue only on that screen's hook suffix.
8. **Commits are Conventional Commits** with a scope: `feat(rest): …`, `fix(i18n): …`, `chore(deps): …`. foonver's `auto` bump: a `!` or `BREAKING CHANGE:` → major (and the update client then holds sites back until an admin opts in), any `feat` → minor, **anything else → patch**. So every push to `main` that carries a commit ships a release to live sites — batch work on a branch and merge through a PR. The subject line becomes the changelog entry sites see, so write it for them. Never hand-edit versions or the changelog; never commit `build/`.

### JavaScript

`@wordpress/scripts` only — no extra bundler or framework. WordPress formatting (`pnpm format`: tabs, spaces inside parentheses, single quotes), components from `@wordpress/components`, data from `@wordpress/api-fetch` / `@wordpress/data`. Hooks are called unconditionally at the top of components. `__experimental*` imports need an eslint-disable comment with the reason, and are a last resort.

## Verification

Before saying a change is done, run what CI runs and report the result honestly:

```bash
composer validate && pnpm check && pnpm build
```

For release-shaped changes also simulate what ships — `references/release.md` has the `rsync --exclude-from=.distignore` one-liner — and check no source, lockfile or dev tooling lands in it and `build/` and `vendor/jcodigital/jcore-update` do. `pnpm playground` (WordPress Playground on a per-plugin port) is the quick way to see it run; it needs network access.

## When to stop and ask

- Anything that renames a slug, option, meta key, table, post type, block name or REST route on a released plugin.
- Breaking changes (a major bump): confirm the user wants sites to need an opt-in.
- Creating GitHub secrets, the dist repo, or registering a slug with update.jcore.fi — the user has to do these.
