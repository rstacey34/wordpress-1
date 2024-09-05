<?php
/**
 * Class for Export/Import logic.
 *
 * @package WPST
 */

namespace WPST\GutenbergBlocks;

/**
 * Class WPST_Block_Templates_Export_Import.
 */
class WPST_Block_Templates_Export_Import {

	/**
	 * The main instance var.
	 *
	 * @var WPST_Block_Templates_Export_Import|null
	 */
	public static $instance = null;

	/**
	 * Initialize the class
	 */
	public function init() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ), 1 );
	}

	/**
	 * Load Gutenberg assets.
	 *
	 * @since   1.0.0
	 * @access  public
	 */
	public function enqueue_editor_assets() {
		$asset_file = include WPST_BLOCK_TEMPLATES_DIR . '/build/export-import/index.asset.php';
	
		wp_enqueue_script(
			'wpst-block-templates-export-import',
			WPST_BLOCK_TEMPLATES_URI . 'build/export-import/index.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);
	
		wp_set_script_translations( 'wpst-block-templates-export-import', 'wpst-block-templates' );
	}

	/**
	 * The instance method for the static class.
	 * Defines and returns the instance of the static class.
	 *
	 * @static
	 * @since 1.0.0
	 * @access public
	 * @return WPST_Block_Templates_Export_Import
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->init();
		}

		return self::$instance;
	}

	/**
	 * Throw error on object clone
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.0.0' );
	}

	/**
	 * Disable unserializing of the class
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.0.0' );
	}
}
