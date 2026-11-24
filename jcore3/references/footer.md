# Footer

The footer's content is edited in **global content posts**, not in the
template. Only the structure and the logo live in the theme.

## Pieces

- **jcore-maailma** (normal plugin, pulled from the server) provides global
  content: a "Global Content" post type (`jcore-global-content`, the slug comes
  from the title), the `jco/global-content` block, and the Twig function
  `jcore_global_content('slug')`. Without the plugin the Twig function is a
  stub that prints nothing.
- **`lohko/footer`** has three areas in its top row, `left`, `middle` and
  `right`, a `bottom` area below them, and a copyright row. Each area prints,
  in order:
  1. the global content post with the slug `footer-<area>`
     (`footer-left`, `footer-middle`, …)
  2. the WordPress menu at the location `footer_<area>`, dashes as
     underscores (the theme registers `footer_left`, `footer_middle`,
     `footer_right`, `footer_bottom` in `includes/setup.php`)
- **One column = one area = one global content post.** Never put columns
  (core columns, a Ruudukko grid) inside a footer global content post to get
  more columns than the footer has: the editor can break them, and the
  columns don't line up with the footer's own grid.
- **More than three columns in the design (e.g. four columns in a row in
  the Figma footer): a change in the project's Lohko copy.** Master Lohko
  stays at three columns; don't take this change back to it. In the
  project's `src/footer/`:
  - `block.json`: an attribute `columns` (number, default 3).
  - `render.php`: the list of areas for the number of columns, e.g. with 4
    `left`, `middle`, `middle-2`, `right`, `bottom`, passed to the template
    as `areas`, and a wrapper class `columns-4`.
  - `view.twig`: loop `areas` instead of the fixed list; the menu location
    is `'footer_' ~ area|replace({'-': '_'})`.
  - `style.scss`: the grid for `.columns-4`
    (`"left middle middle-2 right"` and the bottom row under all four).
  - `edit.js`: a Columns control, so the setting shows in the editor.

  Then in the theme: the footer part sets `"columns":4`, `includes/setup.php`
  registers the new menu location (`footer_middle_2`), and the new area gets
  its own global content post (`footer-middle-2`). The block renders on the
  server, so the change needs no deprecation. More columns than four: add
  the next area the same way.
- **Lists of links are WordPress menus**, e.g. the footer navigation is the
  `footer_right` menu. `lohko/footer` prints an area's menu itself. To show a
  menu anywhere else (inside a global content post, a template part), use
  **`lohko/simple-menu`** with its menu location setting.
- **The logo goes straight into the template**, not into global content.
  `lohko/footer` has `logo` and `logoAlt` settings: a path to an image in
  the theme (e.g. `assets/images/footer-logo.svg`) or a full URL, shown at
  the top of the left area and linked to the front page. An SVG in the
  theme is inlined, so `fill="currentColor"` follows the footer's text
  colour. **Left empty, the footer shows the site logo** (Customizer → Site
  Identity, without WordPress's own link around it), so set `logo` when the
  footer needs a light version on a dark background. A project whose Lohko
  copy is older than this may lack the settings: copy `src/footer/` from
  master Lohko.

## Template part

`parts/footer.html`: a group with `tagName: footer` (colours, link colour) that
holds `lohko/footer` aligned wide. Templates place the part with
`{"slug":"footer","tagName":"div"}`, so the page has a single `<footer>`.
Leaving `tagName` out is not enough: a template part then takes its area's tag
(`footer` for the footer area, `header` for the header area), which nests two
landmarks. The same applies to the header part.

```html
<!-- wp:group {"tagName":"footer","backgroundColor":"…","textColor":"…","layout":{"type":"constrained"}} -->
<footer class="wp-block-group …"><!-- wp:lohko/footer {"copyrightToCurrent":true,"logo":"assets/images/footer-logo.svg","logoAlt":"…","align":"wide"} /--></footer>
<!-- /wp:group -->
```

## Content setup (per site, in the admin)

- Global Content posts titled `footer-left`, `footer-middle`, … (and
  `footer-middle-2` with four columns) with what the design shows in each
  area (text, address, contact details, social links). Maailma names the
  slug after the title, so title each post with its slug.
- Menus assigned to the `footer_*` locations.
- A theme that styles the footer's grid itself (`partials/_footer.scss`)
  must match the specificity of Lohko's `.columns-4` rule
  (`.wp-block-lohko-footer.columns-4`), or Lohko's grid wins.

The footer's look (grid, spacing, decorative patterns) is styled in Lohko's
`src/footer/style.scss` in the project copy, using theme.json presets.
