<?php
/**
 * Demo Child theme bootstrap.
 *
 * Enqueues the webpack-built bundle from assets/dist/. Versions are the
 * file mtimes so cache busting is automatic on deploy.
 */

add_action(
	'wp_enqueue_scripts',
	function () {
		$dist_dir = get_stylesheet_directory() . '/assets/dist';
		$dist_url = get_stylesheet_directory_uri() . '/assets/dist';

		if ( file_exists( $dist_dir . '/css/style.css' ) ) {
			wp_enqueue_style(
				'demo-child',
				$dist_url . '/css/style.css',
				array(),
				(string) filemtime( $dist_dir . '/css/style.css' )
			);
		}

		if ( file_exists( $dist_dir . '/js/main.js' ) ) {
			wp_enqueue_script(
				'demo-child',
				$dist_url . '/js/main.js',
				array(),
				(string) filemtime( $dist_dir . '/js/main.js' ),
				true
			);
		}
	}
);
