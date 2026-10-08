<?php
/**
 * Plugin bootstrap.
 *
 * @package Jcore\{{ns}}
 */

namespace Jcore\{{ns}};

use Jcore\Update\Config\UpdateConfig;
use Jcore\Update\Hooks\PluginUpdateHooks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wires every component to its hooks.
 */
final class Plugin {

	/**
	 * The single instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Returns the single instance.
	 *
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Registers every hook. Called once on `plugins_loaded`.
	 *
	 * @return void
	 */
	public function boot(): void {
		$this->register_updater();

		add_action( 'init', array( $this, 'load_textdomain' ) );
{{#blocks}}
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_action( 'init', array( $this, 'set_block_script_translations' ), 20 );
{{/blocks}}
{{#admin}}
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		if ( is_admin() ) {
			Admin\Menu::register();
			add_filter( 'plugin_action_links_' . plugin_basename( {{CONST}}_FILE ), array( $this, 'action_links' ) );
		}
{{/admin}}
	}

	/**
	 * Hooks the plugin into the J&Co Digital update service.
	 *
	 * The library is vendored into the release; a source checkout without a
	 * `composer install` simply runs without update checks.
	 *
	 * @return void
	 */
	private function register_updater(): void {
		if ( ! class_exists( UpdateConfig::class ) ) {
			return;
		}

		$config = new UpdateConfig(
			pluginFile: {{CONST}}_FILE,
			slug: '{{slug}}',
			version: {{CONST}}_VERSION,
			apiBaseUrl: 'https://update.jcore.fi/v1',
		);

		( new PluginUpdateHooks( $config ) )->register();
	}

	/**
	 * Loads the PHP translations shipped in languages/.
	 *
	 * Just-in-time loading only looks in wp-content/languages, which these
	 * plugins never ship to, so the bundled files have to be loaded explicitly.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain( '{{slug}}', false, dirname( plugin_basename( {{CONST}}_FILE ) ) . '/languages' );
	}
{{#blocks}}

	/**
	 * Registers every block listed in build/blocks-manifest.php.
	 *
	 * @return void
	 */
	public function register_blocks(): void {
		$build_dir = {{CONST}}_PATH . 'build';
		$manifest  = $build_dir . '/blocks-manifest.php';

		if ( ! is_readable( $manifest ) ) {
			return;
		}

		// WordPress 6.8+ registers the whole collection in one call.
		if ( function_exists( 'wp_register_block_types_from_metadata_collection' ) ) {
			wp_register_block_types_from_metadata_collection( $build_dir, $manifest );
			return;
		}

		wp_register_block_metadata_collection( $build_dir, $manifest );
		foreach ( array_keys( require $manifest ) as $block ) {
			register_block_type( $build_dir . '/' . $block );
		}
	}

	/**
	 * Points the block scripts at the JSON translations in languages/.
	 *
	 * Core sets script translations for blocks, but only from wp-content/languages.
	 *
	 * @return void
	 */
	public function set_block_script_translations(): void {
		foreach ( \WP_Block_Type_Registry::get_instance()->get_all_registered() as $block_type ) {
			if ( '{{slug}}' !== $block_type->textdomain ) {
				continue;
			}

			foreach ( array_merge( $block_type->editor_script_handles, $block_type->view_script_handles ) as $handle ) {
				wp_set_script_translations( $handle, '{{slug}}', {{CONST}}_PATH . 'languages' );
			}
		}
	}
{{/blocks}}
{{#admin}}

	/**
	 * Registers the REST routes the admin app talks to.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		( new Rest\Settings_Controller() )->register_routes();
	}

	/**
	 * Adds a Settings link on the Plugins screen.
	 *
	 * @param string[] $links Existing action links.
	 *
	 * @return string[]
	 */
	public function action_links( array $links ): array {
		array_unshift(
			$links,
			sprintf(
				'<a href="%s">%s</a>',
				esc_url( Admin\Menu::url() ),
				esc_html__( 'Settings', '{{slug}}' )
			)
		);

		return $links;
	}
{{/admin}}
}
