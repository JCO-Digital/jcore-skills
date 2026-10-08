<?php
/**
 * Removes everything the plugin stored when it is deleted.
 *
 * @package Jcore\{{ns}}
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Deletes the plugin's options on the current site.
 *
 * Names are spelled out rather than read from the classes so this file stands
 * alone, as uninstall.php runs without the plugin loaded. Add every option,
 * transient, table and meta key the plugin creates.
 *
 * @return void
 */
function {{prefix}}_uninstall_site(): void {
	delete_option( '{{prefix}}_settings' );
}

if ( is_multisite() ) {
	${{prefix}}_site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( ${{prefix}}_site_ids as ${{prefix}}_site_id ) {
		switch_to_blog( (int) ${{prefix}}_site_id );
		{{prefix}}_uninstall_site();
		restore_current_blog();
	}
} else {
	{{prefix}}_uninstall_site();
}
