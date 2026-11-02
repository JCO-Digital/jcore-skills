---
name: jcore-timber2
description: Upgrade a J&Co Digital (JCORE) WordPress site project from Timber 1.x to Timber 2.x — the jcore2 parent theme submodule (switch to its `timber2/<branch>` branch), the jcore2-child / site child theme, composer.json, .gitmodules and jcore.toml. Use this whenever the user mentions Timber 2, timber/timber ^2, a `timber2/*` jcore2 branch, "upgrade Timber", or when a JCORE project (has jcore.toml and wp-content/themes/jcore2) shows Timber 1 code such as `new Timber\Post`, `TimberImage(...)`, `Image(...)` in Twig, `Timber::get_context()`, `timber_context`, `Twig_SimpleFilter` or `{% filter %}` — even if the user only says "update timber" or "move this site to the timber2 branch".
---

# Timber 2 upgrade for JCORE projects

A JCORE site project looks like this:

```
<project>/
├── composer.json            # requires timber/timber (installed to <project>/vendor, loaded by jcore2/functions.php from ABSPATH/vendor/autoload.php)
├── jcore.toml               # branch = "<jcore2 branch>", theme = "<child theme dir>"
├── .gitmodules              # jcore2 + jcore2-child submodules, optional `branch =` lines
└── wp-content/themes/
    ├── jcore2/              # parent theme, submodule of JCO-Digital/jcore2 — one long-lived branch per generation (bream, fluffycritter, grasshopper…)
    ├── jcore2-child/        # template child theme, submodule (not the active theme)
    └── <theme>/             # the site's real child theme, a copy of jcore2-child — this is where the per-site work is
```

The parent theme has already been ported for several generations on branches named **`timber2/<branch>`**. The upgrade of a project is therefore mostly: point the submodule at that branch, bump Composer, then port the site's own child theme by hand. The official guide is <https://timber.github.io/docs/v2/upgrade-guides/2.0/>; `references/timber2-api.md` is a condensed version of it with the JCORE-relevant parts first.

## Workflow

### 1. Find where the project stands

```bash
grep -E '^(branch|theme)' jcore.toml; cat .gitmodules
grep timber/timber composer.json
git -C wp-content/themes/jcore2 fetch --all -q
git -C wp-content/themes/jcore2 branch -r | grep timber2/
```

`branch` in jcore.toml is the jcore2 generation (e.g. `grasshopper`). If it already says `timber2/…`, the parent is done — skip to step 4. Note uncommitted changes in the project and submodules (`git status`, `git diff --submodule`) and leave them alone; jcore CLI's `jcore update project` often leaves composer.json / jcore.toml modified, and that is the user's work.

### 2. Make sure `timber2/<branch>` is current

The timber2 branches were cut from their base branch at some point and **can lag behind it**. A project whose submodule is on the tip of `<branch>` would lose those fixes when switched. Check:

```bash
cd wp-content/themes/jcore2
git log --oneline origin/timber2/<branch>..origin/<branch>   # commits timber2 is missing
git merge-tree --write-tree --name-only origin/timber2/<branch> origin/<branch>   # dry-run merge
```

- **Nothing missing** → go on.
- **Missing commits** → merge `<branch>` into `timber2/<branch>` (that is how `timber2/fluffycritter` was kept up: "Merge remote-tracking branch 'origin/fluffycritter' into timber2/fluffycritter"). Resolve conflicts keeping the Timber 2 API (see the table in step 4) and the new behaviour from `<branch>`; then run `scripts/scan-timber1.sh` on the parent theme to catch Timber 1 calls the new commits brought in. **Pushing to `JCO-Digital/jcore2` affects every site on that branch — ask before pushing.**
- **No `timber2/<branch>` exists** → port the parent first. Create `timber2/<branch>` from `origin/<branch>` and follow the existing ports as worked examples — they touched the same files in every generation: see `references/jcore2-branches.md`. Ask the user before creating and pushing a new shared branch.

