# Figma design tokens → theme.json

Every project's theme starts from **Ilme**, the skeleton theme, and its
theme.json is the backbone. The first job on a new project is to fit the
design's tokens into it.

What is fixed and what follows the design:

- **`success`, `warning`, `failure` always stay.** Forms, notices and the
  packages use them. Their values come from the design's stylesheet page if it
  fills them in, otherwise Ilme's values stay.
- **`dark` and `light` always exist as the base font colours.** `dark` is the
  design's text colour closest to black; `light` is its white or near-white
  colour, used for text on dark backgrounds. `light` may also be used as a
  background; what matters is that it is the near-white base, not a coloured
  tint (a beige or grey surface gets its own slug).
- **Everything else follows the design.** Change values, rename, add, or remove
  Ilme entries the design has no use for. Where a design token has the role of an
  Ilme entry (the primary colour → `brand`, body size → `paragraph`, H1 →
  `heading-1`), reuse that slug, because Ilme's SCSS already refers to it.
- **Nothing may point at a slug that no longer exists.** When you rename or remove
  an entry, re-point every SCSS, template and theme.json reference to it.

## 1. Read what is there

- `theme.json`: current palette slugs, font families, font sizes, element and block styles.
- Find which preset slugs the SCSS and templates depend on, so you know which slugs
  must survive:

  ```bash
  grep -rnoE "(color|font-family|font-size|spacing)(--|\|)[a-z0-9-]+" src templates parts patterns views theme.json | sort | uniq -c
  ```

  Then look at *how* each is used. Ilme's SCSS uses `light` both as a text colour
  and as a panel background (details, accordion, table, search, archive, RSS).
  Both are fine. If `light` ends up so close to the site background that panels
  disappear, point those backgrounds at a design surface colour and say so.
- `src/scss/blocks/_buttons.scss`: the `--jcore-button-*` variables used by
  non-Gutenberg buttons (forms, teases, pagination). They must match the
  theme.json button styles.

## 2. Read the Figma tokens

Use the Figma MCP on the top-level page frame (not a small child, or you only get
the variables that child uses):

- `get_variable_defs`: colours, font families, text styles, and per-property number
  variables such as `h1/font-size`, `h1/line-height`, `h1/letter-spacing`.
- `get_design_context` on a few text nodes (a heading block, the buttons, a quote,
  the footer): shows the rendered px values, which is how you check units.

### The Stylesheet page

J&Co Figma files have a **Stylesheet** page (a J&Co template: Colors, Fonts,
Button styles, Table design, Spacers, Icons). Look at it with `get_screenshot`
and `get_metadata`, then read individual swatches with `get_design_context`. It
is often only partly filled in. Grey `#D9D9D9` swatches and "–" or "XXX" cells
are the template's placeholders, not design values: ignore them.

- **Main colors** (Primary, Secondary), **Additional colors**.
- **Info colors**: Warning, Success, Danger, Info. When filled in, these set the
  values of `warning`, `success`, `failure` (= Danger) and add `info`.
- **Text colors**: Light and Dark. These are `light` and `dark`.
- Button styles (incl. hover), list bullet colours, table design, spacers: use
  them when filled in; otherwise follow the page design and say what you chose.

### Units in Figma tokens

- **Letter spacing is a percentage of the font size.** `h1/letter-spacing: -1.6`
  on an 84px font renders as -1.344px. Write it as `em`: value / 100, so `-0.016em`.
  Check one against the `tracking-[…px]` in `get_design_context` if unsure.
- **Line height** in px (`h2/line-height: 74`) → unitless ratio, line height / size,
  3 decimals: `74 / 60 = 1.233`. A text style lineHeight of `100` with no px
  variable means 100%, i.e. `1`.
- Font weight comes as a number; text style "Medium" = 500.

### Missing or odd tokens

Designs are often incomplete and the missing values won't come later, so fill
the gaps yourself instead of waiting for them:

