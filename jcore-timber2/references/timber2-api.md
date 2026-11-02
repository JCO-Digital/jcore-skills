# Timber 1 → 2 API reference

This condenses <https://timber.github.io/docs/v2/upgrade-guides/2.0/>. Notes marked *(source)* were checked against `vendor/timber/timber/src` of Timber 2.3.1, so they describe what the code does, not only what the guide says. When in doubt, read the vendor source of the project you are working on.

## Requirements

- PHP: 7.4+ for Timber 2.0–2.2. **^8.1 for 2.3+** (and Twig ^3.17). WordPress 5.3+.
- Composer only. The `timber-library` plugin is dead. If a site still has it in `wp-content/plugins` (or an mu-plugin loader requiring it), remove it and use the Composer package.

## Bootstrapping

| Old | New |
| --- | --- |
| `new Timber\Timber()` / `$timber = new Timber\Timber()` | `Timber\Timber::init()` (returns nothing). |
| `Timber::$dirname = 'views'` / `Timber::$locations` | Still supported. `timber/locations` filter is the modern way. |
| `Timber::$autoescape`, `Timber::$cache`, `Timber::$twig_cache` | `timber/twig/environment/options` filter (`$options['autoescape'] = 'html'`, `$options['cache'] = true`). |
| `Timber::add_route()` | Removed. `composer require upstatement/routes` and use `Routes::map()`. |
| `Timber\Request`, `{{ request.get.x }}` | Removed. Read `$_GET` in PHP (sanitize it) and put it in the context. |
| `twig/cache-extension` | `twig/cache-extra`. |

## Class aliases (all removed except `Timber`)

`TimberPost`, `TimberImage`, `TimberTerm`, `TimberUser`, `TimberMenu`, `TimberMenuItem`, `TimberComment`, `TimberSite`, `TimberTheme`, `TimberHelper`, `TimberImageHelper`, `TimberURLHelper`, `TimberLoader`, `TimberCore`, `TimberArchives`, `TimberTwig`, `TimberIntegrations`, `TimberFunctionWrapper`, `TimberCommand` → `Timber\<Name>`. `TimberPostGetter`, `TimberTermGetter`, `TimberQueryIterator`, `TimberRequest`, `TimberPostCollection`/`Timber\PostsCollection` are gone with no alias.

Twig classes: `Twig_Function`/`Twig_SimpleFunction`/`Timber\Twig_Function` → `Twig\TwigFunction`. `Twig_Filter`/`Twig_SimpleFilter`/`Timber\Twig_Filter` → `Twig\TwigFilter`. `Twig_Environment` → `Twig\Environment`. `Twig_SimpleTest` → `Twig\TwigTest`.

## Getting objects

No `new` for Post/PostQuery/Term/User/Comment/Menu. Use the factories:

| Old | New | Notes |
| --- | --- | --- |
| `new Post()` / `new Post( $id )` | `Timber::get_post()` / `Timber::get_post( $id )` | `null` on failure. Accepts ID, numeric string, `WP_Post`, array with `ID`. |
| `Timber::query_post()` | `Timber::get_post()` | |
| `new PostQuery( $args )`, `Timber::get_posts( $args, 'Class', true )` | `Timber::get_posts( $args )` | Always a `PostCollectionInterface`. `->to_array()` for array. Query strings → `_doing_it_wrong` (still run). *(source)* Empty/false argument → main `$wp_query`. Numeric array → posts by ID. `numberposts` → use `posts_per_page`. `['merge_default' => true]` option merges with main query vars. |
| `new Term( $id )`, `Timber::get_term( $id, 'tax', 'Class' )` | `Timber::get_term( $id )` | Taxonomy is inferred. No-arg form returns only the queried term. |
| `Timber::get_terms( $tax, $args, 'Class' )` | `Timber::get_terms( $args )` | *(source)* A taxonomy string or array of taxonomy names still works. `hide_empty` default flipped to `true`. |
| `new User( $id )` | `Timber::get_user( $id )` | also `get_users()`, `get_user_by()` |
| `new Comment( $id )` | `Timber::get_comment( $id )` | |
| `new Menu( 'slot' )` | `Timber::get_menu( 'slot' )` | Argument required. `null` when no menu. The pages fallback is `Timber::get_pages_menu()`. |
| `new Image( $id )` | `Timber::get_image( $id )` | `null` unless it's an image attachment. `Timber::get_attachment()` for any file. `Timber::get_external_image( $url )` sideloads a URL. |
| `Timber::get_pagination()` | `{{ posts.pagination }}` | |
| `Timber::get_context()` | `Timber::context()` | `Timber::context( $extra )` merges. `Timber::context_global()` skips post/posts. |
| `Timber::fetch()` | `Timber::compile()` | |

