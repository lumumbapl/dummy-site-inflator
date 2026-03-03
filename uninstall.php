<?php
/**
 * Dummy Site Inflator — Uninstall Script
 *
 * Runs when the plugin is deleted from the WordPress admin.
 * Removes the cached source image directory.
 * NOTE: Dummy posts are NOT deleted on uninstall to prevent accidental
 * data loss. Use the plugin's Cleanup feature before deleting the plugin.
 *
 * @package DummySiteInflator
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove the cached source image and its directory.
$upload_dir = wp_upload_dir();
$dsi_dir    = trailingslashit( $upload_dir['basedir'] ) . 'dummy-site-inflator';

if ( is_dir( $dsi_dir ) ) {
	$files = glob( trailingslashit( $dsi_dir ) . '*' );
	if ( $files ) {
		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				unlink( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			}
		}
	}
	rmdir( $dsi_dir );
}
