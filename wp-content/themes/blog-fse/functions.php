<?php

if ( ! function_exists( 'blog_fse_setup' ) ) :

	function blog_fse_setup() {

		// Enqueue editor styles.
		add_editor_style( 'style.css' );
	}

endif;

add_action( 'after_setup_theme', 'blog_fse_setup' );

if ( ! function_exists( 'blog_fse_scripts' ) ) :

	/**
	 * Enqueue styles.
	 *
	 * @return void
	 */
	function blog_fse_scripts() {
		// Register theme stylesheet.
		$theme_version = wp_get_theme()->get( 'Version' );

		$version_string = is_string( $theme_version ) ? $theme_version : false;
		wp_register_style(
			'blog-fse-style',
			get_template_directory_uri() . '/style.css',
			array(),
			$version_string
		);

		// Enqueue theme stylesheet.
		wp_enqueue_style( 'blog-fse-style' );

	}

endif;

add_action( 'wp_enqueue_scripts', 'blog_fse_scripts' );

if ( !function_exists( 'blog_fse_plugin_is_activated' ) ) {

	/**
	 * Check plugin activation
	 */
	function blog_fse_plugin_is_activated() {
		return defined( 'WPST_BLOCK_TEMPLATES_VER' ) ? true : false;
	}

}

if ( is_admin() ) {
	require_once( trailingslashit(get_template_directory()) . 'inc/dashboard.php' );
}