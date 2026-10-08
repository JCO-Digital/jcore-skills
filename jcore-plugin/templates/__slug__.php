<?php
/**
 * Plugin Name:       {{name}}
 * Plugin URI:        https://github.com/JCO-Digital/{{slug}}
 * Description:       {{description}}
 * Version:           0.1.0
 * Requires at least: 6.7
 * Tested up to:      {{tested}}
 * Requires PHP:      8.2
 * Author:            J&Co Digital Oy
 * Author URI:        https://jco.fi
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       {{slug}}
 * Domain Path:       /languages
 *
 * @package Jcore\{{ns}}
 */

namespace Jcore\{{ns}};

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// foonver rewrites this along with the header on every release.
define( '{{CONST}}_VERSION', '0.1.0' );
define( '{{CONST}}_FILE', __FILE__ );
define( '{{CONST}}_PATH', plugin_dir_path( __FILE__ ) );
define( '{{CONST}}_URL', plugin_dir_url( __FILE__ ) );

// The update library is vendored into the release, but a source checkout has
// no vendor directory until `composer install` has run.
if ( is_readable( {{CONST}}_PATH . 'vendor/autoload.php' ) ) {
	require_once {{CONST}}_PATH . 'vendor/autoload.php';
}

/**
 * Autoloads classes from the Jcore\{{ns}} namespace.
 *
 * Maps `Jcore\{{ns}}\Foo\Bar_Baz` to `includes/foo/class-bar-baz.php`, the file
 * naming the WordPress coding standards ask for.
 *
 * @param string $class_name Fully qualified class name.
 *
 * @return void
 */
spl_autoload_register(
	static function ( string $class_name ): void {
		$prefix = __NAMESPACE__ . '\\';
		if ( ! str_starts_with( $class_name, $prefix ) ) {
			return;
		}

		$parts = explode( '\\', substr( $class_name, strlen( $prefix ) ) );
		$parts = array_map(
			static function ( string $part ): string {
				return strtolower( str_replace( '_', '-', $part ) );
			},
			$parts
		);

		$parts[ array_key_last( $parts ) ] = 'class-' . end( $parts );

		$file = {{CONST}}_PATH . 'includes/' . implode( '/', $parts ) . '.php';

		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

add_action(
	'plugins_loaded',
	static function (): void {
		Plugin::instance()->boot();
	}
);

// Registered at file scope, not in Plugin::boot(): other JCORE components read
// this list while plugins are still loading.
add_filter(
	'jcore_plugins_loaded',
	static function ( array $plugins ): array {
		$plugins['{{slug}}'] = __DIR__;

		return $plugins;
	}
);
