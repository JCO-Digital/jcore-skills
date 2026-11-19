# The technical plan ("tekninen suunnittelu")

Each project has a Google Sheet with a "Tekninen suunnittelu" tab: one row per
part of the site, one column per question about it. Read it before building
(see SKILL.md, "Start here"). The same file often has a WBS tab with the hour
estimates per part. This file says what each row means in JCORE3 and where in
this skill it's built.

## The columns: what to take from them

| Column | Use it for |
|---|---|
| Mitä se on ja miten se toimii? (tehtävänkuvaus) | What the part does for the user. Compare with the design; a part in the plan but not in the design (or the reverse) is a question. |
| Miten toteutetaan? (tekninen määrittely) | The agreed solution: theme, Lohko block, pattern, plugin, ACF. Follow it; if building shows it won't work, ask before changing it and update the cell. |
| Mobiili | How it behaves on phones. Build it in from the start (e.g. tabs become an accordion, the header becomes a full-screen menu). Empty: ask. |
| Plugari? / Hinta? | A plugin is a normal plugin (pulled from the server, never edited); only Lohko is project code. A paid plugin needs the customer's licence. |
| Mitä tarvitsemme? | Material, texts, images, accounts, licences, API keys still missing: ask for them before the part is built. |
| Aika-arviot (front-end, fullstack, graafinen, UX/UI, copy) | The budget. A part clearly growing over its estimate: tell the user before going on. |
| Huomiot | Read as requirements. |
| Kuka voi hallinnoida ja ylläpitää | Decides where content lives: editable by the customer in the editor (blocks, patterns, menus, a CPT) or fixed in the theme. Customers don't edit templates. |
| Mahdollisuudet? / Rajoitteet? / Vaihtoehtoiset ratkaisut? / Miksi suosittelemme? | Context for the decision; keep to the recommended solution unless the user decides otherwise. |

## The rows: where they're built

**One row per part that needs its own styling.** A group row (Hero, Lohko
Blocks, Patterns) is a heading; each variant under it gets its own row with its
own solution, mobile behaviour and estimate. For example, a project with three
page heroes has three hero rows, not one.

- **Page heroes:** one row each (e.g. plain, photo, navy). Say which one is the
  primary, i.e. the one the default page template (`page.html`) uses, and which
  templates the others belong to (e.g. `page-photo-hero`, `page-navy-hero`).
  Heroes are separate parts, built like blocks, even when they're template parts.
- **Single post and CPT heroes** (article hero, person hero) don't get a row of
  their own: they go in the row for that single post or CPT. That row's
  description says what the single page's hero looks like (which design, what
  it shows) and whether the post type has an archive page (and its URL) or
  only appears in lists and blocks.

**Plugins at the end.** The last section lists every plugin in
`wp-content/plugins`, one row each, including hosting and service plugins that
come with the server (e.g. SpinupWP, SMTP2GO) and the JCORE plugins. Give the
folder name and version, free / paid / own (J&Co), what it's used for in the
project, what's needed (licence, account, API key) and who maintains it.

| Row | JCORE3 solution | Read |
|---|---|---|
| Asennus | jcore-cli project from the template, `jcore.toml`, Lohko copied in, other plugins from the server, `deploy.yml` | SKILL.md "Plugins", "Git branches" |
| Kieliversiot | One language: site language + the project's .po files. Several: a language plugin and a language switcher; ask which plugin the project uses (Silta supports Polylang) | SKILL.md "Translations" |
| Teema ja tyylit | theme.json tokens from the design (colours, text styles, spacing scale, layout widths), then every base block | `references/theme-json.md`, `references/base-blocks.md` |
| Fontit | Self-hosted files in `assets/fonts/` referenced from theme.json; a font kit (e.g. Adobe) only by name in theme.json, enqueued in the theme | `references/theme-json.md` |
| Navigaatio, Hampparimenu, Language switcher | Lohko navigation blocks in the header part (mega menu driven by the WP menu, mobile full-screen menu); styled in the theme | SKILL.md "Blocks", `references/templates.md` |
| Footer | Lohko footer block, global content, footer menus; styled in the theme | `references/footer.md` |
| Buttons | theme.json base + outline, the rest as block styles in `includes/gutenberg.php` and `blocks/_buttons.scss` | SKILL.md "Where styling lives" |
| Forms | Gravity Forms (normal plugin), styled in `partials/_forms.scss` | — |
| Breadcrumbs, Social Media Connections, Meta-data | Yoast (normal plugin): breadcrumbs in the hero parts, social and meta in Yoast's settings | `references/templates.md` |
| Search | The search template from translatable patterns, results with a type label | `references/templates.md` |
| Booking, Video | Booking: usually an external service embedded; ask. Video: core embed (styled), or a Lohko block if it needs more | `references/base-blocks.md` |
| Lohko Blocks (contacts, boxes, sliders…) | One Lohko block per part that a pattern can't do (more than two levels of groups, data, interaction); styles in the block | SKILL.md "Blocks", "Styling Lohko blocks" |
| Patterns | Flat compositions of blocks for sections and page layouts, in the theme's `patterns/` | SKILL.md "Pattern or Lohko blocks?" |
| Hero (one row per page hero) | A template part per hero (plain, photo, navy…), the primary one in `page.html`, the others in selectable page templates; a front page hero pattern. Single and CPT heroes go in their post type's row | `references/templates.md` |
| Sivupalsta (sidebar) | Ruudukko grid in a pattern (text 2/3, sidebar 1/3), not a template column | `references/theme-json.md` "Layout widths" |
| Related posts | Dynamic Archive latest posts, related mode | `references/templates.md` |
| Gutenberg Blocks (only if something special) | The base blocks are always styled (style guide); list here only what differs from the usual | `references/base-blocks.md` |
| Custom post types, archives | ACF post types and taxonomies in `acf-json`, lists with Dynamic Archive and the theme's tease; the single template with its hero. The description states the hero design and whether there's an archive | `references/post-types.md` |
| Lisäosat (last section) | One row per plugin in `wp-content/plugins` (see "Plugins at the end") | SKILL.md "Plugins" |

## While building

- Before each part, read its row again: solution, mobile, who maintains it.
- After a part is done and approved, update the plan with what was built and
  any decision taken (or list the changes for the user to enter, if the
  Google Sheets connector isn't available).
- A row with no design and no definition is not built on assumptions: ask,
  propose a solution built from the design's tokens, and agree on it first.
