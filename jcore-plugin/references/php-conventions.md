# PHP conventions

How code inside a JCORE plugin is organised, with the reasoning. The templates in `templates/` implement all of this; jcore-turva is the full worked example.

## Contents

- Naming
- Layout and bootstrap
- Database tables and migrations
- REST controllers
- Admin screens
- Blocks
- Post types, taxonomies and meta
- Translations
- Uninstall
- Security checklist

## Naming

Everything derives from the slug `jcore-<word>`:

| Thing | Form | Turva example |
| --- | --- | --- |
| Namespace | `Jcore\<Word>` | `Jcore\Turva` |
| Constants | `JCORE_<WORD>_*` | `JCORE_TURVA_PATH` |
| Functions, globals | `jcore_<word>_*` | `jcore_turva_uninstall_site()` |
| Options, transients | `jcore_<word>_*` | `jcore_turva_settings` |
| Hooks the plugin fires | `jcore_<word>_*` | `jcore_turva_hide_login_errors` |
| Text domain | `jcore-<word>` | `jcore-turva` |
| REST namespace | `jcore-<word>/v1` | `jcore-turva/v1` |
| Block names | `jco/<block>` | `jco/portal-slot`, `jco/global-content` |
| Hidden post meta | `_jcore_<word>_*` | `_jcore_portti_start_date` |

PHPCS enforces the prefixes and text domain (`phpcs.xml.dist`). Older plugins have names that predate this (Turva's tables are `jcore_security_*`, Maailma's post type is `jcore-global-content`). **Never rename stored names on a released plugin.** Live data depends on them. Use the convention for new names only.

## Layout and bootstrap

```
jcore-<word>.php           header, constants, vendor autoload, class autoloader, boot
uninstall.php              standalone cleanup
includes/class-plugin.php  Plugin::boot() wires every component
includes/class-*.php       one class per file, Jcore\<Word>\Foo_Bar → class-foo-bar.php
includes/<sub>/class-*.php Jcore\<Word>\Rest\Settings_Controller → rest/class-settings-controller.php
views/                     PHP templates (admin mount points)
src/                       JS/SCSS sources, built into build/
languages/                 .po committed; .pot/.mo/.json generated
```

- The main file stays declarative. Constants are `*_VERSION`, `*_FILE`, `*_PATH` (trailing slash, from `plugin_dir_path`) and `*_URL`. The `*_VERSION` constant must keep exactly the form `define( 'JCORE_X_VERSION', '1.2.3' );`, because foonver's version-sync rewrites any version string that follows the word "version" in that file. Don't put other version-looking strings in the main file.
- Order matters: constants → `vendor/autoload.php` (only `if ( is_readable(...) )`) → `spl_autoload_register` → `register_activation_hook` (if needed, pointing at a static method like `Database::install`) → `plugins_loaded` → `Plugin::instance()->boot()` → `jcore_plugins_loaded` filter at file scope.
- `jcore_plugins_loaded` lets the JCORE theme and other JCORE components discover which plugins are active (`$plugins['jcore-<word>'] = __DIR__`). Every JCORE plugin registers it.
- `Plugin` is a `final` singleton. Components are small classes with a static `init()`/`register()`, or are hooked directly from `boot()`. Admin-only wiring goes inside `if ( is_admin() )`.
- Every PHP file starts with the file docblock (`@package Jcore\<Word>`), the `namespace`, then `if ( ! defined( 'ABSPATH' ) ) { exit; }`.
- PHP 8.2 features are fine and used: typed properties, return types, `?Type`, named arguments (UpdateConfig uses them), `str_starts_with`, `readonly`.

## Database tables and migrations

Only add tables when options, post meta or a custom post type really won't do. When you do, follow Turva's `includes/class-database.php`:

- `private const DB_VERSION = '1.0';` plus `DB_VERSION_OPTION = 'jcore_<word>_db_version'`.
- `TABLES` maps short names to unprefixed table names. `Database::table( 'reports' )` returns `$wpdb->prefix . …`, so no other code concatenates table names.
- `install()` runs `dbDelta()` with the full `CREATE TABLE` for every table and seeds defaults. dbDelta is picky: two spaces after `PRIMARY KEY`, one column per line, `KEY` not `INDEX`.
- `maybe_upgrade()` is called first thing in `Plugin::boot()`. It returns early when the stored version matches. Otherwise it takes a 60-second transient lock (`jcore_<word>_upgrading`), runs versioned migrations (`if ( version_compare( $old, '1.2', '<' ) ) self::migrate_to_1_2();`), then `install()`, and updates the option. Updates arrive through the update client without reactivation, so activation hooks alone would never run the migration.
- To change the schema, bump `DB_VERSION`, edit the `CREATE TABLE`, and add a `migrate_to_x_y()` only for data changes dbDelta can't express.
- Queries use `$wpdb->prepare()` with `%i` for identifiers. Direct-query sniffs are silenced per line with a reason, e.g. `// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table.`
- Add every table and the version option to `uninstall.php`.

## REST controllers

