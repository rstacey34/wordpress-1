<?php
/**
 * Plugin Name: Blocks Starter Templates
 * Plugin URI: https://wp-fse.com/
 * Description: Gutenberg starter templates and patterns
 * Version: 1.0.3
 * Author: Blocks WP
 * Author URI: https://blocks-wp.com/
 * License: GPL-2.0+
 * Text Domain: wpst-block-templates
 *
 * @package Blocks Starter Templates
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( class_exists( 'WPST_Block_Templates' ) ) {
	return;
}

// Set constants.
if ( ! defined( 'WPST_BLOCK_TEMPLATES_LIBRARY_URL' ) ) {
	define( 'WPST_BLOCK_TEMPLATES_LIBRARY_URL', apply_filters( 'wpst_block_templates_library_url', 'https://wp-fse.com/' ) );
}

define( 'WPST_BLOCK_TEMPLATES_VER', '1.0.3' );
define( 'WPST_BLOCK_TEMPLATES_FILE', __FILE__ );
define( 'WPST_BLOCK_TEMPLATES_BASE', plugin_basename( WPST_BLOCK_TEMPLATES_FILE ) );
define( 'WPST_BLOCK_TEMPLATES_DIR', plugin_dir_path( WPST_BLOCK_TEMPLATES_FILE ) );
define( 'WPST_BLOCK_TEMPLATES_URI', plugins_url( '/', WPST_BLOCK_TEMPLATES_FILE ) );

require_once WPST_BLOCK_TEMPLATES_DIR . 'classes/class-wpst-block-templates.php';
add_action(
	'plugins_loaded',
	function () {
		// call this only if Gutenberg is active.
		if ( function_exists( 'register_block_type' ) ) {
			require_once dirname( __FILE__ ) . '/build/class-blocks-export-import.php';

			if ( class_exists( '\WPST\GutenbergBlocks\WPST_Block_Templates_Export_Import' ) ) {
				\WPST\GutenbergBlocks\WPST_Block_Templates_Export_Import::instance();
			}
		}
	}
);

