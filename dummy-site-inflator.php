<?php
/**
 * Plugin Name:       Dummy Site Inflator
 * Plugin URI:        https://lumumbas-blog.co.ke/plugins/
 * Description:       Generate dummy posts with large images to inflate your test site size for QA, load testing, and hosting benchmarks.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Patrick Lumumba
 * Author URI:        https://lumumbas-blog.co.ke
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       dummy-site-inflator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Plugin constants.
define( 'DSI_VERSION', '1.0.0' );
define( 'DSI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DSI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'DSI_IMAGE_SOURCE_URL', 'https://lumumbas-blog.co.ke/wp-content/uploads/2026/03/1.png' );
define( 'DSI_IMAGE_FILENAME', '1.png' );
define( 'DSI_META_KEY', '_dummy_inflator_post' );
define( 'DSI_BATCH_SIZE', 1 );

// Include required files.
require_once DSI_PLUGIN_DIR . 'includes/class-dsi-generator.php';
require_once DSI_PLUGIN_DIR . 'includes/class-dsi-cleanup.php';
require_once DSI_PLUGIN_DIR . 'admin/class-dsi-admin.php';

/**
 * Main plugin class.
 */
final class Dummy_Site_Inflator {

	/**
	 * Single instance of the plugin.
	 *
	 * @var Dummy_Site_Inflator
	 */
	private static $instance = null;

	/**
	 * Get the single instance.
	 *
	 * @return Dummy_Site_Inflator
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor — register hooks.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'plugins_loaded', array( $this, 'init_components' ) );
		register_activation_hook( __FILE__, array( $this, 'on_activate' ) );
	}

	/**
	 * Load plugin text domain for translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'dummy-site-inflator',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}

	/**
	 * Initialise plugin components.
	 */
	public function init_components() {
		DSI_Admin::get_instance();
	}

	/**
	 * On plugin activation — create the local storage directory.
	 */
	public function on_activate() {
		$upload_dir = wp_upload_dir();
		$dsi_dir    = trailingslashit( $upload_dir['basedir'] ) . 'dummy-site-inflator';

		if ( ! file_exists( $dsi_dir ) ) {
			wp_mkdir_p( $dsi_dir );
		}

		// Add an index.php to prevent directory listing.
		$index_file = trailingslashit( $dsi_dir ) . 'index.php';
		if ( ! file_exists( $index_file ) ) {
			file_put_contents( $index_file, '<?php // Silence is golden.' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
	}
}

// Boot the plugin.
Dummy_Site_Inflator::get_instance();