- A missing value (e.g. a heading with no line height, or only "100%"): pick one
  that fits its neighbours. For line heights, take a ratio between the sizes
  above and below (H2 1.233 and H4 1.286 → H3 36px at 44px = 1.222; H6 20px at
  28px = 1.4, near the quote's 24/34).
- A stray font on one layer that isn't in any text style (e.g. a leftover
  font on the copyright line): use the style's font that matches its role.

List these values in the report as your choices, so the user can change them,
but don't present them as something for the designer to fix.

## 3. Write theme.json

Editing with a short Python script (`json.load` → change → `json.dump(indent="\t",
ensure_ascii=False)`) then running Prettier keeps the formatting and avoids broken
JSON from hand edits of a large file.

### Colours (`settings.color.palette`)

- `name` = the Figma variable name, tidied ("Text dark", "Additional 1").
- `dark` = the design's text colour closest to black, `light` = its white or
  near-white base colour.
- Other roles reuse Ilme slugs where they fit: `brand` = primary colour, `black`,
  `white`.
- Every other design colour gets a kebab-case slug from its Figma name
  (`secondary`, `secondary-dark`, `additional-1`).
- One palette entry per colour value. Figma often has the same value under
  several names (a "Text light" and a light background, "White" and
  "Additional 4 - white"); they share one entry, using the Ilme slug if one of
  them has that role (a background with the same value as the light font colour
  is just `light`). Colours that are only *nearly* the same are separate design
  decisions: keep them apart, and mention them to the user in case they should
  be merged.
- `success`, `warning`, `failure`: keep with Ilme's values.
- Ilme colours and gradients the design doesn't use (e.g. `orange`, `grey`,
  `green`, the White-to-Brand gradient) can be removed. Re-point their SCSS uses
  to the design colour with the same role: hover/darker → the darker variant,
  accent → primary, muted text → the lighter text colour.

### Fonts (`settings.typography.fontFamilies`)

- Self-host every font in `assets/fonts/` and reference it with
  `file:./assets/fonts/<file>`. Prefer variable fonts; Google fonts come from the
  `google/fonts` GitHub repo (`ofl/<family>/<Family>[wght].ttf` and the Italic file).
- One family entry per design typeface, slug = kebab-case family name
  (`inter`, `playfair-display`). Declare the weight range the file supports.
- Ilme ships Playfair Display (headings) and Inter (body and buttons), both
  variable with real italic files. Fonts the design doesn't use can be
  removed, with their files (`git rm`). After changing the heading font,
  set the pullquote's mark offset (`references/base-blocks.md`).
- Update `src/scss/partials/_typography.scss`:
  `--jcore-font-family` (body) and `--jcore-heading-font-family` (headings).

### Font sizes (`settings.typography.fontSizes`)

Set the design values on Ilme's slugs (`paragraph`, `heading-1` … `heading-5`,
`ingress`, `mini`), add any the design needs that Ilme lacks (e.g. `heading-6`,
`quote`). Ilme sizes the design has no use for can stay or go; check SCSS
references before removing one. `size` is the Figma desktop px value.

**Fluid range.** Set `settings.typography.fluid` to
`{ "minViewportWidth": "1024px", "maxViewportWidth": "<Figma desktop frame width>" }`
(e.g. 1920px). WordPress's default range (320–1600px) makes headings grow from
phone widths and reach full size too early; with this range they reach the
design size at the design's frame width and stay at their minimum below 1024px.