`references/jcore2-branches.md` lists the known state of each branch and leftovers the existing ports missed.

### 3. Switch the project over

1. **composer.json** — `"timber/timber": "^2.3"`. Timber 2.0–2.2 ran on PHP 7.4, but **2.3+ requires PHP ^8.1 and Twig ^3.17**, and many JCORE projects still pin `config.platform.php` to `8.0`. With `8.0` Composer either fails or quietly settles on an old 2.x. Set the platform to the server's PHP version, which must be ≥ 8.1. Done projects use 8.2–8.4 and Timber 2.3.1–2.5.1. Then `composer update timber/timber --with-all-dependencies` (needs repo.jco.fi credentials, which are in composer.json). Remove `upstatement/routes`-dependent code if any — `Timber::add_route()` is gone.
2. **jcore.toml** — `branch = "timber2/<branch>"`.
3. **.gitmodules** — set `branch = timber2/<branch>` on the jcore2 submodule (add the line if missing). jcore2-child has no timber2 branches; its own branch tip already uses `timber/context`, so leave it or just update it to its branch tip.
4. **Submodule** — `git -C wp-content/themes/jcore2 checkout timber2/<branch> && git -C wp-content/themes/jcore2 pull`, then `git add wp-content/themes/jcore2` in the project.
5. **Check `.github/workflows/deploy.yml` before pushing.** Pushing to `main` deploys to production, and `vendor/` (Timber 2) and the parent theme have to arrive together. Its `paths` must include `wp-content/themes/jcore2/` (every sibling project has it). If it's missing, the server keeps the Timber 1 parent next to Timber 2 in `vendor/`. That happened on bojaco: pages rendered, but search, 404 and archives returned 500. Also drop paths that don't exist in the project (a template-added `wp-content/plugins/lohko/` breaks rsync), and check that the Makefile's `build` target matches how the theme actually builds. jcore2's `dist/` is not needed on the server, because its scripts resolve to the child theme's `dist/`. After the deploy, load search, a 404 and an archive on the live site, not just the front page.
6. Any mu-plugin that boots Timber itself (older bream-era `jcore-loader.php`): `new \Timber\Timber` → `\Timber\Timber::init()`.

### 4. Port the site child theme (and any project plugins)

Run the scanner over every theme/plugin the project owns, **including `wp-content/mu-plugins`** (older projects boot Timber in `jcore-loader.php` there), but not jcore2 unless you are porting it:

```bash
~/.claude/skills/jcore-timber2/scripts/scan-timber1.sh wp-content/themes/<theme> wp-content/mu-plugins [more dirs…]
```

It prints `file:line` grouped by problem with the fix. It is grep-based: treat each hit as a lead, read the code, and fix what is real. Things it cannot see are listed after the table.

The changes that come up in JCORE child themes, in order of frequency:

