# jcore2 timber2 branches

State as of 2026-11-02. Re-check with `git fetch` before relying on it. Branches move.

| Base branch | timber2 branch | Port commit(s) | Lag behind base at time of writing | Projects already on it |
| --- | --- | --- | --- | --- |
| `bream` (old `JCOre` namespace) | `timber2/bream` | `813ba94` build(deps): update timber/timber to version 2.0, `f163752` style | 0 | fiskarsmuseum |
| `fluffycritter` | `timber2/fluffycritter` | `9bf437d` refactor: Refactor Timber usage to Timber 2, plus later merges of `origin/fluffycritter` | 6 commits | fonzit (fter/flexam/fatman are on Timber 2 with plain `fluffycritter`, so check those before copying them) |
| `grasshopper` | `timber2/grasshopper` | `1711163` refactor: Refactor Timber usage to Timber 2 | 6 commits (`e3cf659`…`d8d6100`). **The merge conflicts in `includes/timber.php`** | kopikirjastot |
| others (`anchovy`, `brill`, `eel`, `herring`, `perch`, `pike`, `roach`, `zander`, `aviation`, `bellini`, `cosmopolitan`, `daiquiri`, `elduelo`…) | none | n/a | n/a | n/a |

Use `git show <commit>` on the port commits as worked examples. Each generation changed the same set of files:

- `functions.php`: `new \Timber\Timber()` → `\Timber\Timber::init()`
- `includes/timber.php`: menus → `Timber::get_menu()`, `Timber\Twig_Function` → `Twig\TwigFunction`, the `timber_context` hook → `timber/context`, and `timber/twig` (bream also had the old `get_twig` hook)
- `404.php`, `archive.php`, `search.php`, `singular.php`, `author.php`, `home.php`: `new Post/PostQuery` → `Timber::get_post(s)`; `singular.php` stops reading `$context['posts'][0]`
- `blocks/class-*.php`: `new \Timber\PostQuery( $args )` → `Timber::get_posts( $args )`, `new \TimberImage( $id )` → `Timber::get_post( $id )` (or `get_image`), `Timber::get_context()` → `Timber::context()`
- `includes/dynamic-archive.php`: `Timber::fetch` → `Timber::compile`, `new Post` → `Timber::get_post`
- `includes/woo-functions.php`: `timber_set_product()` moved from the global namespace into `jcore\` (so Twig calls `fn('jcore\\timber_set_product', post)`)
- `woocommerce.php`: `Timber::get_posts( $ids )` only when `$ids` is non-empty (an empty array means "main query" in Timber 2!), `new Timber\Term()` → `Timber::get_term()`
- `woocommerce/loop/*.php`: `Timber::context()` → `Timber::context_global()`
- Twig: `Image()`/`TimberImage()` → `get_image()`, `post.get_thumbnail` → `post.thumbnail`, `child.get_link` → `child.link`, `{% filter %}` → `{% apply %}`

## Known leftovers in the existing ports

Fix these when you touch the branch. They are real Timber 1 code the ports missed:

- `views/single-ld.twig:11` (`timber2/grasshopper`, `timber2/fluffycritter`): `post.custom.lesson_title` → `post.meta('lesson_title')`. This only affects LearnDash sites.
- `includes/timber.php` `timber_image` Twig test: `get_class( $value ) === 'Timber\Image'` is false for subclasses (e.g. a class-mapped image). `$value instanceof \Timber\Image` is the robust version. This is harmless until someone adds a class map.
- *(Fixed locally 2026-11-02 in merge `7c30067` + fix `5721ac1`, which also replaced `post.custom` in single-ld.twig. Check whether they were pushed.)* `timber2/grasshopper` lacked the 6 newest `grasshopper` commits (WP_HTML_Processor refactor, `jcore_dynamic_archive_query_args` filter, the `data-jfocus` quote fix, REST locale languages, the Polylang archive loader fix, and the `jcore_post_preview` filter). A project currently on the `grasshopper` tip (e.g. bojaco at `d8d6100`) would regress if switched without merging first.

## Empty-array gotcha

`Timber::get_posts( array() )` and `Timber::get_posts( $ids )` with empty `$ids` do **not** return an empty collection. They fall back to the main query. Guard ID lists (related/upsell products, ACF relationship fields) with `! empty( $ids )` before calling, as the jcore2 ports do in `woocommerce.php`. In Twig, use `{% if ids %}{% for p in get_posts(ids) %}…{% endif %}`.
