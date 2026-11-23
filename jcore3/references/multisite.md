# Multisite projects

The rest of this skill assumes **one site**. In a multisite project several
sites share **one theme**, so check each rule for whether it hard-codes something
that differs per site. The examples come from a network of two public sites
that differ only in colours and logo (the network root is not public).

## The rule

| Same on every site: **in the theme** | Differs per site: **on each site, or per site in code** |
|---|---|
| Templates, parts, patterns | Colours (see below) |
| theme.json tokens (fonts, sizes, spacing, layout) and palette **slugs** | Palette **values** |
| SCSS, Lohko blocks | Logo (Site Logo) and favicon (Site Icon) |
| | Site name, menus, global content posts |
| | Plugin settings: Polylang languages, Yoast, Gravity Forms |

Never put a per-site thing in the theme as a file path or a literal: no
`logo: "assets/images/…"` on `lohko/footer`, no single `assets/favicon/`, no
site name in a template. Use what WordPress already keeps per site (Site Logo,
Site Icon, `get_bloginfo( 'name' )`), or the per-site map below.

## Setup

- `jcore.toml`: `multiSite = true`, every site's local domain in `domains`, and
  a `replace` pair for **each** site's domain (network root and every site).
  `env-values.toml` needs `multisite = true`, otherwise `.env` has no
  `MULTISITE` and the pulled database shows only the root. Run `jcore restart`
  after changing either.
- Plugins are **network activated**. Pull them as usual (`jcore pull`).
- **Subsites on their own domains** (not subdomains of the network root):
  set `define( 'COOKIE_DOMAIN', '' );` in the server's `wp-config.php`,
  otherwise WordPress sets cookies for the root's domain and logging in on a
  subsite fails with "incorrect username or password". Each site's address is
  set under Network Admin → Sites → Edit (both the domain and the Site Address,
  with `https`).
- WP-CLI works on one site at a time: always pass `--url=<site domain>`
  (`wp cache flush`, `wp option`, `wp post list`…). Without it, the command
  runs on the network root.

## Per-site code: a map from blog ID to site key

Keyed by **blog ID**, not domain: the IDs are the same in every environment
(the database comes from upstream), the domains are not.

```php
function site_key(): ?string {
	$sites = apply_filters( '<project>_sites', array( 2 => 'site-a', 3 => 'site-b' ) );
	return $sites[ get_current_blog_id() ] ?? null; // null: network root.
}
```

Everything per site hangs off this (e.g. in the theme's `includes/sites.php`):

- **Colours.** theme.json holds the shared palette with **role-named slugs**
  (`brand`, `secondary`, `accent`, never `orange`: one slug is a different
  colour on each site). Each site overrides values in `sites/<key>.json`
  (`{ "palette": { "brand": "#c8102e" } }`), merged into the palette by slug
  on the `wp_theme_json_data_theme` filter. Everything that uses the presets,
  in the editor too, follows. Two catches:
  - `WP_Theme_JSON_Data::get_data()` returns presets **grouped by origin**
    (`palette['theme']`), while `update_with()` takes a **plain list** and
    replaces the whole palette. Rebuild the full list with only the site's
    values changed.
  - Put the files in `sites/`, not `styles/`: files in `styles/` show up as
    style variations in the Site Editor.
- **Body class** `site-<key>` for the rare per-site SCSS rule.
- **Text that names the site** in a shared template, e.g. the footer's
  copyright: fill it from `get_bloginfo( 'name' )` on `render_block_data`
  instead of an attribute in the template.

## Content and settings: each site separately

- **Global content** (hat, `footer-*`) and **menus** are per site, and per
  language with Polylang. Content setup instructions say "on each site".
- **Polylang** is configured per site: languages, default language, and the
  language of existing content (`set_language_in_mass`).
- **Menu locations with Polylang** are stored per language in Polylang's own
  `polylang` option (`nav_menus[<theme>][<location>][<lang>]`), not in the theme
  mod. `wp menu location assign` therefore shows nothing; assign them in
  Appearance → Menus → Manage locations (one column per language), or set the
  option.
- **Templates saved in the Site Editor** are stored in that site's database
  and override the theme file on that site only, so the sites drift apart.
  The rule in `templates.md` (don't save templates in the Site Editor) matters
  twice as much here.
- The **network root** is usually not a public site: it falls back to
  theme.json's own values and needs no content.

## Checking

Check every change on **every site**, not only the first one: flush the object
cache per site (`wp cache flush --url=…`), then compare, e.g.
`curl -sk https://<site>/ | grep -o -- '--wp--preset--color--brand: *[^;]*'`.