**Keep Ilme's clamps.** Ilme already decides which sizes scale (`fluid: { min,
max }`) and which are fixed (`fluid: false`), and its spacing sizes in
`settings.custom.spacingSizes` are `clamp()`s too. Keep that structure:

- `max` (and `size`) = the design's desktop size.
- `min` = the design's mobile size if the design gives one (a mobile frame with
  real content, or mobile values in the stylesheet page or variables).
- Otherwise `min` = **the desktop size of the next size down**: H1's min is H2's
  desktop size, H2's min is H3's, H3's is H4's; ingress steps down to
  `paragraph`. This keeps the hierarchy on mobile.
- Only where that is logical. The main check is that the longest word of a real
  heading fits a phone: about 350px of text width at a 390px viewport, about
  290px at 320px. If H2's desktop size is too big for H1 on mobile (long Finnish
  compounds such as "asiakkaitamme" are about 6.8× the font size wide in
  Playfair Display), use the largest size that fits and is still bigger than
  H2's mobile size; Ilme's `min` is a good fallback. Measure with the font file
  if in doubt (`fontTools`: sum of `hmtx` advance widths / unitsPerEm × px).
- `giant`: no rule, but its `min` must be larger than H1's mobile size and still
  fit on a phone (e.g. H1 mobile 44px → giant mobile 52px).
- Sizes Ilme marks `fluid: false` stay fixed; new sizes Ilme doesn't have (e.g.
  `heading-6`, `quote`) are `fluid: false` unless the design gives a mobile size.
- Spacing clamps (`settings.custom.spacingSizes`: small, medium, large,
  xlarge): take them from the design's spacers if it defines them. If it
  doesn't (a prototype or Figma with only measured positions), derive them
  from the values that recur: the common gaps inside content give small /
  medium / large, section padding gives xlarge. Ilme's own clamps all use
  the same 3.7vw slope and hit their maximum on narrow screens, so they
  aren't distinct steps: on mobile Small and Medium spacers came out equal,
  on desktop X-Large was 4px taller than Large. Write each as a clamp from
  390px to 1440px wide: slope = (max − min) / 1050 × 100 vw, intercept =
  min − slope × 390px. Example (one project): small 12→16, medium 20→32,
  large 32→48, xlarge 48→96px =
  `clamp(0.75rem, 0.657rem + 0.381vw, 1rem)`,
  `clamp(1.25rem, 0.971rem + 1.143vw, 2rem)`,
  `clamp(2rem, 1.629rem + 1.524vw, 3rem)`,
  `clamp(3rem, 1.886rem + 4.571vw, 6rem)`.
  Check the steps on the style guide's spacers at 390, 900 and 1440px.
- Spacer styles get their height only from the spacing size: in
  `_spacers.scss`, `.wp-block-spacer[class*="is-style-"] { height: 0
  !important; }`, because the spacer's own 1rem height would otherwise act as
  a minimum and hide the smaller steps on mobile.

Before deciding there are no mobile sizes, look: list the file's pages with
`get_metadata` (no node id) and check the stylesheet page and any frame named
"Mobile" for actual content.

### Styles

- `styles.typography`: body font family, `paragraph` size, paragraph line height.
- `styles.color`: body text colour and site background from the design.
- `styles.elements.heading`: heading font family, weight and colour;
  `h1`–`h6`: size preset, line height, letter spacing. A heading that uses the body
  typeface in Figma (often H5) gets its own `fontFamily`.
- `styles.elements.link`, `caption`: colours from the design.
- `styles.blocks.core/button` and its `outline` variation: colours, border,
  radius, padding, font. Then set the same values in the `--jcore-button-*`
  variables and the hover rules in `_buttons.scss`.

- Don't change shared `settings.custom` values such as `lineHeight.xs` to suit
  one heading, because Ilme's SCSS uses them too. Put the value on the heading
  instead.

### Layout widths (`settings.layout`)

Ilme ships `min(1200px, 90vw)` / `min(800px, 90vw)`. Set them from the design:

- `wideSize` = the design's container width: the content area of the desktop
  frame (e.g. x 298–1622 on a 1920 frame = `1324px`).
- `contentSize` = the main text column (e.g. the article or service text next
  to a sidebar, `872px`).
- Plain px values: the root padding (root-padding-aware alignments are on)
  gives the side margins on narrower screens, so the `90vw` is not needed.

Header, mega menu and footer follow `wideSize`, so check them against the design
after the change.

How the two work together (e.g. `contentSize` 872px, `wideSize`
1324px, root padding `spacing-sizes--medium`):

- Page titles in the heroes start at the wide layout's left edge. If the
  design puts the text column under the title, not in the middle, move the
  `contentSize` column there with `is-content-left` (see
  `references/templates.md`); a page the design centres keeps core's centring.
- A text-and-sidebar layout at the wide width (a Ruudukko grid of three
  columns, the text spanning two) gives a text column of about
  `contentSize` when the widths come from the design: (1324 − 2 × 32) / 3 × 2
  + 32 = 872px. A larger gap (e.g. `spacing-sizes--xlarge`) narrows it a
  little toward the design's sidebar width.
- The gap between Ruudukko columns is `settings.custom.ruudukko.gap`
  (Ilme: `fluid-medium`, 1–2rem). Ruudukko's own CSS has no gap; Ilme's
  `src/scss/blocks/_ruudukko.scss` applies the variable on the front end and
  in the editor. Set the design's column gap there, not on each grid.
- Blocks with their own two-column layout (e.g. a services tab panel) should
  choose their layout by their own width (a container query), not the
  screen's, so they work both at `wideSize` and at `contentSize`.

### Lists and tables

Ilme styles them in `src/scss/blocks/_lists.scss` (bullets, numbers, and an
`.arrow-list` variant with `assets/images/arrow-long.svg` as a mask) and
`_table.scss` (a faint line between rows, the header row on `light` in small
capitals, Stripes white and `light`). Use the stylesheet page's bullet, number
and table designs when filled in. Otherwise:

- Lists: look for a list in the page designs (service lists, link lists with
  arrows) and match its arrow/bullet colour, text style and item spacing,
  usually through `.arrow-list`. Bullets and numbers default to `brand`.
- Tables: build from the tokens: body text in the paragraph colour, header row
  on the light surface colour (`additional-1` or similar) in `dark` with weight
  500, borders in a visible light tint, `0.75rem 1rem` cell padding. Keep the
  stripes variation working with the same colours.
- Ilme's `core/table` `css` string has been broken (`color: … & th{ background: …`
  without `;` and `}`); write it as valid CSS.

### Text styles with no element

Figma text styles that have no theme.json element (over-heading, footer
navigation, tag cloud, quote, ingress) go in `settings.custom.textStyle`, which
WordPress exposes as CSS variables, e.g.
`--wp--custom--text-style--footer-navigation--letter-spacing`:

```json
"textStyle": {
	"overHeading": { "fontSize": "14px", "lineHeight": "20px", "fontWeight": "400", "letterSpacing": "0.04em" }
}
```

## 4. Apply the design to every block stylesheet

theme.json alone doesn't style the blocks. Every block stylesheet must be worked
through for every new design. The block designs come from a separate **block
artefact** for the project, not from the page designs; if the user hasn't given
its address, ask for it before starting this step. Go through each
`src/scss/blocks/_<block>.scss` and adapt it to the artefact: colours to the
design's slugs, fonts via `--jcore-font-family` / `--jcore-heading-font-family`,
corner radii, borders, icon colours and hover states as the design shows them.
For a block the design never shows, derive its look from the tokens and similar
elements (e.g. if the design's cards and buttons are rounded, rounded is the
default). Note which blocks you styled from the design and which you derived.

## 5. Check

- `python3 -c "import json; json.load(open('theme.json'))"`
- `success`, `warning`, `failure`, `dark` and `light` are in the palette.
- Every slug referenced in SCSS, templates and theme.json exists. Run the grep
  from step 1 again and compare with the defined slugs. Ilme itself may already
  reference slugs it never defined (e.g. `lightgrey`, `lightblue` in
  `_forms.scss`): report those rather than silently inventing colours.
- `pnpm run build` passes.

## 6. Report

Give the user a table of the tokens as they ended up in theme.json, and a separate
list of every value that did **not** come from Figma (hover states, fluid minimum
sizes, missing line heights) so they can confirm or correct it.
