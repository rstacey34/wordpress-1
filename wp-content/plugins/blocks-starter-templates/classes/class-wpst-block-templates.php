<?php
/**
 * Init
 *
 * @since 1.0.0
 * @package Blocks Starter Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPST_Block_Templates' ) ) :

	/**
	 * Admin
	 */
	class WPST_Block_Templates {

		/**
		 * Instance
		 *
		 * @since 1.0.0
		 * @var (Object) WPST_Block_Templates
		 */
		private static $instance = null;

		/**
		 * Get Instance
		 *
		 * @since 1.0.0
		 *
		 * @return object Class object.
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 */
		private function __construct() {
			require_once WPST_BLOCK_TEMPLATES_DIR . 'classes/functions.php';
			require_once WPST_BLOCK_TEMPLATES_DIR . 'classes/class-wpst-block-templates-sync-library.php';
			require_once WPST_BLOCK_TEMPLATES_DIR . 'classes/class-wpst-block-templates-image-importer.php';
			require_once WPST_BLOCK_TEMPLATES_DIR . 'classes/class-wpst-block-templates-sync-library-wp-cli.php';

			add_action( 'enqueue_block_editor_assets', array( $this, 'template_assets' ) );
			add_action( 'wp_ajax_wpst_block_templates_importer', array( $this, 'template_importer' ) );
			add_action( 'wp_ajax_wpst_block_templates_activate_plugin', array( $this, 'activate_plugin' ) );
			add_action( 'wp_ajax_wpst_block_templates_import_block', array( $this, 'import_block' ) );
			add_action( 'wp_ajax_wpst_block_templates_data_option', array( $this, 'api_request' ) );
		}
		
		/**
		 * Retrieve block data from an API and update the option with the data.
		 *
		 * @since 1.3.0
		 * @return void
		 */
		public function api_request() {

			if ( ! current_user_can( 'edit_posts' ) ) {
				wp_send_json_error( __( 'You are not allowed to perform this action', 'wpst-block-templates' ) );
			}
			
			// Verify Nonce.
			check_ajax_referer( 'wpst-block-templates-ajax-nonce', '_ajax_nonce' );
			$block_id     = isset( $_REQUEST['id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['id'] ) ) : '';
			$block_type   = isset( $_REQUEST['type'] ) ? sanitize_text_field( wp_unslash($_REQUEST['type']) ) : '';
			if ( str_contains($block_id, '9897') ) {
				$block_ids = str_replace('9897', '', $block_id);
				$complete_url = WPST_BLOCK_TEMPLATES_LIBRARY_URL . 'woocommerce/wp-json/wp/v2/' . $block_type . '/' . $block_ids . '/';
			} else {
				$complete_url = WPST_BLOCK_TEMPLATES_LIBRARY_URL . 'wp-json/wp/v2/' . $block_type . '/' . $block_id . '/';
			}
			//wpst_block_templates_log( $complete_url );

			$response = wp_remote_get( $complete_url );

			if ( ! is_wp_error( $response ) || 200 === $response['response']['code'] ) {
				$body = json_decode( wp_remote_retrieve_body( $response ) );
				// Create a dynamic option name to save the block data.
				$content = $body->{'original_content'};
				update_option( 'wpst-block-templates_data-' . $block_id, $content );
				wp_send_json_success( $content );
			} else {
				wp_send_json_error( __( 'Something went wrong', 'wpst-block-templates' ) );
			}
		}

		/**
		 * Import Block
		 */
		public function import_block() {

			if ( ! current_user_can( 'edit_posts' ) ) {
				wp_send_json_error( __( 'You are not allowed to perform this action', 'wpst-block-templates' ) );
			}
			// Verify Nonce.
			check_ajax_referer( 'wpst-block-templates-ajax-nonce', '_ajax_nonce' );

			// Allow the SVG tags in batch update process.
			add_filter( 'wp_kses_allowed_html', array( $this, 'allowed_tags_and_attributes' ), 10, 2 );

			// Post content.
			$content = isset( $_REQUEST['content'] ) ? wp_kses_post( wp_unslash($_REQUEST['content']) ) : ''; 

			// # Tweak
			// Gutenberg break block markup from render. Because the '&' is updated in database with '&amp;' and it
			// expects as 'u0026amp;'. So, Converted '&amp;' with 'u0026amp;'.
			//
			// @todo This affect for normal page content too. Detect only Gutenberg pages and process only on it.
			$content = str_replace( 'u0026', "&", $content );
			$content = $this->get_content( $content );

			// Update content.
			wp_send_json_success( $content );
		}

		/**
		 * Download and Replace hotlink images
		 *
		 * @since 1.0.0
		 *
		 * @param  string $content Mixed post content.
		 * @return array           Hotlink image array.
		 */
		public function get_content( $content = '' ) {

			// Extract all links.
			preg_match_all( '#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $content, $match );

			$all_links = array_unique( $match[0] );

			// Not have any link.
			if ( empty( $all_links ) ) {
				return $content;
			}

			$link_mapping = array();
			$image_links  = array();
			$other_links  = array();

			// Extract normal and image links.
			foreach ( $all_links as $key => $link ) {
				if ( wpst_block_templates_is_valid_image( $link ) ) {

					// Get all image links.
					// Avoid *-150x, *-300x and *-1024x images.
					if (
						false === strpos( $link, '-150x' ) &&
						false === strpos( $link, '-300x' ) &&
						false === strpos( $link, '-1024x' )
					) {
						$image_links[] = $link;
					}
				} else {
					// Collect other links.
					$other_links[] = $link;
				}
			}

			// Step 1: Download images.
			if ( ! empty( $image_links ) ) {
				foreach ( $image_links as $key => $image_url ) {
					// Download remote image.
					$image            = array(
						'url' => $image_url,
						'id'  => 0,
					);
					$downloaded_image = WPST_Block_Templates_Image_Importer::get_instance()->import( $image );

					// Old and New image mapping links.
					$link_mapping[ $image_url ] = $downloaded_image['url'];
				}
			}

			// Step 3: Replace mapping links.
			foreach ( $link_mapping as $old_url => $new_url ) {
				$content = str_replace( $old_url, $new_url, $content );

				// Replace the slashed URLs if any exist.
				$old_url = str_replace( '/', '/\\', $old_url );
				$new_url = str_replace( '/', '/\\', $new_url );
				$content = str_replace( $old_url, $new_url, $content );
			}

			return $content;
		}

		/**
		 * Allowed tags for the batch update process.
		 *
		 * @param  array        $allowedposttags   Array of default allowable HTML tags.
		 * @param  string|array $context    The context for which to retrieve tags. Allowed values are 'post',
		 *                                  'strip', 'data', 'entities', or the name of a field filter such as
		 *                                  'pre_user_description'.
		 * @return array Array of allowed HTML tags and their allowed attributes.
		 */
		public function allowed_tags_and_attributes( $allowedposttags, $context ) {

			// Keep only for 'post' contenxt.
			if ( 'post' === $context ) {

				// <svg> tag and attributes.
				$allowedposttags['svg'] = array(
					'xmlns'   => true,
					'viewbox' => true,
				);

				// <path> tag and attributes.
				$allowedposttags['path'] = array(
					'd' => true,
				);
			}

			return $allowedposttags;
		}

		/**
		 * Activate Plugin
		 */
		public function activate_plugin() {

			if ( ! current_user_can( 'activate_plugins' ) ) {
				wp_send_json_error( __( 'You are not allowed to perform this action.', 'wpst-block-templates' ) );
			}
			// Verify Nonce.
			check_ajax_referer( 'wpst-block-templates-ajax-nonce', 'security' );

			wp_clean_plugins_cache();

			$plugin_init = ( isset( $_POST['init'] ) ) ? sanitize_text_field( wp_unslash($_POST['init']) ) : '';

			$activate = activate_plugin( $plugin_init, '', false, true );

			if ( is_wp_error( $activate ) ) {
				wp_send_json_error( $activate->get_error_message() );
			}

			wp_send_json_success(
				array(
					'message' => 'Plugin activated successfully.',
				)
			);
		}

		/**
		 * Template Importer
		 *
		 * @since 1.0.0
		 */
		public function template_importer() {

			if ( ! current_user_can( 'edit_posts' ) ) {
				wp_send_json_error( __( 'You are not allowed to perform this action', 'wpst-block-templates' ) );
			}
			// Verify Nonce.
			check_ajax_referer( 'wpst-block-templates-ajax-nonce', '_ajax_nonce' );

			$block_id   = isset( $_REQUEST['id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['id'] ) ) : '';
			$block_data = get_option( 'wpst-block-templates_data-' . $block_id );

			//$data = json_decode( $block_data, true );

			// Flush the object when import is successful.
			//delete_option( 'wpst-block-templates_data-' . $block_id );

			wp_send_json_success( $block_data );
		}

		/**
		 * Template Assets
		 *
		 * @since 1.0.0
		 */
		public function template_assets() {

			$post_types = get_post_types( array( 'public' => true ), 'names' );

			$current_screen = get_current_screen();

			if ( ! is_object( $current_screen ) && is_null( $current_screen ) ) {
				return false;
			}

			if ( ! array_key_exists( $current_screen->post_type, $post_types ) ) {
				return;
			}

			wp_enqueue_script( 'wpst-block-templates', WPST_BLOCK_TEMPLATES_URI . 'dist/main.js', array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'masonry', 'imagesloaded', 'updates' ), WPST_BLOCK_TEMPLATES_VER, true );
			wp_add_inline_script( 'wpst-block-templates', 'window.lodash = _.noConflict();', 'after' );

			wp_enqueue_style( 'wpst-block-templates', WPST_BLOCK_TEMPLATES_URI . 'dist/style.css', array(), WPST_BLOCK_TEMPLATES_VER, 'all' );

			$license_status = false;
			if ( function_exists('fse_pro_status') ) {
				$license_status = fse_pro_status();
			}
			wp_localize_script(
				'wpst-block-templates',
				'WPSTBlockTemplatesVars',
				apply_filters(
					'wpst_block_templates_localize_vars',
					array(
						'popup_class'             => 'wpst-block-templates-lightbox',
						'ajax_url'                => admin_url( 'admin-ajax.php' ),
						'uri'                     => WPST_BLOCK_TEMPLATES_URI,
						'white_label_name'        => '',
						'allBlocks'               => $this->get_all_blocks(),
						'allSites'                => $this->get_all_sites(),
						'allCategories'           => get_site_option( 'wpst-block-templates-categories', array() ),
						'woo_status'			  => $this->get_plugin_status( 'woocommerce/woocommerce.php' ),
						'wpst_status'			  => $this->get_plugin_status( 'blocks-starter-templates/blocks-starter-templates.php' ),
						'_ajax_nonce'             => wp_create_nonce( 'wpst-block-templates-ajax-nonce' ),
						'button_text'             => esc_html__( 'Template Kits', 'wpst-block-templates' ),
						'display_button_logo'     => true,
						'popup_logo_uri'          => WPST_BLOCK_TEMPLATES_URI . 'dist/logo.png',
						'button_logo'             => WPST_BLOCK_TEMPLATES_URI . 'dist/logo.png',
						'button_class'            => '',
						'display_suggestion_link' => false,
						'suggestion_link'         => 'https://wp-fse.com/?utm_source=demo-import-panel&utm_campaign=fse-sites&utm_medium=suggestions',
						'license_status'          => $license_status,
						'isPro'                   => defined( 'FSE_PRO_CURRENT_VERSION' ) ? true : false,
						'getProURL'               => defined( 'FSE_PRO_CURRENT_VERSION' ) ? esc_url( admin_url( 'options-general.php?page=fse-license-options' ) ) : esc_url( 'https://wp-fse.com/starter-templates-pro/?utm_source=fse-templates&utm_medium=dashboard&utm_campaign=Starter-Template-Backend' ),
					)
				)
			);

		}

		/**
		 * Get plugin status
		 *
		 * @since 1.0.0
		 *
		 * @param  string $plugin_init_file Plguin init file.
		 * @return mixed
		 */
		public function get_plugin_status( $plugin_init_file ) {

			$installed_plugins = get_plugins();

			if ( ! isset( $installed_plugins[ $plugin_init_file ] ) ) {
				return 'not-installed';
			} elseif ( is_plugin_active( $plugin_init_file ) ) {
				return 'active';
			} else {
				return 'inactive';
			}
		}

		/**
		 * Get all sites
		 *
		 * @since 1.0.0
		 *
		 * @return array page builder sites.
		 */
		public function get_all_sites() {
			$total_requests = (int) get_site_option( 'wpst-block-templates-site-requests', 0 );

			$sites = array();

			if ( $total_requests ) {

				for ( $page = 1; $page <= $total_requests; $page++ ) {
					$current_page_data = get_site_option( 'wpst-block-templates-sites-' . $page, array() );
					if ( ! empty( $current_page_data ) ) {
						foreach ( $current_page_data as $site_id => $site_data ) {

							// Replace `wpst-tag` with `tag`.
							if ( isset( $site_data['wpst-tag'] ) ) {
								$site_data['tag'] = $site_data['wpst-tag'];
								unset( $site_data['wpst-tag'] );
							}

							// Replace `id-` from the site ID.
							$site_data['ID'] = str_replace( 'id-', '', $site_id );

							if ( count( $site_data['pages'] ) ) {
								foreach ( $site_data['pages'] as $page_id => $page_data ) {

									$single_page = $page_data;

									// Replace `wpst-tag` with `tag`.
									if ( isset( $single_page['wpst-tag'] ) ) {
										$single_page['tag'] = $single_page['wpst-tag'];
										unset( $single_page['wpst-tag'] );
									}

									// Replace `id-` from the site ID.
									$single_page['ID'] = str_replace( 'id-', '', $page_id );

									$site_data['pages'][] = $single_page;

									unset( $site_data['pages'][ $page_id ] );
								}
							}

							$sites[] = $site_data;
						}
					}
				}
			}

			return $sites;
		}

		/**
		 * Get all blocks
		 *
		 * @since 1.0.0
		 * @return array All Elementor Blocks.
		 */
		public function get_all_blocks() {
			$blocks         = array();
			$total_requests = (int) get_site_option( 'wpst-block-templates-block-requests', 0 );

			for ( $page = 1; $page <= $total_requests; $page++ ) {
				$current_page_data = get_site_option( 'wpst-block-templates-blocks-' . $page, array() );
				if ( ! empty( $current_page_data ) ) {
					foreach ( $current_page_data as $page_id => $page_data ) {
						$page_data['ID'] = str_replace( 'id-', '', $page_id );
						$blocks[]        = $page_data;
					}
				}
			}

			return $blocks;
		}

		/**
		 * Download File Into Uploads Directory
		 *
		 * @since 1.0.0
		 *
		 * @param  string $file Download File URL.
		 * @param  array  $overrides Upload file arguments.
		 * @param  int    $timeout_seconds Timeout in downloading the XML file in seconds.
		 * @return array        Downloaded file data.
		 */
		public function download_file( $file = '', $overrides = array(), $timeout_seconds = 300 ) {

			// Gives us access to the download_url() and wp_handle_sideload() functions.
			require_once ABSPATH . 'wp-admin/includes/file.php';

			// Download file to temp dir.
			$temp_file = download_url( $file, $timeout_seconds );

			// WP Error.
			if ( is_wp_error( $temp_file ) ) {
				return array(
					'success' => false,
					'data'    => $temp_file->get_error_message(),
				);
			}

			// Array based on $_FILE as seen in PHP file uploads.
			$file_args = array(
				'name'     => basename( $file ),
				'tmp_name' => $temp_file,
				'error'    => 0,
				'size'     => filesize( $temp_file ),
			);

			$defaults = apply_filters(
				'wpst_block_templates_wp_handle_sideload',
				array(

					// Tells WordPress to not look for the POST form
					// fields that would normally be present as
					// we downloaded the file from a remote server, so there
					// will be no form fields
					// Default is true.
					'test_form'   => false,

					// Setting this to false lets WordPress allow empty files, not recommended.
					// Default is true.
					'test_size'   => true,

					// A properly uploaded file will pass this test. There should be no reason to override this one.
					'test_upload' => true,

					'mimes'       => array(
						'xml'  => 'text/xml',
						'json' => 'application/json',
					),
				) 
			);

			$overrides = wp_parse_args( $overrides, $defaults );

			// Move the temporary file into the uploads directory.
			$results = wp_handle_sideload( $file_args, $overrides );

			if ( isset( $results['error'] ) ) {
				return array(
					'success' => false,
					'data'    => $results,
				);
			}

			// Success.
			return array(
				'success' => true,
				'data'    => $results,
			);
		}

	}

	/**
	 * Kicking this off by calling 'get_instance()' method
	 */
	WPST_Block_Templates::get_instance();

endif;
