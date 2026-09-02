<?php
/**
 * Environment-driven wp-config.
 *
 * Nothing secret lives in this file — every credential comes from .env
 * (local) or from the server's own untracked config (staging/production).
 */

if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'] ) {
	$_SERVER['HTTPS']       = 'on';
	$_SERVER['SERVER_PORT'] = 443;
}

/** Composer autoload */
if ( file_exists( __DIR__ . '/wp-content/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/wp-content/vendor/autoload.php';
}

/** Load .env */
try {
	$dotenv = Dotenv\Dotenv::createImmutable( __DIR__ );
	$dotenv->load();
	$dotenv->required( array( 'WP_HOME', 'WP_DIR' ) );
	$dotenv->required( array( 'DB_NAME', 'DB_USER', 'DB_PASSWORD', 'DB_HOST' ) );
	$dotenv->required( array( 'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT' ) );
} catch ( Exception $e ) {
	http_response_code( 500 );
	exit( 'Missing environment configuration. Copy .env.example to .env.' );
}

/** URLs & paths (core in cms/, wp-content at root) */
define( 'WP_HOME', $_ENV['WP_HOME'] );
define( 'WP_SITEURL', $_ENV['WP_HOME'] . '/' . $_ENV['WP_DIR'] );
define( 'WP_CONTENT_URL', $_ENV['WP_HOME'] . '/wp-content' );
define( 'WP_CONTENT_DIR', __DIR__ . '/wp-content' );

/** Database */
define( 'DB_NAME', $_ENV['DB_NAME'] );
define( 'DB_USER', $_ENV['DB_USER'] );
define( 'DB_PASSWORD', $_ENV['DB_PASSWORD'] );
define( 'DB_HOST', $_ENV['DB_HOST'] );
define( 'DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8' );
define( 'DB_COLLATE', $_ENV['DB_COLLATE'] ?? '' );
$table_prefix = $_ENV['DB_TABLE_PREFIX'] ?? 'wp_';

/** Keys & salts — https://api.wordpress.org/secret-key/1.1/salt/ */
define( 'AUTH_KEY', $_ENV['AUTH_KEY'] );
define( 'SECURE_AUTH_KEY', $_ENV['SECURE_AUTH_KEY'] );
define( 'LOGGED_IN_KEY', $_ENV['LOGGED_IN_KEY'] );
define( 'NONCE_KEY', $_ENV['NONCE_KEY'] );
define( 'AUTH_SALT', $_ENV['AUTH_SALT'] );
define( 'SECURE_AUTH_SALT', $_ENV['SECURE_AUTH_SALT'] );
define( 'LOGGED_IN_SALT', $_ENV['LOGGED_IN_SALT'] );
define( 'NONCE_SALT', $_ENV['NONCE_SALT'] );

/** Developer flags */
define( 'WP_DEBUG', filter_var( $_ENV['WP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN ) );
define( 'WP_DEBUG_LOG', filter_var( $_ENV['WP_DEBUG_LOG'] ?? false, FILTER_VALIDATE_BOOLEAN ) );
define( 'WP_DEBUG_DISPLAY', filter_var( $_ENV['WP_DEBUG_DISPLAY'] ?? false, FILTER_VALIDATE_BOOLEAN ) );
define( 'SCRIPT_DEBUG', filter_var( $_ENV['SCRIPT_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN ) );
define( 'DISALLOW_FILE_EDIT', filter_var( $_ENV['DISALLOW_FILE_EDIT'] ?? true, FILTER_VALIDATE_BOOLEAN ) );
define( 'WP_ENVIRONMENT_TYPE', $_ENV['WP_ENVIRONMENT_TYPE'] ?? 'production' );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
require_once ABSPATH . 'wp-settings.php';
