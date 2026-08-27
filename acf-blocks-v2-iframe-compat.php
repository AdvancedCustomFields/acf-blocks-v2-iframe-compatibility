<?php
/**
 * Plugin Name:       ACF Blocks V2 Iframe Compatibility
 * Plugin URI:        https://advancedcustomfields.com
 * Description:       Forces the WordPress block editor to not use the iframe canvas, if there's an ACF v2 block on the site. This plugin is intended to give site owners more time to migrate to the iframe experience provided by core. It should not be used permanently.
 * Version:           1.0.0
 * Requires at least: 7.1
 * Requires PHP:      7.4
 * Author:            ACF
 * Author URI:        https://advancedcustomfields.com
 * Text Domain:       abic
 * Requires Plugins:  advanced-custom-fields-pro
 */

defined( 'ABSPATH' ) || exit;

if ( defined( 'ABIC_LOADED' ) ) {
	return;
}

define( 'ABIC_LOADED', true );
define( 'ABIC_VERSION', '1.0.0' );

/**
 * Cap ACF's default block version at 2 for blocks registered without an
 * explicit ACF block version.
 *
 * ACF bumps its default to 3 on WordPress 7.1+, which changes the
 * type of any block registered without acf_block_version set. Users of
 * this plugin want to stay on v2 without touching their block registrations,
 * so hold the default at 2. Blocks that explicitly opt into v3 are unaffected;
 * the filter default only applies when no version is set.
 *
 * @param integer $version The default block version ACF would have used.
 * @return integer
 */
add_filter( 'acf/blocks/default_block_version', 'abic_cap_default_block_version' );
function abic_cap_default_block_version( $version ) {
	return 2;
}

/**
 * Check if any registered ACF blocks are using ACF Block Version 2 (or lower).
 *
 * @return bool
 */
function abic_has_acf_v2_blocks(): bool {
	if ( ! function_exists( 'acf_get_block_types' ) ) {
		return false;
	}

	$blocks = acf_get_block_types();

	if ( empty( $blocks ) ) {
		return false;
	}

	foreach ( $blocks as $block ) {
		if ( empty( $block['acf_block_version'] ) || $block['acf_block_version'] < 3 ) {
			return true;
		}
	}

	return false;
}

/**
 * Enqueue the script that forces the editor canvas into an iframe.
 */
add_action( 'enqueue_block_editor_assets', 'abic_enqueue', 1 );
function abic_enqueue(): void {
	if ( ! abic_has_acf_v2_blocks() ) {
		return;
	}

	$path = __DIR__ . '/assets/js/force-iframeless-canvas.js';

	if ( ! is_file( $path ) ) {
		return;
	}

	$js = (string) file_get_contents( $path );

	if ( wp_script_is( 'react-jsx-runtime', 'registered' ) ) {
		wp_add_inline_script( 'react-jsx-runtime', $js, 'after' );
		return;
	}

	if ( wp_script_is( 'wp-element', 'registered' ) ) {
		wp_add_inline_script( 'wp-element', $js, 'after' );
	}
}
