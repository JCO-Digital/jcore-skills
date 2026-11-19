# Styling the base Gutenberg blocks

Every project goes through **all** of the core blocks the theme styles and
makes each one fit the design. Do this once the design tokens are in
theme.json (`references/theme-json.md`), and keep going until each block on
the list is styled or a decision has been made about it.

## 1. Inventory

List what the theme styles now:

- `src/scss/blocks/_<block>.scss` (all `@use`d in `src/scss/theme.scss`).
- `styles.elements` and `styles.blocks` in theme.json.
- Block styles registered in `includes/gutenberg.php`.

The base set, from Ilme: paragraph and headings (typography), lists, quote and
pullquote, buttons, image and gallery, cover, group, columns, media & text,
separator, spacer, table, details, file, search, embeds, RSS, Yoast
breadcrumbs. Add any other core block the content will use.

## Style guide page (always)

Every project has a style guide page that lists and shows **all** the base
blocks the theme styles, so the whole set can be checked against the design at
once, on every site, and after every change. Build it first and keep it up to
date as blocks and styles are added.

- It's a theme pattern, `patterns/style-guide.php` (slug `jcore/style-guide`,
  title "Style guide"), so it's versioned and deployed with the theme. Images
  come from the theme (`get_theme_file_uri( 'assets/images/…' )`), so the
  pattern works on any site without media.
- Create the page from the pattern: a page "Tyyliopas" / "Style guide" (slug
  `tyyliopas` or `style-guide`) whose content is the pattern's content,
  e.g. with WP-CLI `wp eval` reading
  `WP_Block_Patterns_Registry::get_instance()->get_registered( 'jcore/style-guide' )`.
  Set `$_SERVER['HTTPS'] = 'on'` first in the eval: WP-CLI runs without
  HTTPS, so `get_theme_file_uri()` would save `http://` image URLs (mixed
  content).
- Locally, link the page in the footer's bottom menu so it's one click away.
- On staging and production a new or changed pattern may not register after
  a deploy: WordPress caches theme patterns per theme version. Clear it in one
  command (`wp eval 'wp_get_theme()->delete_pattern_cache();'`), then create
  the page in a second one.
  On staging, create it the same way or insert the pattern; keep it out of
  menus and set it to noindex in Yoast.
- Only blocks the editor offers: leave out what Ydin hides
  (`jcore_restricted_blocks`), or the editor shows them as unsupported.
- Get the markup of plugin blocks (Ruudukko, Lohko) by serializing them in the
  editor (`wp.blocks.serialize( wp.blocks.createBlock( … ) )`), so it matches
  what the editor saves.
- Content, each section under an eyebrow label and separated by a separator:
  - **Headings:** H1–H6, each followed by a normal paragraph, so the heading
    sizes and the spacing between heading and text show.
  - **Fonts and font sizes:** a line in each font family, and a line in
    every font size preset from theme.json, labelled with its slug.
  - **Colours:** a swatch for every palette colour, labelled with its name
    and slug (a grid of paragraphs with the colour as background, styled in
    `partials/_style-guide.scss`, which the editor stylesheet uses too).
  - **Text and links:** the paragraph styles (lead, ingress, eyebrow…), body
    text with a **normal text link**, bold and italic, small text.
  - **Buttons:** every button style (default, outline, text link…), and the
    light styles on a dark group.
  - **Lists:** every list style (bullets, dots, ordered, arrow list…).
  - **Quotes:** pullquote with a cite (core quote is hidden by Ydin).
  - **Images and media:** a normal image, an image with a caption, every
    image style, a gallery with captions, cover,
    video embed.
  - **Groups and columns:** every group style, a Ruudukko grid (`jcore/grid`
    with `jcore/column`s; core columns are hidden by Ydin).
  - **Responsive spacers:** each of Ydin's spacer styles (`is-style-sm`,
    `-md`, `-lg`, `-xl`, from `jcore_spacer_styles`) labelled, inside a
    `style-guide-spacers` group that gives them a sand background (in
    `_spacers.scss`) so the heights show. A spacer is its fluid size with 1rem
    as the minimum (the padding sits inside the 1rem height), so compare them
    at desktop and mobile widths.
    Above them, a note for editors: always use these spacer styles
    (Välike → Tyylit), not just the spacer's plain height in pixels, which
    stays the same on mobile and desktop.
  - **Tables:** default and stripes, with a caption.
  - **Post lifts:** `jcore/latest-posts` with a few posts (one without a
    featured image), on the background the site lists them on; the same
    for every post type with its own card (`tease-<post_type>.twig`).
  - **Accordions:** core details and Lohko's accordion, which share the look,
    and every block style of the Lohko accordion.
  - **File, search, RSS.**
  - Any project block style registered in `includes/gutenberg.php`.
- When a block or style is added to the theme, add it to the pattern and
  regenerate the page.
- Check the page in the editor too (no "invalid content" blocks) and with
  screenshots at desktop and mobile widths.

## 2. Match each block to the design

For each block, find its counterpart in the design (Figma or the prototype
export and its screenshots): colours, fonts and text styles, spacing, radii,
borders, icons, hover and focus states. Then style it as described in
SKILL.md ("Where styling lives", "Block styles", "Icons in CSS"):

- Base look in theme.json where Gutenberg's controls need it; everything else
  in the block's `_<block>.scss`.
- A second look the design shows (a box, round bullets, a light button) is a
  block style, not a custom class.
- Replace what's left over from Ilme: hard-coded hex or `rgb()` colours,
  Ilme's icons, radii and sizes that aren't the design's.

## 3. Blocks the design doesn't cover: ask

When a block isn't in the design, or the theme has no styles for it, **don't
invent a look silently**. Collect those blocks and ask the user which ones the
site will use. Then make a plan together: for each block, propose a style
built from the design's tokens and existing patterns (e.g. a table with the
separator's line colour and the eyebrow style for the header row; the file
block's button as the design's outline button). Agree on the plan, then
implement it. Blocks the site won't use can be left with a minimal, token-based
look, or removed from the inserter through Ydin's editor restrictions.

## 4. Check and commit

- Build (`make build`) and check each block on a local test page with every
  style variation in it, including hover and focus. A Playwright screenshot
  of the page is a quick check against the design.
- Check the editor too: the styles reach the editor through theme.json and
  the editor stylesheet.
- Commit in batches by block group on a `feat/<name>` branch, e.g.
  `feat: quote and pullquote as the design's testimonial`.
