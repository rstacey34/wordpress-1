<?php
/**
 * Sync Library
 *
 * @package Blocks Starter Templates
 * @since 1.0.0
 */

if ( ! class_exists( 'WPST_Block_Templates_Sync_Library' ) ) :

	/**
	 * Sync Library
	 *
	 * @since 1.0.0
	 */
	class WPST_Block_Templates_Sync_Library {

		/**
		 * Catch the latest checksums
		 *
		 * @since 1.1.0
		 * @access public
		 * @var string Last checksums.
		 */
		public $wpst_export_checksums;

		/**
		 * Instance
		 *
		 * @since 1.0.0
		 * @access private
		 * @var object Class object.
		 */
		private static $instance;

		/**
		 * Initiator
		 *
		 * @since 1.0.0
		 * @return object initialized object of class.
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			add_action( 'wp_ajax_wpst-block-templates-check-sync-library-status', array( $this, 'check_sync_status' ) );
			add_action( 'wp_ajax_wpst-block-templates-update-sync-library-status', array( $this, 'update_library_complete' ) );
			add_action( 'admin_head', array( $this, 'setup_templates' ) );
		}

		/**
		 * Auto Sync the library
		 *
		 * @since 1.0.6
		 * @return void
		 */
		public function auto_sync() {

			// Flush the data to the browsers.
			if ( function_exists( 'fastcgi_finish_request' ) ) {
				/**
				 *
				 * Any kind of output flush after fastcgi_finish_request is treated as exit;
				 * Hence, If any PHP Warnings/Notices after this can also terminate the execution.
				 * ignore_user_abort disables this and does not terminate the script on PHP Warnings/Notices.
				 * https://stackoverflow.com/questions/14191947/php-fpm-fastcgi-finish-request-reliable
				 */
				ignore_user_abort( true );
				fastcgi_finish_request();
			}

			$this->update_latest_checksums();
		}

		/**
		 * Start Importer
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function setup_templates() {
			$is_fresh_site = get_site_option( 'wpst_block_templates_fresh_site', 'yes' );

			$this->process_sync();

			if ( 'no' === $is_fresh_site ) {
				return;
			}

			$this->set_default_assets();

			update_site_option( 'wpst_block_templates_fresh_site', 'no' );
		}

		/**
		 * Set default assets
		 *
		 * @since 1.0.2
		 */
		public function set_default_assets() {

			$dir        = WPST_BLOCK_TEMPLATES_DIR . 'dist/json';
			$list_files = $this->get_default_assets();
			foreach ( $list_files as $key => $file_name ) {
				if ( file_exists( $dir . '/' . $file_name . '.json' ) ) {
					$data = wpst_block_templates_get_filesystem()->get_contents( $dir . '/' . $file_name . '.json' );
					if ( ! empty( $data ) ) {
						update_site_option( $file_name, json_decode( $data, true ) );
					}
				}
			}

		}

		/**
		 * Process Import
		 *
		 * @since 1.0.6
		 *
		 * @return mixed Null if process is already started.
		 */
		public function process_sync() {

			if ( apply_filters( 'wpst_block_templates_disable_auto_sync', false ) ) {
				return;
			}

			// Check if last sync and this sync has a gap of 24 hours.
			$wpst_check_time = get_site_option( 'wpst-block-templates-export-checksums-time', 0 );
			if ( ( time() - $wpst_check_time ) < 86400 ) { //86400
				return;
			}

			$current_screen = get_current_screen();

			// Bail if not on Blok editor screen.
			if ( true !== $current_screen->is_block_editor ) {
				return;
			}
      $this->set_default_assets();
			// Process sync.
			if ( 'yes' === $this->get_wpst_export_checksums() ) {
				add_action( 'shutdown', array( $this, 'auto_sync' ) );
			}
		}

		/**
		 * Json Files Names.
		 *
		 * @since 1.0.1
		 * @return array
		 */
		public function get_default_assets() {
			return array(
				'wpst-block-templates-categories',
				'wpst-block-templates-sites-1',
				'wpst-block-templates-site-requests',
				'wpst-block-templates-blocks-1',
				'wpst-block-templates-block-requests',
			);
		}

		/**
		 * Update Library Complete
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function update_library_complete() {

			if ( ! wpst_block_templates_doing_wp_cli() ) {

				if ( ! current_user_can( 'edit_posts' ) ) {
					wp_send_json_error( __( 'You are not allowed to perform this action', 'wpst-block-templates' ) );
				}
				// Verify Nonce.
				check_ajax_referer( 'wpst-block-templates-ajax-nonce', '_ajax_nonce' );
			}

			$this->update_latest_checksums();

			update_site_option( 'wpst-block-templates-batch-is-complete', 'no', 'no' );
			update_site_option( 'wpst-block-templates-manual-sync-complete', 'yes', 'no' );

			if ( wpst_block_templates_doing_wp_cli() ) {
				WP_CLI::line( 'Updated checksums' );
			} else {
				wp_send_json_success(
					array(
						'message' => 'Updated checksums',
						'status'  => true,
						'data'    => '',
					)
				);
			}
		}

		/**
		 * Update Library
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function check_sync_status() {

			if ( ! wpst_block_templates_doing_wp_cli() ) {

				if ( ! current_user_can( 'edit_posts' ) ) {
					wp_send_json_error( __( 'You are not allowed to perform this action', 'wpst-block-templates' ) );
				}
				// Verify Nonce.
				check_ajax_referer( 'wpst-block-templates-ajax-nonce', '_ajax_nonce' );
			}

			if ( 'no' === $this->get_wpst_export_checksums() ) {

				if ( wpst_block_templates_doing_wp_cli() ) {
					WP_CLI::error( 'Template library refreshed!' );
				} else {
					wp_send_json_success(
						array(
							'message' => 'Updated',
							'status'  => true,
							'data'    => 'updated',
						)
					);
				}
			}

			if ( ! wpst_block_templates_doing_wp_cli() ) {
				wp_send_json_success(
					array(
						'message' => 'Complete',
						'status'  => true,
						'data'    => '',
					)
				);
			}
		}

		/**
		 * Get Last Exported Checksum Status
		 *
		 * @since 1.0.0
		 * @return string Checksums Status.
		 */
		public function get_wpst_export_checksums() {

			$old_wpst_export_checksums = get_site_option( 'wpst-block-templates-export-checksums', '' );

			$new_wpst_export_checksums = $this->set_wpst_export_checksums();

			$checksums_status = 'no';

			if ( empty( $old_wpst_export_checksums ) ) {
				$checksums_status = 'yes';
			}

			if ( $new_wpst_export_checksums !== $old_wpst_export_checksums ) {
				$checksums_status = 'yes';
			}

			return apply_filters( 'wpst_block_templates_checksums_status', $checksums_status );
		}

		/**
		 * Set Last Exported Checksum
		 *
		 * @since 1.0.0
		 * @return string Checksums Status.
		 */
		public function set_wpst_export_checksums() {

			if ( ! empty( $this->wpst_export_checksums ) ) {
				return $this->wpst_export_checksums;
			}

			$api_args = array(
				'timeout' => 60,
			);

			update_site_option( 'wpst-block-templates-export-checksums-latest', WPST_BLOCK_TEMPLATES_VER, 'no' );

			$this->wpst_export_checksums = WPST_BLOCK_TEMPLATES_VER;


			return $this->wpst_export_checksums;
		}

		/**
		 * Update Latest Checksums
		 *
		 * Store latest checksum after batch complete.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function update_latest_checksums() {
			$latest_checksums = get_site_option( 'wpst-block-templates-export-checksums-latest', '' );
			update_site_option( 'wpst-block-templates-export-checksums', $latest_checksums, 'no' );
			update_site_option( 'wpst-block-templates-export-checksums-time', time(), 'no' );
		}


	}

	/**
	 * Kicking this off by calling 'get_instance()' method
	 */
	WPST_Block_Templates_Sync_Library::get_instance();

endif;
