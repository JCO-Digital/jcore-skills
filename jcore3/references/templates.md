# Templates and heroes

Block templates live in `templates/`, template parts in `parts/`, patterns in
`patterns/`. Register parts and selectable templates in theme.json
(`templateParts`, `customTemplates`).

## Principles

- **Let the editor choose.** Page templates with different heroes are
  selectable per page (`customTemplates`), not picked automatically from the
  page hierarchy: you never know what the customer will want to build.
- **Hero = template part**, one per hero type (`hero-plain`, `hero-photo`,
  `hero-navy`…), filled from page data: post title, featured image, excerpt,
  Yoast breadcrumbs. A front page hero with free text and buttons is a
  **pattern** in the page content instead, so it stays editable.
- **The header overlays the top of the page** when the design has heroes
  under it (`lohko/nav-header` `overlay`). Every hero then leaves room for
  the header itself. A `header-dark` part (light text) is used over photo and
  dark heroes.
- Header and footer parts are placed with `"tagName":"div"`
  (see `footer.md`), so the page has one `<header>` and one `<footer>`.

## Article lists in templates

`home.html` and `archive.html` list posts with `jcore/dynamic-archive`, not
the core Query Loop that Ilme's templates still contain; related or latest
posts elsewhere use `jcore/latest-posts`. The cards are the theme's post lifts
(`views/partials/tease*.twig`, `_tease.scss`). See SKILL.md, "Articles and
post lifts".

## Data no core block shows

Use **block bindings** instead of a new block. Register a source in the theme
(`register_block_bindings_source` in `includes/gutenberg.php`) and bind a
paragraph or heading to it:

```html
<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"<project>/parent-title"}}},"className":"is-style-eyebrow"} -->
<p class="is-style-eyebrow"></p>
<!-- /wp:paragraph -->
```

Examples: the parent page's title as an eyebrow, the posts page's title on
`home.html`. Small recurring text styles (eyebrow) are block styles
(`register_block_style`) the editor can pick.

## Breadcrumbs

Breadcrumbs come from **Yoast** (`yoast-seo/breadcrumbs` block), a normal
plugin. The separator and home label are Yoast settings (Settings →
Breadcrumbs), so set them on each site. Locally Yoast refuses to build its
index on a non-production site, so the trail misses parent pages; build it
once with WP-CLI:

```bash
printf '%s\n' '<?php' 'WP_CLI::add_wp_hook( "Yoast\\WP\\SEO\\should_index_indexables", "__return_true" );' > /tmp/yoast-local.php
# copy into the container, then:
wp --require=/tmp/yoast-local.php yoast index --reindex --skip-confirmation
```

## Gotchas

- **Ilme pads and spaces every group** (`styles.blocks.core/group`: padding
  and margin on all sides). Inside heroes and other tight layouts, reset
  `padding` and `margin-block` on the inner groups, and on the main group
  where a full-bleed hero must touch the top.
- **Overlay header and the admin bar.** WordPress moves the whole page down
  for the admin bar (`html { margin-top }`), so an absolutely positioned
  overlay header needs `top: 0`; adding the admin bar height doubles the
  offset for logged-in users. Only a fixed (sticky) header needs
  `top: var(--wp-admin--admin-bar--height)`. Check heroes logged in too.
- **Cover block:** a cover with a content position (e.g. bottom left) gets
  `width: auto` on its inner container from core, with a selector as specific
  as a theme's and loaded later; use `width: 100% !important` there. A wide
  group inside a cover also needs its width and centring set explicitly.
- **Hand-written block markup must match what the editor saves**, or the
  editor shows "unexpected or invalid content". For `core/cover` the
  `<img class="wp-block-cover__image-background">` comes before the overlay
  `<span>`, and a gradient overlay span has the class
  `wp-block-cover__gradient-background`. Check new templates, parts and
  patterns in the editor: log in with a temporary local admin user (deleted
  afterwards), open them, and read the console's "Block validation" messages,
  which show the expected markup.
