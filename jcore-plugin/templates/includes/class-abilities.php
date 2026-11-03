<?php
/**
 * Registers the plugin's abilities through Taika's registrar.
 *
 * @package Jcore\{{ns}}
 */

namespace Jcore\{{ns}};

use Jcore\Taika\Registrar;
use Jcore\Taika\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declares the module's abilities and the gateway tools they offer.
 *
 * Each ability's last argument is its gateway tool: `name` is the MCP tool
 * name clients see and `description` what the model reads, naming tools
 * (publish_post) rather than abilities, untranslated. A field added after
 * the first schema version carries `Registrar::SINCE_KEYWORD` with the
 * version that accepts it, and an enum that differs per site carries
 * `Registrar::SITE_ENUM_KEYWORD`. Names and schemas are a public contract:
 * add fields and bump the schema version, never rename or remove one.
 *
 *     $module->ability(
 *         'get-things',
 *         array(
 *             'label'               => __( 'Get things', '{{slug}}' ),
 *             'description'         => __( 'Returns the things of a post.', '{{slug}}' ),
 *             'input_schema'        => Schema::object( array( 'id' => Schema::id() ), array( 'id' ) ),
 *             'execute_callback'    => array( Things::class, 'get_things' ),
 *             'permission_callback' => array( Things::class, 'can_edit' ),
 *         ),
 *         Registrar::READ,
 *         1,
 *         array(
 *             'name'        => 'get_things',
 *             'description' => 'Returns the things of a post or page, with a version token for update_things.',
 *         )
 *     );
 */
final class Abilities {

	/**
	 * Module slug, and the namespace of the ability IDs.
	 */
	public const MODULE = '{{slug}}';

	/**
	 * Registers the abilities. Hooked to `jcore_taika_register`.
	 *
	 * @param Registrar $registrar Taika's registrar.
	 *
	 * @return void
	 */
	public static function register( Registrar $registrar ): void {
		$module = $registrar->module( self::MODULE, {{CONST}}_VERSION );

		// One $module->ability( … ) per ability, as in the example above.
	}
}
