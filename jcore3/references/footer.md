# Footer

The footer's content is edited in **global content posts**, not in the
template. Only the structure and the logo live in the theme. Reference
project: `chefsdagarna`.

## Pieces

- **jcore-maailma** (normal plugin, pulled from the server) provides global
  content: a "Global Content" post type (`jcore-global-content`, the slug comes
  from the title), the `jco/global-content` block, and the Twig function
  `jcore_global_content('slug')`. Without the plugin the Twig function is a
  stub that prints nothing.
- **`lohko/footer`** has four areas, `left`, `middle`, `right` and `bottom`, plus
  a copyright row. Each area prints, in order:
  1. the global content post with the slug `footer-<area>`
     (`footer-left`, `footer-middle`, …)
  2. the WordPress menu at the location `footer_<area>`
     (the theme registers `footer_left`, `footer_middle`, `footer_right`,
     `footer_bottom` in `includes/setup.php`)
- **Lists of links are WordPress menus**, e.g. the footer navigation is the
  `footer_right` menu. `lohko/footer` prints an area's menu itself. To show a
  menu anywhere else (inside a global content post, a template part), use
  **`lohko/simple-menu`** with its menu location setting.
- **The logo goes straight into the template**, not into global content. In
  chefsdagarna `lohko/footer` got `logo` and `logoAlt` settings: a path to an
  image in the theme (e.g. `assets/images/footer-logo.png`) or a full URL,
  shown at the top of the left area and linked to the front page. Copy that
  change from chefsdagarna's Lohko if the project's copy doesn't have it.

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

- Global Content posts titled `footer-left`, `footer-middle`, … with what the
  design shows in each area (text, address, contact details, social links).
- Menus assigned to the `footer_*` locations.

The footer's look (grid, spacing, decorative patterns) is styled in Lohko's
`src/footer/style.scss` in the project copy, using theme.json presets.
