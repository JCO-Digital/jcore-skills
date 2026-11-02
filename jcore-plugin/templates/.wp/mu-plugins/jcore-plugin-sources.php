<?php
/**
 * Installs JCORE plugins from their GitHub releases instead of wordpress.org.
 *
 * Plugin Check (WordPress/plugin-check-action) installs what the header's
 * `Requires Plugins` names with `wp plugin install <slug>`, which asks
 * wordpress.org through plugins_api(). JCORE plugins aren't there: each one
 * publishes `<slug>.zip` on its GitHub releases in JCO-Digital/<slug>, so
 * this answers plugins_api() for jcore-* slugs with the latest release.
 *
 * Development and CI only: mapped in by .wp-env.override.json (Plugin Check)
 * and the playground script, never shipped (/.wp/ is in .distignore). The
 * repositories must be public for the download to work.
 *
 * @package Jcore\{{ns}}
 */

add_filter(
	'plugins_api',
	static function ( $result, string $action, $args ) {
		$slug = is_object( $args ) && isset( $args->slug ) ? (string) $args->slug : '';

		if ( 'plugin_information' !== $action || 1 !== preg_match( '/^jcore-[a-z0-9-]+$/', $slug ) ) {
			return $result;
		}

		return (object) array(
			'name'          => $slug,
			'slug'          => $slug,
			// The latest release; WP-CLI only shows this in its log line.
			'version'       => 'latest',
			'download_link' => "https://github.com/JCO-Digital/{$slug}/releases/latest/download/{$slug}.zip",
		);
	},
	10,
	3
);