| Timber 1 | Timber 2 | Notes |
| --- | --- | --- |
| `add_filter( 'timber_context', … )` | `add_filter( 'timber/context', … )` | **Every** child theme has this in functions.php. In 2.x it still runs through `apply_filters_deprecated` and logs a notice on every request. It is the most visible leftover. `get_twig` → `timber/twig` behaves the same way. |
| `new Timber\Post()` / `new \Timber\Post( $id )` | `Timber::get_post()` / `Timber::get_post( $id )` | Returns `null` (not `false`) when not found. Same for `Term`→`get_term`, `User`→`get_user`, `Comment`→`get_comment`. |
| `new Timber\PostQuery( $args )` | `Timber::get_posts( $args )` | Args must be an array; query strings (`'post_type=x'`) no longer work. Returns a collection — `->to_array()` if you need an array. **An empty array or empty ID list falls back to the main query** (`$query ?: $wp_query` in the source) — guard ID lists with `! empty()`. Use `posts_per_page`, not `numberposts`. |
| `new Timber\Menu( 'primary' )` / `new \TimberMenu(…)` | `Timber::get_menu( 'primary' )` | Returns `null` if no menu is assigned — guard with `{% if menu %}` before `menu.items`. |
| `Timber::get_context()` | `Timber::context()` | Old one still works but is deprecated. |
| `Timber::fetch( … )` | `Timber::compile( … )` | |
| `Timber::query_post()` / `$context['posts'][0]` in singular templates | `Timber::get_post()` | |
| `new \Timber\Timber()` | `\Timber\Timber::init()` | Constructor is protected → fatal. |
| `new \Timber\Twig_Function( … )`, `new \Twig_SimpleFunction( … )` | `new \Twig\TwigFunction( … )` | Same for `Twig_SimpleFilter`/`Timber\Twig_Filter` → `\Twig\TwigFilter`. |
| `Timber::get_terms( 'category', $args, 'MyTerm' )` | `Timber::get_terms( array( 'taxonomy' => 'category' ) + $args )` | A bare taxonomy string still works. The old 2nd/3rd arguments do not. **`hide_empty` now defaults to WP's `true`**: pass `'hide_empty' => false` where empty categories used to show (e.g. filter lists). |
| `$post->terms( 'category' )` | `$post->terms( array( 'taxonomy' => 'category' ) )` | |
| Twig `Image(x)`, `TimberImage(x)` | `get_image(x)` | The old names still work in 2.x but log deprecations, so replace them anyway. `get_image()` takes an attachment ID, a WP_Post, or an ACF image array (anything with an `ID` key). It returns `null` if `x` is not an image attachment, and a URL never works: use `get_external_image(url)` for URLs and `get_attachment(x)` for PDFs and other files. |
| Twig `Post(x)`, `TimberPost(x)` | `get_post(x)` / `get_posts(ids)` | |
| Twig `post.get_thumbnail`, `post.get_link`, `item.get_link`, `post.get_preview` … | `post.thumbnail`, `post.link`, `item.link`, `post.excerpt` | The `get_*` getters on Post, Term, MenuItem and Image were removed (full list in the reference). In Twig they don't error. `__get` falls through to `meta('get_thumbnail')`, so they quietly render nothing and `{% if post.get_thumbnail %}` is always false. `post.preview` still exists but is deprecated: use `post.excerpt`. The jcore `\|preview` *filter* is jcore's own and is fine. |
| Twig `post.custom.field` | `post.meta('field')` | `custom` now returns `false` and logs a deprecation, so the output is silently empty. |
| Twig `site.url` | `site.link` | Optional: `site.url` is still a property and works. `site.get_link`/`site.get_url` do not. |
| `{% filter apply_filters('x') %}…{% endfilter %}` | `{% apply apply_filters('x') %}…{% endapply %}` | Same for `{% spaceless %}` → `{% apply spaceless %}`. |
| `fn('timber_set_product', post)` | `fn('jcore\\timber_set_product', post)` | jcore2's timber2 branches moved this WooCommerce helper into the `jcore` namespace. |
| `{% do action('x', a) %}` + `add_action( 'x', $cb, 10, 2 )` reading `$context` | pass what you need explicitly: `{% do action('x', a, post) %}` | Actions no longer receive the Twig context as an extra argument. |
| `{{ wp_head }}` / `{{ wp_footer }}` | `{{ function('wp_head') }}` | jcore2's header/footer already do this. |

WooCommerce template overrides in a child theme that render small partials (`loop/price.php`, `loop/rating.php`, `loop/add-to-cart.php`) should use `Timber::context_global()` instead of `Timber::context()` — the full context runs post/posts queries per product in the loop. jcore2's timber2 branches already did this.

#### Things the scanner can't decide