## Twig functions

| Old | New |
| --- | --- |
| `Post(x)`, `TimberPost(x)` | `get_post(x)`, `get_posts(ids_or_args)` |
| `Image(x)`, `TimberImage(x)` | `get_image(x)` (`get_attachment`, `get_attachment_by('url', url)`, `get_external_image(url)`) |
| `Term(x)`, `TimberTerm(x)` | `get_term(x)`, `get_terms(args)` |
| `User(x)`, `TimberUser(x)` | `get_user(x)`, `get_users(args)` |
| `{{ wp_head }}`, `{{ wp_footer }}` | `{{ function('wp_head') }}`, `{{ function('wp_footer') }}` |

*(source)* The old capitalised functions are still registered and log deprecations.

Tags/filters: `{% filter x %}` → `{% apply x %}…{% endapply %}`, `{% spaceless %}` → `{% apply spaceless %}`. `|get_type` is removed. `|get_class` and `|print_r` are deprecated.

Actions: `{% do action('x', a) %}` no longer appends the context. Add the arguments you need and lower `accepted_args`.

`timber/twig/functions` and `timber/twig/filters` now receive and return **arrays** of definitions. Code that does `$twig->addFunction()` inside them fatals with "Call to a member function addFunction() on array". Do environment work in `timber/twig`.

## Removed/renamed object methods

Twig `obj.get_x` that no longer exists does **not** error *(source: `Core::__get` falls back to `meta()` and then `false`)*. It just renders nothing. That makes these the most dangerous leftovers.

**Post**: `get_author`→`author`, `get_categories`→`categories`, `get_category`→`category`, `get_children`→`children`, `get_comment_count`→`comment_count`, `get_comments`→`comments`, `get_content`→`content`, `get_edit_url`→`edit_link`, `get_format`→`format`, `get_image('f')`→`get_image(post.meta('f'))`, `get_link`/`get_permalink`/`permalink`→`link`, `get_modified_author`/`_date`/`_time`→`modified_*`, `get_next`→`next`, `get_prev`→`prev`, `get_pagination`→`pagination`, `get_parent`→`parent`, `get_path`→`path`, `get_post_type`→`type`, `get_tags`→`tags`, `get_terms`→`terms`, `get_thumbnail`→`thumbnail`, `get_title`→`title`, `audio`/`video`→`get_media_embedded_in_content()`. Deprecated, but still present: `preview`/`get_preview`→`excerpt`, `get_field`/`import_field`→`meta`, `update`→`update_post_meta()`.

`post.terms('category')` → `post.terms({taxonomy: 'category'})`. Options go in a second hash: `post.terms({...}, {merge: false})`.

**Term**: `get_link`→`link`, `get_path`→`path`, `get_term_from_query`→`Timber::get_term()`. Deprecated: `get_children`→`children`, `get_edit_url`→`edit_link`, `get_field`/`get_meta_field`→`meta`, `get_posts`→`posts`. `term.posts(-1, 'book')` → `term.posts({post_type: 'book', posts_per_page: -1})`. An integer alone still works.

**MenuItem**: `get_link`/`permalink`→`link`, `get_path`→`path`. Deprecated: `external()`→`is_external`, `get_children`→`children`, `get_field`→`meta`. New: `target`, `is_target_blank`. **Menu**: new `current_item()`, `current_top_level_item()`.

**Image/Attachment**: `get_src`/`get_url`/`url`→`src`, `get_attachment_info`→`get_info`, `is_image()` removed. Deprecated: `get_pathinfo`→`pathinfo`, `get_dimensions*`→`width`/`height`, `get_post_custom`→`meta`. `caption` and `sizes` are methods now. Twig is unchanged. New: `size()` (bytes), `extension()`.

**User**: `first_name`/`last_name`/`description` properties → `user.meta('first_name')`. `name` is a method, and Twig is unchanged. Deprecated: `get_field`/`get_meta`/`get_meta_field`→`meta`.

**Site**: `get_link`/`get_url` removed → `link`. Deprecated: `meta`→`option`, `pingback`→`pingback_url`, `url()`→`link()` (*(source)* the `site.url` property still works).