- Compare against the design with screenshots at 1920, 1440 and 390 wide,
  measuring the H1's position and size against the prototype.
- **Templates come from the theme's files; don't save them in the Site
  Editor** on staging or production. Saving a template or part there stores a
  copy in the database that overrides the file, so later deploys of that
  template no longer show. A "Customized" template is put back with **Reset**
  (Clear customizations) in Appearance → Editor. Site settings that live
  outside templates are set elsewhere: the logo in `wp-admin/customize.php` →
  Site Identity (a block theme hides the menu item, the URL works), menus
  under Appearance → Menus. Silta doesn't sync these settings.
- **Text in block templates can't be translated.** Templates (`.html`) hold
  no PHP, so any visible text there (404, search, "no results", button and
  pagination labels) goes into a theme pattern (`patterns/<name>.php`,
  `Inserter: no`) with `__( …, 'jcore' )` strings, and the template only
  holds `<!-- wp:pattern {"slug":"jcore/<name>"} /-->`. Block attributes
  with text (e.g. `core/search`'s label and button) are built with
  `wp_json_encode()`. The same word with two meanings needs a context
  (`_x( 'Search', 'search page eyebrow', 'jcore' )`: "Haku" vs the button's
  "Hae").
- **Search page:** results like the theme's other lists, with the post type
  as an eyebrow from a block binding (`<project>/post-type-label` in
  `includes/gutenberg.php`), and noindex posts (Yoast's
  `_yoast_wpseo_meta-robots-noindex`) left out of the search in
  `includes/search.php`, e.g. the style guide page.
- **Article page (`single.html`):** without its own design, give it the
  person hero's look as a theme part (`parts/hero-article.html`, core blocks
  only) but **without the image**: article titles are often very long and
  need the whole width. Date (`post-date` with `"format":"j.n.Y"`) and
  category (`post-terms`) as eyebrows above the title, the title as an H1 at
  the H2 size capped at about 1100px wide, the excerpt as the intro. On
  phones use the H3 size and `hyphens: auto` (with `lang="fi"`), or long
  Finnish words break mid-word. Add the part to theme.json's
  `templateParts`, reset Ilme's group padding inside it, hide the default
  category ("Uncategorized") with a `get_the_terms` filter on the front end,
  drop Ilme's English "Previous/Next post" links, and end the article with a
  translatable "All articles" back link pattern to the posts page.
- **Related articles** go last on the article page, between the content and
  the footer: Dynamic Archive's `jcore/latest-posts` with
  `"related":true,"backfill":true` (posts sharing any public taxonomy's
  terms, filled up with the newest), 6 posts in 3 columns, wide. Its
  `heading` attribute is text, so the section is a pattern
  (`patterns/related-articles.php`, attributes via `wp_json_encode()`) and
  the template holds `<!-- wp:pattern {"slug":"jcore/related-articles"} /-->`.
  The heading only renders when there are posts.
  On pages (e.g. a service's "related articles"), related mode would list
  pages, since it queries the current post's type: set `postTypes` on the
  block and let the theme use them in related mode through Dynamic Archive's
  `jcore_latest_posts_args` filter (`includes/articles.php`). Patterns placed
  with `<!-- wp:pattern {"slug":"…"} /-->` inside another pattern stay one
  source, so a fix in the referenced pattern reaches every page using it.
- **Content column on the left:** core's constrained layout centres the
  content column, while the page titles in the heroes start at the wide
  layout's left edge. When the design puts the text under the title, give
  the template's `post-content` (or `main`) the class `is-content-left` and
  move the column with
  `margin-left: max(0px, calc((100% - var(--wp--style--global--wide-size)) / 2)) !important`
  on its non-wide, non-full children (core centres with `!important`, so this
  needs it too; use a selector like `.wp-block-post-content.is-content-left >
  :where(...)` so it's more specific). Wide and full blocks stay as they are.
  Keep it per template: a page the design centres (e.g. a person page) doesn't
  get the class.