- **Custom fields via property access** (`post.hero_image`, `post.my_field`). This still works, silently and without a notice: `Core::__get()` tries `meta($field)` first. But the guide discourages it, and it returns the wrong thing when the field name collides with a Timber method or property (`link`, `title`, `content`, `thumbnail`, `date`, `class`, `type`, `children`, `parent`, `category`, `author`, `name`, `path`, `excerpt`…). Use `post.meta('field')` in code you touch, and always for colliding names. Don't mass-rewrite working templates.
- **ACF values are no longer turned into Timber objects.** `meta()` returns what the ACF field's return format says. Wrap IDs yourself: `get_image(post.meta('img'))`, `get_posts(post.meta('relationship'))`. Do not enable `timber/meta/transform_value` site-wide on an existing site — it changes the type of every field at once.
- **Context changes.** Timber 2 puts `post` into the context on singular views and `posts` (plus `term`/`author`) on archives automatically. Explicit `$context['post'] = Timber::get_post()` still works; keep it if the template needs a *different* post (e.g. a page fetched by slug).
- **`Timber::get_posts()` with no arguments** returns the main query; fine in archive templates, but `posts` is already in the context there.
- **Class maps.** `Timber::get_posts( $args, 'MyPost' )`, `new MyPost()` and `Timber\PostClassMap` → register the class in the `timber/post/classmap` filter.
- **Hook renames** (`timber_render_data`, `timber_post_get_meta_field`, `timber/loader/paths`, …) — see the reference.

### 5. Verify

There are no tests in these projects; verification is "it renders and nothing is logged".

```bash
composer validate && composer install
find wp-content/themes/<theme> -name '*.php' -not -path '*/vendor/*' -exec php -l {} \; | grep -v 'No syntax errors'
~/.claude/skills/jcore-timber2/scripts/scan-timber1.sh wp-content/themes/<theme>   # should be clean or only reviewed hits
jcore start          # or jcore restart if already running; jcore status prints the URL
composer show timber/timber | grep -m1 versions      # expect 2.x
```

If the local DB is a fresh install (no `jcore pull` yet), permalinks are plain: every pretty URL returns the front page with 200. Use `/?p=1`, `/?page_id=2`, `/?cat=1`, `/?author=1`, `/?s=x` and `/?p=999999` (404) instead, or pull content first (ask, since it reads production). Projects with `pluginInstall = "remote"` have no plugins locally, so Twig functions that a plugin registers will fatal locally ("Unknown \"x\" function"). Grep the repo before blaming Timber, and report these as unverified rather than fixing them.

Then load the front page, a page, a post, the blog/archive, a category, search, 404 and (if WooCommerce) shop/product/cart, with `WP_DEBUG` and `WP_DEBUG_LOG` on, and read `wp-content/debug.log`. Timber 2 reports deprecated calls through `_deprecated_function` / `_doing_it_wrong` notices. Many Timber 1 leftovers don't log anything. A removed `get_*` getter or `post.custom` just renders empty, so compare the pages visually with the live site too. Look especially at teasers and cards (`post.get_thumbnail` → no image), menus (`item.get_link` → `href=""`), blocks with images (`get_image` returning null → empty `src`) and category filter lists (`hide_empty` change).

Report what was checked and what wasn't (e.g. "WooCommerce pages not tested, plugin inactive locally").

### Commits

Conventional Commits, as in the existing projects: one `refactor(theme): upgrade to Timber v2` (or `build(deps): update timber/timber to version 2`) commit in the project containing composer.json/lock, jcore.toml, .gitmodules, the submodule pointer and the child-theme changes. Changes inside jcore2 are committed in the jcore2 repo on `timber2/<branch>`. Don't push either without the user's go-ahead.

## When to stop and ask

- Pushing to, or creating, a `timber2/*` branch in `JCO-Digital/jcore2` — it is shared by every site on that generation.
- Merge conflicts in jcore2 where it is not obvious which side's behaviour should win.
- Third-party plugins in the project that bundle Timber 1 themselves (e.g. old `jcodigital/jquest-plugin` < 0.4 required `timber/timber ^1.22`) — Composer will refuse to resolve; the plugin needs updating first.
- Deploying. The remote server has to run at least the `config.platform.php` version (≥ 8.1 for Timber 2.3+). If you can't confirm it, say so: `jcore ssh` then `php -v` checks it.
