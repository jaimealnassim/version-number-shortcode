<?php
/**
 * Uninstall routine for Version Number Shortcode.
 *
 * Runs automatically when the plugin is deleted via the WordPress admin.
 * Removes all options and transients created by the plugin.
 *
 * @package VersionNumberShortcode
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove all stored options.
delete_option( 'vns_release_url' );
delete_option( 'vns_cached_version' );
delete_option( 'vns_last_check' );
delete_option( 'vns_cache_interval' );

// Remove the transient cache.
delete_transient( 'vns_release_version' );
