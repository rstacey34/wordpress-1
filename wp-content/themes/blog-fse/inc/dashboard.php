<?php
/**
 * Blog FSE admin notify
 *
 */
if ( !defined( 'ABSPATH' ) ) {
	exit;
}  

if ( !class_exists( 'Blog_FSE_Notify_Admin' ) ) :

	/**
	 * The Blog FSE admin notify
	 */
	class Blog_FSE_Notify_Admin {

		/**
		 * Setup class.
		 *
		 */
		public function __construct() {

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'admin_notices', array( $this, 'admin_notices' ), 99 );
			add_action( 'wp_ajax_blog_fse_dismiss_notice', array( $this, 'dismiss_nux' ) );
			add_action( 'admin_menu', array( $this, 'add_menu' ), 5 );
		}

		/**
		 * Enqueue scripts.
		 *
		 */
		public function enqueue_scripts() {
			global $wp_customize;

			if ( isset( $wp_customize ) || blog_fse_plugin_is_activated() ) {
				return;
			}

			wp_enqueue_style( 'blog-fse-admin', get_template_directory_uri() . '/assets/css/admin.css', '', '1' );

			wp_enqueue_script( 'blog-fse-admin', get_template_directory_uri() . '/assets/js/admin.js', array( 'jquery', 'updates' ), '1', 'all' );

			$blog_fse_notify = array(
				'nonce' => wp_create_nonce( 'blog_fse_notice_dismiss' )
			);

			wp_localize_script( 'blog-fse-admin', 'blog_fse_ux', $blog_fse_notify );
		}

		/**
		 * Output admin notices.
		 *
		 */
		public function admin_notices() {
			global $pagenow;
			$theme_data = wp_get_theme();
			if ( ( 'themes.php' === $pagenow ) && isset( $_GET[ 'page' ] ) && ( 'blog-fse' === $_GET[ 'page' ] ) || true === (bool) get_option( 'blog_fse_notify_dismissed' ) || blog_fse_plugin_is_activated() ) {
				return;
			}
			$theme_data = wp_get_theme();
			$theme_name = $theme_data->Name;
			?>

			<div class="notice notice-info blog-fse-notice is-dismissible">
				<div class="blog-fse-row">
					<div class="blog-fse-col">
						<div class="notice-content">
							<?php if ( !blog_fse_plugin_is_activated() && current_user_can( 'install_plugins' ) && current_user_can( 'activate_plugins' ) ) : ?>
								<h2>
									<?php
									/* translators: %s: Theme name */
									printf( esc_html__( 'Thank you for installing %s.', 'blog-fse' ), '<strong>' . $theme_name . '</strong>' );
									?>
								</h2>
								<p class="blog-fse-description">
									<?php
									/* translators: %s: Plugin name string */
									printf( esc_html__( 'To take full advantage of all the features this theme has to offer, please install and activate the %s plugin.', 'blog-fse' ), '<strong>Blocks Starter Templates</strong>' );
									?>
								</p>
								<p>
									<?php self::install_plugin_button( 'blocks-starter-templates', 'blocks-starter-templates.php', 'Blocks Starter Templates', array( 'blog-fse-nux-button' ), __( 'Activated', 'blog-fse' ), __( 'Activate', 'blog-fse' ), __( 'Install', 'blog-fse' ) ); ?>
									<a href="<?php echo esc_url( admin_url( 'themes.php?page=blog-fse' ) ); ?>" class="button button-primary button-hero">
										<?php
										/* translators: %s: Theme name */
										printf( esc_html__( 'Get started with %s', 'blog-fse' ), $theme_data->Name );
										?>
									</a>
								</p>

							<?php endif; ?>
						</div>
					</div>
					<div class="blog-fse-col blog-fse-col-right">
						<div class="image-container">
							<?php echo '<img src="' . esc_url( get_template_directory_uri() ) . '/assets/images/' . strtolower( str_replace( ' ', '-', $theme_name ) ) . '-banner.png"/>'; ?>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

		public function add_menu() {
			if ( isset( $wp_customize ) || blog_fse_plugin_is_activated() ) {
				return;
			}
			$theme_data = wp_get_theme();

			add_theme_page(
			$theme_data->Name, $theme_data->Name, 'edit_theme_options', 'blog-fse', array( $this, 'admin_page' )
			);
		}

		public function admin_page() {
			if ( blog_fse_plugin_is_activated() ) {
				return;
			}
			$theme_data = wp_get_theme();
			?>

			<div class="notice notice-info blog-fse-notice-nux">

				<div class="notice-content">
					<?php if ( !blog_fse_plugin_is_activated() && current_user_can( 'install_plugins' ) && current_user_can( 'activate_plugins' ) ) : ?>
						<h2>
							<?php
							/* translators: %s: Theme name */
							printf( esc_html__( 'Thank you for installing %s.', 'blog-fse' ), '<strong>' . $theme_data->Name . '</strong>' );
							?>
						</h2>
						<p>
							<?php
							/* translators: %s: Plugin name string */
							printf( esc_html__( 'To take full advantage of all the features this theme has to offer, please install and activate the %s plugin.', 'blog-fse' ), '<strong>Blocks Starter Templates</strong>' );
							?>
						</p>
						<p><?php  self::install_plugin_button( 'blocks-starter-templates', 'blocks-starter-templates.php', 'Blocks Starter Templates', array( 'blog-fse-nux-button' ), __( 'Activated', 'blog-fse' ), __( 'Activate', 'blog-fse' ), __( 'Install', 'blog-fse' ) ); ?></p>
					<?php endif; ?>


				</div>
			</div>
			<?php
		}

		/**
		 * AJAX dismiss notice.
		 *
		 * @since 2.2.0
		 */
		public function dismiss_nux() {
			$nonce = !empty( $_POST[ 'nonce' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'nonce' ] ) ) : false;

			if ( !$nonce || !wp_verify_nonce( $nonce, 'blog_fse_notice_dismiss' ) || !current_user_can( 'manage_options' ) ) {
				die();
			}

			update_option( 'blog_fse_notify_dismissed', true );
		}
    
    /**
		 * Output a button that will install or activate a plugin if it doesn't exist, or display a disabled button if the
		 * plugin is already activated.
		 *
		 * @param string $plugin_slug The plugin slug.
		 * @param string $plugin_file The plugin file.
		 */
		public static function install_plugin_button( $plugin_slug, $plugin_file, $plugin_name, $classes = array(), $activated = '', $activate = '', $install = '' ) {
			if ( current_user_can( 'install_plugins' ) && current_user_can( 'activate_plugins' ) ) {
				if ( is_plugin_active( $plugin_slug . '/' . $plugin_file ) ) {
					// The plugin is already active
					$button = array(
						'message' => esc_attr__( 'Activated', 'blog-fse' ),
						'url'     => '#',
						'classes' => array( 'blog-fse-button', 'disabled' ),
					);

					if ( '' !== $activated ) {
						$button['message'] = esc_attr( $activated );
					}
				} elseif ( $url = self::_is_plugin_installed( $plugin_slug ) ) {
					// The plugin exists but isn't activated yet.
					$button = array(
						'message' => esc_attr__( 'Activate', 'blog-fse' ),
						'url'     => $url,
						'classes' => array( 'blog-fse-button', 'activate-now' ),
					);

					if ( '' !== $activate ) {
						$button['message'] = esc_attr( $activate );
					}
				} else {
					// The plugin doesn't exist.
					$url = wp_nonce_url( add_query_arg( array(
						'action' => 'install-plugin',
						'plugin' => $plugin_slug,
					), self_admin_url( 'update.php' ) ), 'install-plugin_' . $plugin_slug );
					$button = array(
						'message' => esc_attr__( 'Install now', 'blog-fse' ),
						'url'     => $url,
						'classes' => array( 'blog-fse-button', 'blog-fse-install-now', 'install-now', 'install-' . $plugin_slug ),
					);

					if ( '' !== $install ) {
						$button['message'] = esc_attr( $install );
					}
				}

				if ( ! empty( $classes ) ) {
					$button['classes'] = array_merge( $button['classes'], $classes );
				}

				$button['classes'] = implode( ' ', $button['classes'] );

				?>
				<span class="blog-fse-plugin-card plugin-card-<?php echo esc_attr( $plugin_slug ); ?>">
					<a href="<?php echo esc_url( $button['url'] ); ?>" class="<?php echo esc_attr( $button['classes'] ); ?>" data-originaltext="<?php echo esc_attr( $button['message'] ); ?>" data-name="<?php echo esc_attr( $plugin_name ); ?>" data-slug="<?php echo esc_attr( $plugin_slug ); ?>" aria-label="<?php echo esc_attr( $button['message'] ); ?>"><?php echo esc_html( $button['message'] ); ?></a>
				</span>
				<a href="https://wordpress.org/plugins/<?php echo esc_attr( $plugin_slug ); ?>" target="_blank"><?php esc_html_e( 'Learn more', 'blog-fse' ); ?></a>
				<?php
			}
		}

		/**
		 * Check if a plugin is installed and return the url to activate it if so.
		 *
		 * @param string $plugin_slug The plugin slug.
		 */
		private static function _is_plugin_installed( $plugin_slug ) {
			if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin_slug ) ) {
				$plugins = get_plugins( '/' . $plugin_slug );
				if ( ! empty( $plugins ) ) {
					$keys        = array_keys( $plugins );
					$plugin_file = $plugin_slug . '/' . $keys[0];
					$url         = wp_nonce_url( add_query_arg( array(
						'action' => 'activate',
						'plugin' => $plugin_file,
					), admin_url( 'plugins.php' ) ), 'activate-plugin_' . $plugin_file );
					return $url;
				}
			}
			return false;
		}

	}

	endif;

return new Blog_FSE_Notify_Admin();
