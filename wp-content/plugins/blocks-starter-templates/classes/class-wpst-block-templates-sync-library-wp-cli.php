<?php
/**
 * WP CLI
 *
 * 1. Run `wp wpst-block-templates sync`       Info.
 *
 * @since 1.0.0
 *
 * @package wpst-block-templates
 */

if ( ! class_exists( 'WPST_Block_Templates_Sync_Library_WP_CLI' ) && class_exists( 'WP_CLI_Command' ) ) :

	/**
	 * WPST_Block Templates WP CLI
	 */
	class WPST_Block_Templates_Sync_Library_WP_CLI extends WP_CLI_Command {

		/**
		 * Sync
		 *
		 *  Example: wp wpst-block-templates sync
		 *
		 * @since 1.0.0
		 * @param  array $args       Arguments.
		 * @param  array $assoc_args Associated Arguments.
		 * @return void
		 */
		public function sync( $args = array(), $assoc_args = array() ) {

			// Start Sync.
			if ( wpst_block_templates_doing_wp_cli() ) {
				WP_CLI::line( 'Sync Started' );
			}

			$force = isset( $assoc_args['force'] ) ? true : false;

			if ( ! $force ) {
				// Check sync status.
				WPST_Block_Templates_Sync_Library::get_instance()->check_sync_status();
			}

			// Categories.
			WPST_Block_Templates_Sync_Library::get_instance()->set_default_assets();

			

			// Sync Complete.
			WPST_Block_Templates_Sync_Library::get_instance()->update_library_complete();

			// Start Sync.
			if ( wpst_block_templates_doing_wp_cli() ) {
				WP_CLI::line( 'Sync Completed' );
			}
		}
	}

	/**
	 * Add Command
	 */
	if ( wpst_block_templates_doing_wp_cli() ) {
		WP_CLI::add_command( 'wpst-block-templates', 'WPST_Block_Templates_Sync_Library_WP_CLI' );
	}

endif;