**Helper**: `function_wrapper`→`{{ function('x') }}`, `trim_words`/`close_tags`→`TextHelper::`, `paginate_links`→`Pagination::paginate_links`, `get_current_url`→`URLHelper::get_current_url`, `get_comment_form`→`{{ function('comment_form') }}`, `filter_array`→`wp_list_filter`. `TextHelper::starts_with`/`ends_with` → PHP 8 `str_starts_with`/`str_ends_with`.

## Meta / ACF

- `post.meta('f')` = ACF `get_field()` (formatted per the field's return format). `post.raw_meta('f')` = DB value. `post.meta('f', {format_value: false})` is unformatted.
- `post.custom` → `false` plus a deprecation *(source)*. Use `meta()`/`raw_meta()` with no args to list all values (dev only).
- Property access `post.f` → `__get` → `meta('f')`. It works, but loses to real methods and properties with the same name.
- ACF values are **not** converted to Timber objects unless `add_filter( 'timber/meta/transform_value', '__return_true' )` (global) or `meta('f', {transform_value: true})` (per field). When transformed: image → `Image`, file → `Attachment`, gallery/relationship/post object → arrays of `Post`, taxonomy → `Term`s, user → `User`s, date → `DateTimeImmutable`. Without it: `get_image(post.meta('img'))`, `get_posts(post.meta('rel'))`.
- ACF blocks rendered by jcore2's `Block` class get `get_fields()` arrays, not Timber objects. That is unchanged.

## Excerpts

`PostPreview` → `PostExcerpt`. `post.preview` → `post.excerpt`. Hash args: `post.excerpt({words: 30, read_more: 'Lue lisää', end: '…', always_add_read_more: false, force: false})`. The chained `post.preview.length(30).read_more('…')` style still works on `post.excerpt`. Filters: `timber/post/preview/read_more_class` → `timber/post/excerpt/read_more_class`, `timber/post/get_preview/read_more_link` → `timber/post/excerpt/read_more_link`. New: `timber/post/excerpt/defaults`.

## Hooks

| Old | New |
| --- | --- |
| `timber_context` | `timber/context` (*(source)* old one still fires via `apply_filters_deprecated`) |
| `get_twig` | `timber/twig` (same) |
| `timber/twig/functions`, `timber/twig/filters` used with `$twig->add…` | `timber/twig` (the former now take and return arrays) |
| `timber_render_file` / `timber_render_data` | `timber/render/file` / `timber/render/data` |
| `timber_compile_file` / `_data` / `_done` | `timber/compile/file` / `/data` / `/done` |
| `timber_post_get_meta_field_pre` / `timber_post_get_meta_pre` | `timber/post/pre_meta` / `timber/post/pre_get` |
| `timber_post_get_meta_field`, `timber_post_get_meta` | `timber/post/meta` |
| `timber_term_get_meta_field`, `timber_term_get_meta`, `timber/term/meta/field` | `timber/term/meta`. The old `timber/term/meta` (from `get_meta_values`) is now `timber/term/get_meta_fields`. |
| `timber_comment_get_meta*`, `timber_user_get_meta*` | `timber/comment/(pre_)meta`, `timber/user/(pre_)meta` |
| `timber_site_set_meta` | `timber/site/update_option` |
| `Timber\PostClassMap` | `timber/post/classmap` (also new `timber/term/classmap`, `timber/menu/classmap`, `timber/menuitem/class`, `timber/user/class`, `timber/comment/classmap`, `timber/pages_menu/class`) |
| `timber/loader/paths` | `timber/locations` |
| `timber/cache/location` | `cache` key in `timber/twig/environment/options` |
| `timber/URLHelper/*` | `timber/url_helper/*` |
| `timber/class/posts_iterator`, `timber/get_posts/mirror_wp_get_posts`, `timber_post_getter_get_posts` | removed. Use `Post::setup()` / `teardown()` in a class-mapped Post. |

## Context

- Timber 2 adds `post` (singular), `posts` (archives, a `PostQuery` of the main query), `term` (term archives) and `author` (author archives) to `Timber::context()` automatically.
- If you assign your own `$context['post']`, call `$post->setup()` when plugins rely on the global `$post` (WooCommerce, some page builders).
- In partials rendered inside loops (WooCommerce loop templates, REST HTML), use `Timber::context_global()` to avoid re-running the main-query part.

## Other

- External images go to `uploads/external/` (`timber/sideload_image/subdir`).
- WP-CLI: `wp timber clear_cache` → `wp timber clear-cache [timber|twig]`.
- Twig `date` filter/function now use the WP timezone and date format. Don't set timezones in templates.
- `URLHelper::get_params()` returns `false` (not `null`) for a missing index. `is_local`/`is_external` compare the hostname only.
