---
name: jcore3
description: How J&Co Digital builds JCORE3 WordPress projects (Ilme base theme, jcore/ydin, Lohko, Ruudukko blocks), starting with reading the project's technical plan (the "tekninen suunnittelu" Google Sheet) and turning a Figma design into theme.json design tokens. Use this whenever working in a repo that has jcore.toml or a theme built on JCORE3/Ilme/Hurricane, or when the user asks to set up colours, fonts, font sizes, spacing, buttons or other design tokens for a WordPress theme from Figma, even if they don't say "JCORE".
---

# JCORE3 projects

JCORE3 is J&Co Digital's WordPress project template. A project is a copy of the
template; the theme is copied in and detached, so it holds as little as possible
and shared behaviour lives in packages.

**Multisite.** This skill describes a single site. If `jcore.toml` has
`multiSite = true`, read `references/multisite.md` first: several sites share
one theme, so anything that differs per site (colours, logo, names, menus,
content, plugin settings) must not be hard-coded the way the single-site rules
below do.

## Start here: read the technical plan

Every project has a technical plan, a Google Sheet ("<Project> tekninen
suunnittelu", sheet "Tekninen suunnittelu"). **Read it before building
anything**, and come back to it before each new part: it decides what gets
built, how, with which plugin, how it behaves on mobile and who maintains it.
The design (Figma or a prototype) shows how it looks; the plan says what it is.

- Ask the user for the link if it isn't in the conversation. Read it with the
  Google Drive connector (`read_file_content`), or the Google Sheets connector
  for full values (Drive returns only a sample of a sheet). Filling in or
  changing the sheet needs the Google Sheets connector.
- Read it row by row: each row is a part of the site, each column a question
  about it. `references/planning.md` maps the rows to the parts of this skill
  and says what to check in each column.
- Empty or unclear cells are questions for the user, asked before that part is
  built, not guesses. Decisions taken while building go back into the plan.
- Build in the plan's order of dependencies: installation and theme tokens
  first, then structure (navigation, footer, templates), then blocks,
  patterns and post types.

## Project layout

```
jcore.toml                       Project settings (root).
wp-content/themes/<project>/     The theme (copied from the "Ilme" base theme).
  theme.json                     Colours, typography, spacing, layout, block styles.
  src/scss/                      SCSS, built to dist/css. partials/ and blocks/.
  src/js/                        TypeScript, built to dist/js.
  assets/fonts/                  Self-hosted font files referenced from theme.json.
  templates/ parts/ patterns/    Block templates, template parts, patterns.
  views/                         Twig templates for teases and images.
  includes/setup.php             Menus, modules, which Ydin features run.
  includes/gutenberg.php         Core (Gutenberg) block additions: block styles, block bindings.
```

What is **not** in the theme:

| Concern | Lives in |
|---|---|
| Theme supports, menus, media, login/admin styling, block registration, editor restrictions | `jcore/ydin` (composer) |
| Header, footer, navigation, accordion blocks, and every new custom block | `lohko` plugin (`wp-content/plugins/lohko`) |
| Grid and column blocks | `ruudukko` plugin |
| Archive filtering and pagination | `dynamic-archive` plugin |

So before writing custom code for one of those, check whether the package already
does it and has a filter for changing it.

**Blocks.** Gutenberg's core blocks are styled in the theme
(`src/scss/blocks/`). Any block that isn't a core block is built in the Lohko
plugin, `wp-content/plugins/lohko`, not in the theme. That includes the site's
navigation and footer, which are Lohko blocks rather than theme template code.

**Styling Lohko blocks for a project.** Lohko is copied into every project,
so it's project code: **a Lohko block's whole look goes in the block itself**
(`src/<block>/style.scss`), not in the theme. The exceptions are the
**footer and the navigation**, which are styled in the theme
(`partials/_footer.scss`, `partials/_navigation.scss`). In the block's SCSS:

- Use the theme.json presets with a fallback
  (`var(--wp--preset--color--brand, currentcolor)`), so the block still looks
  right where a preset is missing. Lines and icons that should follow the
  text take `currentcolor` (`color-mix(in srgb, currentcolor 15%,
  transparent)` for a faint line).
- Media queries with the `@jcodigital/jcore-media` mixins: `@use "media";`
  (Lohko's webpack config aliases it) and
  `@include media.breakpoint(lg) { … }` (xs 0, sm 360, md 768, lg 992,
  xl 1200, xxl 1536; min-width only, so write the narrow case as the default).
  A layout that depends on the block's own width uses a container query.
- Images the block needs (an icon, the logo's mark) are copied into the
  block's folder and used as `url("./icon.svg")`: the build inlines small
  ones into the CSS, so there are no paths to break. Don't reach into the
  theme's assets from Lohko, and don't pass a `url()` through a CSS
  variable: a relative `url()` in a custom property resolves against the
  stylesheet that uses it, so it ends up pointing at the site root.
- Twig templates shared by several blocks go in Lohko's `twig/` folder.
- A block created as `.css` gets renamed to `.scss` once it has any real
  styling.
- The block's `style.scss` loads in the editor too, so nothing extra is needed
  in the theme's `editor.scss`.

**What master Lohko already has** (check before building a new block; the
project's copy is a snapshot of it):

- `lohko/accordions` + `lohko/accordion-item`: "First item open", "FAQ
  schema" (FAQPage, into Yoast's graph when Yoast is active, so FAQs are
  this block, not Yoast's FAQ block), Default and Compact styles, the
  address shows the open item.
- `lohko/nav-header`: Sticky, Scroll classes and "Overlay content" (header
  over a hero; Lohko removes the top margin of the block after it).
- `lohko/navigation` (with logo, search, hamburger that turns into an X),
  `lohko/sliding-menu` (off-canvas drill-down menu), `lohko/simple-menu`,
  `lohko/header`.
- `lohko/footer` with a logo setting (see `references/footer.md`).
- `lohko/logocloud`, `lohko/copyright-date`, `lohko/rive-animation`.

Widths are not set in Lohko: the template parts set them (`align`, and
`layout` on the nav header); see `references/templates.md`.

**Blocks that repeat the menu's content** (e.g. a services block showing the
mega menu's categories) read the same menu through Lohko's
`includes/menu.php` (`mega_categories()`, `mega_category_links()`), so the
navigation and the block stay in step and editors edit one place. A
desktop tab panel that becomes an accordion on mobile: render each panel
right after its button, and on desktop place all panels in the second grid
column over all rows (`grid-row: 1 / span <count>`); use `aria-expanded`
buttons rather than tab roles, since the same markup is both.

**Editor lists of posts** (`getEntityRecords`): only use REST parameters
the post type allows. `orderby: 'menu_order'` is rejected (400) unless the
post type supports page attributes, and the request then never resolves, so
the editor spins forever. Check `hasFinishedResolution` and show a message
when nothing came back.

**New options on Lohko blocks with saved markup** (a `save.js`): a new
attribute that changes the saved HTML makes every existing copy of the block
"invalid content" in the editor. Add to the markup only when the option is
on (e.g. `if ( attributes.firstOpen ) { context.openFirst = true; }`), so
blocks saved before the option keep identical markup; otherwise write a
deprecation. Check existing pages in the editor after the change.

**Interactivity API state getters** must read the context before anything
that can return early: a derived state that returns before touching
`getContext()` (e.g. while `getElement().ref` is still null on the first
render) never subscribes to it and doesn't update. Lohko's accordion had this
bug.

**IDs in Lohko block attributes.** Content moves between sites with JCORE
Silta, which rewrites post, term and media IDs only in the attributes it knows
about. A Lohko block that stores an ID (e.g. `personId`, `imageId`, a list of
post IDs) must be registered in Lohko's `silta_block_references()` on the
`jcore_silta_block_references` filter
(`$paths['lohko/contact-person'] = array( 'personId' => 'post' );`), or it
arrives on the other site pointing at the wrong post or nothing.

**Pattern or Lohko blocks?** If a content part needs more than two levels of
nesting group-like blocks (group, columns, cover), never build it as a
Gutenberg pattern. Build it as a set of Lohko blocks instead: a **containing
block holds the grid**, and the **items are their own blocks inside it** (the
way `lohko/accordions` holds `lohko/accordion-item`s). Deep patterns break
easily in the editor, and the customer can't edit them sensibly. A pattern is
fine for a flat part, e.g. a cover with a heading and buttons.

**Articles and post lifts.** Article lists use the **jcore-dynamic-archive**
plugin and its two blocks: `jcore/dynamic-archive` (archive with filters,
search, sorting, pagination or load more) and `jcore/latest-posts` (latest or
related posts). Use them rather than core Query Loop blocks. **Every archive
is a `jcore/dynamic-archive`**: the posts page and the archive of every
custom post type (references, people, …), each in its own
`archive-<type>.html`, never a page with a list on it; see
`references/post-types.md`.
- Dynamic Archive stays a normal plugin so it can be updated: **don't edit
  the installed plugin**, not even a small fix (a pull from the server
  overwrites it). **All its styling is in the theme**
  (`src/scss/partials/_archive.scss`), its post cards are the theme's post
  lifts, and any change in what it queries or outputs is made from the theme
  through its filters (`jcore_dynamic_archive_args`,
  `jcore_dynamic_archive_tax_query`, `jcore_latest_posts_args`, …) or
  template overrides in the theme (`views/dynamic-archive/…`). The same goes
  for every other normal plugin (Turva, Maailma, Ruudukko, Yoast, ACF).
- **Last resort: a project copy of Dynamic Archive.** If something really
  can't be done with its filters or template overrides, it's fine to copy
  the plugin into the project and commit it, the same way as Lohko (see
  Plugins). But be really sure first: check its hooks and Twig overrides
  (`includes/helpers.php` lists the `apply_filters` calls), try the fix from
  the theme, and ask the user before copying. A copy no longer gets the
  plugin's updates. Example of a fix that did *not* need a copy: the
  two-level term filter turning `include_children` off for every term
  (fixed with `jcore_dynamic_archive_tax_query` in the theme).
- Each post in a list is a **post lift** (tease), rendered by the theme's
  Twig: `views/partials/tease.twig` (wrapper, picks
  `tease-<post_type>.twig` or falls back to `tease-content.twig`) and
  `views/partials/tease-content.twig` (the card markup). Edit those for the
  card's content, and style it in `src/scss/partials/_tease.scss`.
  Make the whole card one link (`<a class="tease-card">` around image,
  date and title) when the design's card is clickable as a whole, give
  cards without a featured image a visible placeholder (it may sit on a
  background of the same colour), and add `jcore/latest-posts` to the style
  guide so the card shows there. The theme's archive grid
  (`_archive.scss`) must list `.wp-block-jcore-latest-posts__inner` as well
  as Dynamic Archive's `__inner`, or latest posts come out one per row.
  Ilme's archive templates (`home.html`, `archive.html`) come with a core
  Query Loop and their own card markup; replace it with
  `jcore/dynamic-archive` (e.g. `{"postType":"post","columns":3,
  "perPage":9,"showPagination":true,"infiniteScroll":false,"align":"wide"}`)
  so lists use the theme's tease. Ilme's pagination in `_archive.scss` has
  chevron icons as SVG masks (`assets/images/chevron.svg`, `chevrons.svg`,
  turned for previous and first); restyle it from the design (nav classes
  `is-first-page` / `is-last-page` tell which arrow to hide). Test it by setting `perPage` low for a moment.

**Logo.** For a logo that follows the header's text colour, use an SVG with
`fill="currentColor"` as the custom logo and turn on Ydin's inline SVG logo
(`add_filter( 'jcore_inline_svg_logo', '__return_true' )` before
`Media::init()` in `includes/setup.php`). Ydin strips WordPress's link from an
inlined logo; Lohko's `twig/navbar-brand.twig` adds the front-page link back.

**Favicon.** Ydin doesn't add one. Make it from the logo's mark in
`assets/favicon/`: `favicon.svg`, `favicon.ico` (16/32/48) and a 180px
`apple-touch-icon.png` (square, no rounded corners: iOS rounds it). Put a
wide mark on a square of the brand colour so it reads at 16px, and draw the
small sizes on whole pixels (rsvg-convert + ImageMagick; check them enlarged):
a mark scaled freely blurs at 16px. `includes/setup.php` prints the three
`<link>`s on `wp_head`, `login_head` and `admin_head` unless `has_site_icon()`,
so an editor's Site Icon still wins.

## Ilme is the backbone

The theme starts as a copy of **Ilme**, the skeleton theme. Its theme.json and
files are the backbone every project builds on. The design decides most values,
but a few things are fixed: the `success`, `warning` and `failure` colours always
stay, and `dark` / `light` always exist as the base font colours (`dark` close
to black, `light` white or near-white; `light` may also be a background). Ilme's
SCSS refers to its slugs, so reuse them where the role fits and re-point any
reference before renaming or removing one.

## Where styling lives

- `theme.json`: the design tokens (colours, fonts, sizes, spacing, layout) and
  a *partial* set of element and block styles: what Gutenberg's controls need.
- `src/scss/blocks/_<block>.scss`: one file per Gutenberg block (buttons, lists,
  table, details, pullquote, file, embeds, images and gallery, media and text,
  search, RSS, spacers, Yoast breadcrumbs). These style the block **fully**:
  hover and focus states, variations, icons, everything theme.json can't express.
  They are all `@use`d in `src/scss/theme.scss`. A block with no file yet gets a
  new `_<block>.scss` there.
- Buttons are the clearest case: theme.json sets the base `core/button` style
  and outline variation; `_buttons.scss` covers them fully, with hover rules and
  the `--jcore-button-*` variables used by non-Gutenberg buttons (`.btn`, form
  submits, teases, pagination).
- `src/scss/partials/`: site-wide styles (typography, navigation, footer,
  archive, teases, forms, accordion, search page…).

**The editor needs the block styles too.** Ydin loads only
`dist/css/editor.css` into the editor, so `src/scss/editor.scss` must `@use`
every `blocks/_<block>.scss` file that `theme.scss` uses (and the partials the
content needs, e.g. typography), or the editor looks unstyled. Keep the two
lists in step when adding a block file. Ilme's theme.json has `blockGap: null`:
the front end then has no block gap and blocks keep their own margins, but the
editor still adds a 24px gap above each block and none below, overriding
theme.json's heading and paragraph margins. Restate those margins in
`editor.scss` under `:is(.is-layout-constrained, .is-layout-flow) > …`.

**Ydin hides some core blocks** (`jcore_restricted_blocks`): quote, media &
text, query, navigation and others. Ilme changes the list in
`includes/gutenberg.php`: it hides **details** as well (accordions are Lohko's
`lohko/accordions`) and takes **columns** back off it, so editors have core
columns, but column layouts in patterns and the style guide are Ruudukko grids
(`jcore/grid`). Don't style or use a hidden block without asking; to allow
one, remove it through that filter in `includes/gutenberg.php`.

Every project goes through all the base blocks and styles each to the design;
blocks the design doesn't cover are planned with the user, not invented. See
`references/base-blocks.md`.

Use theme.json presets in SCSS (`var(--wp--preset--color--dark)`), not hex
values, so a token change reaches every block.

### Block styles (variations)

When the design has a second look for a core block (a highlight box, round
bullets, a light outline button), give the editor a **block style** rather than
a custom class or a new block:

- Register it in PHP in the theme's `includes/gutenberg.php`, in
  `register_block_styles()` (hooked on `init`), one `register_block_style()`
  per style, grouped by block with a short comment saying what it is in the
  design:
  ```php
  // Groups: the design's highlight boxes.
  register_block_style(
  	'core/group',
  	array(
  		'name'  => 'box-light',
  		'label' => __( 'Box, light', 'jcore' ),
  	)
  );
  ```
  The same style on several blocks: loop over the block names.
- `name` is a kebab-case slug describing the look (`box`, `dots`,
  `outline-light`, `rounded-corners`); it becomes the class `is-style-<name>`.
  `label` is short English through `__( …, 'jcore' )` so it gets translated
  with the theme.
- Style it in that block's `src/scss/blocks/_<block>.scss` with
  `.wp-block-<block>.is-style-<name>`. Start the file with a comment naming
  the styles it covers and pointing to `includes/gutenberg.php`. When the
  default look and a style differ (chevron vs. dot bullets), scope the
  default with `:not(.is-style-<name>)` so the style doesn't have to undo it.
- Don't reuse a core style name for a different look: core's image "Rounded"
  is a full circle, so the design's rounded corners are a new
  `rounded-corners` style.
- Only the base look and core's own variations (e.g. button `outline`) go in
  theme.json's `styles.blocks`; project styles live in PHP + SCSS.

**Spacing in content:** use Ydin's responsive spacer styles (Small,
Medium, Large, X-Large on `core/spacer`), not the plain spacer with a
pixel height, and don't add fixed-height spacers to templates or patterns.
The styles follow the theme's fluid spacing sizes.

### Icons in CSS

Small design icons (bullets, chevrons, arrows, social icons) are SVG files in
`assets/images/`, exported from Figma, named after what they are
(`bullet-chevron.svg`, `chevron-accordion.svg`, `arrow-long.svg`). Use them
from SCSS as a **mask** on a pseudo-element so the colour comes from a
preset or `currentcolor` and can change on hover:

```scss
&::before {
	content: "";
	width: 4.366px;
	height: 8.151px;
	background-color: var(--wp--preset--color--dark);
	mask: url("../../assets/images/bullet-chevron.svg") center / contain no-repeat;
}
```

The path is relative to the built CSS in `dist/css`. Use `background` or
`content: url()` only for multicolour images (patterns). Quote marks are
text, not images: the pullquote's mark is a quotation mark in the project's
heading font (`references/base-blocks.md`, "Default: the pullquote's
quotation mark").

### Image hover zoom

Cards whose image zooms a little on hover (a post lift, a person card, a
reference) wobble with a plain `transform: scale(1.03)` and a slow
transition, most visibly in Firefox. Speeding the transition up only hides
it. The causes, measured frame by frame:

- **Firefox snaps a 2D-scaled image to whole pixels.** At the slow end of
  the ease the image stands still for several frames, then jumps a pixel,
  each edge at its own moment. A 3D transform isn't snapped while it
  animates, so the image keeps an invisible tilt, `rotateX(0.02deg)` in
  perspective, at rest and zoomed, and only the scale changes. Don't raise the
  tilt: from about 0.1deg the still image isn't snapped either, and it looks
  soft.
- **When the transition ends, the still image is snapped again:** a small
  shift at the end, in view while the pointer stays on the card. So the
  zoom-in doesn't end there. The same 0.5s ease is squeezed into the first
  2.5% of a 20s transition that then holds still. The zoom-out uses the rest
  state's plain 0.5s transition, because a transition is taken from the state
  being entered.
- **Chromium redraws the image when a zoom ends** unless it has its own
  layer (`will-change: transform`). The box that clips it gets
  `isolation: isolate`, so the rounded corners keep clipping during the zoom
  in Safari.

Use this in the teases partial, wherever a card image zooms:

```scss
/* 'ease', sampled into the first 2.5% (0.5s of 20s), then holding still. */
$zoom-rest: perspective(2000px) rotateX(0.02deg) scale(1);
$zoom-in: perspective(2000px) rotateX(0.02deg) scale(1.03);
$zoom-ease-hold: linear(0, 0.033 0.125%, 0.0948 0.25%, 0.1855 0.375%, 0.2952 0.5%, 0.4085 0.625%, 0.5133 0.75%, 0.6048 0.875%, 0.6825 1%, 0.7479 1.125%, 0.8024 1.25%, 0.8478 1.375%, 0.8852 1.5%, 0.9159 1.625%, 0.9408 1.75%, 0.9605 1.875%, 0.9756 2%, 0.9868 2.125%, 0.9943 2.25%, 0.9986 2.375%, 1 2.5%, 1 100%);

@mixin zoom-image {
	transform: $zoom-rest;
	transition: transform 0.5s ease;
	will-change: transform;
	backface-visibility: hidden;
}

@mixin zoom-in-hold {
	transform: $zoom-in;

	@supports (transition-timing-function: linear(0, 1)) {
		transition: transform 20s $zoom-ease-hold;
	}
}

.tease-card__image {
	overflow: hidden;
	border-radius: 16px;
	isolation: isolate;

	img {
		@include zoom-image;
	}
}

.tease-card:hover .tease-card__image img {
	@include zoom-in-hold;
}
```

- **Another zoom amount:** change `scale(1.03)` in `$zoom-in` and nothing else.
- **Another duration:** the 0.5s is 2.5% of the 20s. To change it, sample CSS
  `ease` again over the new share of the long transition.
- **Browsers without `linear()`** (before 2023) get the plain transition: a
  smooth zoom with the small shift at the end.

The utility classes `img-hover-zoom` and `img-hover-zoom-reverse` in Ilme's
`_images-and-gallery.scss` use the plain scale and wobble the same way. Move
them to these mixins if a project uses them.

## Plugins

The JCORE plugins are listed in `jcore.toml` `pluginGit` only so jcore-cli pulls
them when a project starts. After that:

- **Lohko is copied into the project and committed**, then disconnected from its
  own repo: `.gitignore` gets `!/wp-content/plugins/lohko/` (Lohko's own
  `.gitignore` already leaves out `node_modules` and `build`). New blocks for the
  project are built in this copy. If one turns out to be good shared content, it
  can be taken back to the master Lohko repo.
- **Every other plugin is a normal plugin**: Dynamic Archive, Ruudukko, Turva, jcore-maailma and so on stay
  ignored by git and come from the server like any WordPress plugin.
- **Exception: Dynamic Archive** may get a project copy like Lohko, but only
  when a fix can't be made from the theme (see "Articles and post lifts"
  above) and the user agrees. Then `.gitignore` gets
  `!/wp-content/plugins/jcore-dynamic-archive/`, `pluginExclude` lists it, and
  the deploy workflow's `paths` include it. The installed plugin is a release
  build (`blocks/build` prebuilt, no block sources): a PHP or Twig fix can be
  made in a copy of it without a build step, but a change to the blocks' JS
  needs the plugin's source repo and a build job like Lohko's. Make sure only
  one copy of it is active (servers may also have suffixed copies from zip
  uploads, e.g. `jcore-dynamic-archive-<random>`).
- `jcore.toml`: `pluginGit = []`, and `pluginExclude` lists `"lohko"` so pulling
  plugins from the server never overwrites the project's copy:
  `pluginExclude = ["mainwp-child", "lohko"]`.
- Commit `composer.lock`, `pnpm-lock.yaml` and `wp-content/themes/index.php`.
- `.config/nginx/site.conf` and `.file.checksums.json` are rewritten by
  jcore-cli locally; don't commit those local changes.
- The deploy workflow's `paths` include `wp-content/plugins/lohko/`; the build
  job builds it.

Reference project for all of this: `chefsdagarna`.

## Translations

- **Translation work stays on one branch, `develop`**, so .po/.mo files never
  have to be merged between branches. Do features on `feat/<name>`, merge them,
  then translate on `develop`.
- Code strings are written in English and translated. A single-language site
  just gets its WordPress site language set (Settings → General); that covers
  WordPress itself, and the project's own files cover Lohko and the theme.
- **Lohko** (`lohko` text domain) loads from its own `languages/` folder, so
  its translations are committed and deployed with the plugin:
  1. `pnpm make-pot` in `wp-content/plugins/lohko`. It runs make-pot and then
     `bin/make-pot-twig.mjs`, which adds the `__('…', 'lohko')` strings from
     Twig templates (make-pot itself skips Twig, where the front-end strings
     of dynamic blocks are).
  2. Merge the new strings into the existing translation, keeping what is
     already translated: `wp i18n update-po languages/lohko.pot languages/lohko-fi.po`
     (in the container), then translate the empty `msgstr`s.
  3. Compile with WP-CLI in the container:
     `wp i18n make-mo languages/lohko-fi.po languages/` and
     `wp i18n make-php languages/lohko-fi.po languages/`.
  4. Editor labels in block JS need JSON files too; Lohko's `make-json` script
     is currently broken (it lacks `dlx`), so those are optional for now.
- **Theme** (`jcore` text domain) loads `languages/<locale>.mo` (e.g. `fi.mo`,
  no domain prefix) from the theme. Ydin sets ACF's text domain to `jcore`
  too, so ACF labels live in the theme's file:
  1. In the container: `wp i18n make-pot . languages/jcore.pot --domain=jcore
     --exclude=node_modules,dist,vendor,acf-json,bin` (reads PHP, JS,
     patterns and theme.json: colours, sizes, template and part names).
  2. `node bin/make-pot-extra.mjs` adds what make-pot can't see: Twig
     `__('…', 'jcore')` in `views/` and the labels in `acf-json/`.
  3. `wp i18n update-po languages/jcore.pot languages/fi.po` (or create
     `fi.po` from the .pot the first time), translate, then `make-mo` and
     `make-php` as for Lohko.
- **ACF post type and taxonomy labels** aren't translated by ACF at
  registration, only in its own screens. The theme's `includes/acf.php`
  filters `acf/post_type/registration_args` and `acf/taxonomy/registration_args`
  through `__( $label, 'jcore' )`.
- Lohko's make-pot writes the .pot as ISO-8859-1 and mangles non-ASCII
  characters in source strings (`…` became `?`): keep source strings ASCII.
- Run WP-CLI commands that write files in the container as the `jcore` user
  (`docker exec -u jcore -w <theme or plugin dir> <project>-wordpress-1 wp …`):
  the language files and `wp-content/uploads` belong to it, so `www-data`
  gets "Permission denied" (`wp media import` fails with "can't create the
  folder" and `--porcelain` hides it).
- Check on the local site with its language switched
  (`wp language core install fi --activate`).

## Git branches

- Remote: `git@github.com:JCO-Digital/<project>.git`; default branch `develop`.
- `develop` deploys to the **staging** site.
- `main` deploys to the **production** site.
- A new project starts with only `develop` (staging); `main` is added when the
  site goes to production.
- Feature work goes on `feat/<name>` branches (`feat/footer`, `feat/blocks`),
  merged into `develop`.
- Commit messages are conventional and short, describing what changed:
  `feat: single post template with the striped header, meta and title`,
  `fix: darker hero video and shadowed logo over it`, `revert: …`.

Don't create `main` or merge into it unless the user asks, because that publishes
to production. Pushing `develop` updates staging, so confirm before pushing.

### Deploy workflow

`.github/workflows/deploy.yml` uses `jco-digital/deploy-wp-action` (build job,
then a `server-deploy` job per branch). Ilme ships it set up for `main` only,
with the theme path `wp-content/themes/jcore-ilme/` and an empty server. For a
new project, set it up like `chefsdagarna`:

- `on.push.branches`: `develop`, with `main` commented out (`# - main # Disabled
  until needed.`).
- A `deploy-develop` job (`if: github.ref == 'refs/heads/develop'`) with the
  staging server, e.g. `server_url: <project>@<staging-host>`, `base_path: files`.
- Keep `deploy-main` for later; it can point at the staging server until the
  production address exists.
- `paths`: the project's theme folder (`wp-content/themes/<project>/`), plus
  `wp-content/plugins/lohko/`, the two mu-plugins and `vendor/`.
- `php_version` and `slack_channel` are per project; ask if unknown.
- A project often has no server at first. Leave Ilme's `main`-only workflow in
  place until the staging server exists, so pushes to `develop` don't try to
  deploy.

## Local environment

The local site runs in Docker, managed by jcore-cli. From the project root,
`jcore start` starts everything (containers, local domain); use it, not bare
`docker compose`, when the user asks to start the project, and `jcore stop` to
shut it down (the database is kept). `jcore` may not be on the PATH of a
non-interactive shell: if the command isn't found, ask the user to run it, or
fall back to `docker compose start` / `docker compose stop` in the project root.

## Building

From the theme directory: `make build` (or `pnpm run build`) builds once, `make dev`
watches. Run a build after any SCSS change; theme.json needs no build but must stay
valid JSON. Format with the repo's Prettier (`npx prettier --write theme.json`).

## Tasks

| Task | Read |
|---|---|
| Reading the project's technical plan, and what each row means | `references/planning.md` |
| Design tokens from Figma into theme.json (colours, fonts, sizes, buttons) | `references/theme-json.md` |
| Footer: global content, menus, logo, `lohko/footer` | `references/footer.md` |
| Page templates, heroes, template parts, breadcrumbs, block bindings | `references/templates.md` |
| Custom post types, taxonomies, ACF fields, post lifts, blocks showing post data | `references/post-types.md` |
| Styling all the base Gutenberg blocks to the design; blocks the design lacks | `references/base-blocks.md` |
| Multisite: one theme on several sites, per-site colours and logos, setup | `references/multisite.md` |

More topics will be added as the team documents them. When a task falls outside
the table, follow the existing code in the theme and say which conventions you
assumed so the user can correct them.