- `includes/rest/class-controller.php` is an abstract base extending `\WP_REST_Controller`. It holds `$namespace = 'jcore-<word>/v1'`, `admin_permission()` (`current_user_can( 'manage_options' )`), and a `route( $method, $callback, $args )` helper that fills in the permission callback. Use it so no route can forget its permission check.
- One controller per resource: `class-<resource>-controller.php` → `Rest\<Resource>_Controller`, registered in `Plugin::register_rest_routes()` on `rest_api_init`.
- Declare `args` with `type`, `required`, `enum`, `sanitize_callback`/`validate_callback` as needed. Let the REST schema validate input instead of hand-checking it in the callback.
- Return `WP_REST_Response` / `rest_ensure_response()`, and `WP_Error` with a `status` for failures.
- A public route (like Turva's `POST /csp-report`) sets `'permission_callback' => '__return_true'` explicitly, with a comment saying why it is public. It also validates and rate-limits or deduplicates what it accepts.
- Document every route in the README's REST API table.

## Admin screens

- A screen is `includes/admin/class-menu.php` (or `class-<screen>.php`). It has `SLUG`, the `HOOK` suffix that `add_*_page()` returns, and the build `ENTRY`.
- `render_page()` requires `views/admin/<screen>.php`, which outputs only `<div id="jcore-<word>-app"></div>`. The React app renders its own `.wrap`.
- `enqueue_assets( $hook )` returns unless `$hook === self::HOOK`. It reads `build/<entry>.asset.php` for dependencies and version, enqueues `build/<entry>.js` and `build/style-<entry>.css` (with `wp-components` as a style dependency), and calls `wp_set_script_translations()`.
- Data the app needs up front is passed with `wp_add_inline_script( SLUG, 'window.jcore<Word> = ' . wp_json_encode( … ) . ';', 'before' )`. Everything else comes over REST.
- Add a "Settings" link with `plugin_action_links_{basename}`.
- Settings live in one array option `jcore_<word>_settings`, autoload off, merged over defaults on read. The template's `Rest\Settings_Controller` shows the pattern.

## Blocks

- Sources live in `src/<block>/`, with `block.json` `"name": "jco/<block>"` and `"textdomain": "jcore-<word>"`. To add one: `cd src && npx @wordpress/create-block@latest <block> --no-plugin --variant dynamic --namespace jco --textdomain jcore-<word>`. Then run `pnpm format`. create-block's `render.php` also needs a `@package Jcore\<Word>` file docblock and `// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped` on the `get_block_wrapper_attributes()` echo (core escapes it) before PHPCS passes.
- Build with `--blocks-manifest`. `Plugin::register_blocks()` registers from `build/blocks-manifest.php` with `wp_register_block_types_from_metadata_collection()` (WP 6.8+), falling back to `wp_register_block_metadata_collection()` plus `register_block_type()` per block on 6.7.
- Dynamic blocks render in `render.php`. Its variables are local to the render callback, which is why `phpcs.xml.dist` excludes `*/render.php` from the global-variable prefix sniff.
- `--admin` and `--blocks` together: `webpack.config.js` spreads `defaultConfig.entry()` (the blocks) and adds `admin`.
- For editor-only UI that isn't a block (Portti's campaign sidebar plugin), add an explicit webpack entry and enqueue it on `enqueue_block_editor_assets`.

## Post types, taxonomies and meta

- Register on `init`. Use `show_in_rest => true` (the block editor needs it) and labels that use the plugin's text domain.
- Meta used by the editor: `register_post_meta( $type, '_jcore_<word>_<key>', array( 'show_in_rest' => true, 'single' => true, 'type' => …, 'auth_callback' => fn() => current_user_can( 'edit_posts' ) ) )`.
- Constants for type and taxonomy names (`JCORE_PORTTI_POST_TYPE`) so they are spelled once.

## Translations

- Every `__()`/`_e()`/`esc_html__()` uses the literal text domain `'jcore-<word>'`. A different domain (even `'jcore'`) silently makes the string untranslatable. PHPCS catches it now, and older plugins often have it wrong.
- `Plugin::load_textdomain()` calls `load_plugin_textdomain()` on `init`. Just-in-time loading only reads `wp-content/languages/`, which these plugins never ship to.
- JS strings use `@wordpress/i18n` with the same domain. Each script handle that has strings needs `wp_set_script_translations( $handle, 'jcore-<word>', JCORE_<WORD>_PATH . 'languages' )`. Blocks get this from `set_block_script_translations()`.
- `pnpm i18n` regenerates `languages/jcore-<word>.pot`, compiles `.mo`, and generates the JS `.json` files from committed `.po` files (fi and sv_SE are the usual locales). Only `.po` files are committed; CI generates the rest in `make ci`.

## Uninstall

`uninstall.php` runs without the plugin loaded, so it spells out every name instead of using class constants. It starts with `defined( 'WP_UNINSTALL_PLUGIN' ) || exit;` and wraps the work in `jcore_<word>_uninstall_site()`. On multisite it loops `get_sites( array( 'fields' => 'ids', 'number' => 0 ) )` with `switch_to_blog()`. When you add an option, transient, table, cron event or meta key, add it here in the same change.

## Security checklist

- Escape late: `esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`. Output that is already safe (block content after `the_content`) gets a `phpcs:ignore` with the reason.
- Sanitize on input: REST `args` schema, or the `sanitize_*` functions.
- Capabilities on every REST route and admin action. Nonces on any non-REST form or `admin-post.php` handler.
- `$wpdb->prepare()` for every query with variables.
- No remote requests except the update client, which `readme.txt` declares under "External services". A new external service gets its own entry there.
