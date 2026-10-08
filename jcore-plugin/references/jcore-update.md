# jcore-update

`jcodigital/jcore-update` is the client every JCORE plugin bundles so sites get updates from `update.jcore.fi` instead of wordpress.org. The source is at `JCO-Digital/jcore-update`, usually checked out at `../../Libraries/jcore-update` from the plugins directory. Read its `README.md`, `CHANGELOG.md` and `examples/wordpress-plugin-bootstrap.php` before using anything not covered here, because the library is still gaining features.

Require it as `"jcodigital/jcore-update": "^1.7"` (1.7 added the readme-based popup). It is a runtime dependency, not `require-dev`.

## Standard registration

In `Plugin::register_updater()`, called first in `boot()`:

```php
use Jcore\Update\Config\UpdateConfig;
use Jcore\Update\Hooks\PluginUpdateHooks;

if ( ! class_exists( UpdateConfig::class ) ) {
	return; // Source checkout without composer install.
}

$config = new UpdateConfig(
	pluginFile: JCORE_<WORD>_FILE,
	slug: 'jcore-<word>',
	version: JCORE_<WORD>_VERSION,
	apiBaseUrl: 'https://update.jcore.fi/v1',
);

( new PluginUpdateHooks( $config ) )->register();
```

- `version` can be omitted, in which case the library reads the plugin header. Older plugins pass `PluginHelper::getVersion( FILE )` (`Jcore\Update\Support\PluginHelper`). Both work; new plugins pass the constant.
- `slug` must equal the directory name, the `slug` input of the publish workflow, and the slug registered with update.jcore.fi.
- Use named arguments as above. The constructor has many optional parameters, and names keep the call readable.

## What it does once registered

- Hooks `pre_set_site_transient_update_plugins` to ask `GET /v1/update-check` whether a newer version exists. 204 means no update, and the result is cached for `updateCacheTtl` (12 h default).
- Hooks `plugins_api` to serve the "View details" popup. It completes the service's answer from the plugin's bundled **`readme.txt`**: description, installation, FAQ, screenshots and changelog sections. When the plugin is current, it describes the installed release. So `readme.txt` is user-facing documentation; keep it accurate. Override its location with `readmeFile:` only if it really lives elsewhere.
- **Major-update protection** (`filterMajorUpdates: true` by default): a `2.x` release is hidden from a `1.x` site. The Plugins screen shows a notice with "Allow upgrade to v2.x", which opts that site into exactly the next major. Breaking changes therefore need the admin to act. Mention this whenever a change would make foonver bump major. Pass `filterMajorUpdates: false` only for plugins where that's explicitly wanted.

## Licensing (optional)

Plugins that need a license key pass `licenseKey:` (a string) or `licenseProvider:` (an object implementing `Jcore\Update\Licensing\LicenseProviderInterface::getLicenseKey(): ?string`). The library only validates (`$updater->validateLicense( $key )` → `LicenseValidationResult`, or `$updater->isLicenseValid( $key )`). Settings UI, storing the key and notices are the plugin's job. Validate in the option's sanitize callback or the REST handler that saves the key, and never log or echo the key.

## Other config

`requestTimeout` (10 s), `updateCacheTtl` (43200 s), `licenseValidationCacheTtl` (600 s), `httpArgsFilter` (`Closure` to adjust request args), `logger` (`Jcore\Update\Support\LoggerInterface`; `WordPressLogger` writes to the debug log). The defaults are right for almost everything.

## Scoping

The release zip doesn't contain `Jcore\Update\…`. The publish workflow prefixes it per library version (see `release.md`), so two plugins bundling different versions coexist. Locally and in the dist repo, the code is unscoped. For plugin code this means: reference the library only from scanned PHP files (not `build/`), through `use` imports, `::class` or literal fully qualified names, so php-scoper can rewrite them. A `class_exists( 'Jcore\Update\Config\UpdateConfig' )` string literal is also rewritten, but `UpdateConfig::class` is clearer.

## Updating the library

`composer update jcodigital/jcore-update`, then commit `composer.json`/`composer.lock` as `build(composer): update jcore-update to vX.Y.Z`. This ships a patch release of the plugin, which is the point: sites only get library fixes through plugin releases.
