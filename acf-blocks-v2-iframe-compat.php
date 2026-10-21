<?php
/**
 * Plugin Name:       ACF Blocks V2 Iframe Compatibility
 * Plugin URI:        https://advancedcustomfields.com
 * Description:       Forces the WordPress block editor to not use the iframe canvas, if there's an ACF v2 block on the site. This plugin is intended to give site owners more time to migrate to the iframe experience provided by core. It should not be used permanently.
 * Version:           1.0.2
 * Requires PHP:      7.4
 * Author:            ACF
 * Author URI:        https://advancedcustomfields.com
 * Text Domain:       abic
 * Requires Plugins:  advanced-custom-fields-pro
 */

defined( 'ABSPATH' ) || exit;

// No-op below WP 7.1 so this plugin can be installed ahead of the upgrade and activate on its own.
if ( version_compare( get_bloginfo( 'version' ), '7.1', '<' ) ) {
	return;
}

if ( defined( 'ABIC_LOADED' ) ) {
	return;
}

define( 'ABIC_LOADED', true );
define( 'ABIC_VERSION', '1.0.1' );

/**
 * Cap ACF's default block version for blocks registered without an explicit
 * ACF block version.
 *
 * ACF bumps its default to 3 on WordPress 7.1+, which changes the type of any
 * block registered without acf_block_version set. Users of this plugin want to
 * stay on the pre-7.1 defaults without touching their block registrations, so
 * mirror them: PHP-registered blocks (acf_register_block_type) get v1, JSON-
 * registered blocks (block.json) get v2. Blocks that explicitly opt into v3
 * are unaffected; the filter default only applies when no version is set.
 *
 * @param integer     $version The default block version ACF would have used.
 * @param array|null  $context The block settings/metadata array (unused).
 * @param string|null $source  Registration source: 'php', 'json', or null on
 *                             ACF versions that don't pass this arg — in which
 *                             case both paths fall through to v2.
 * @return integer
 */
add_filter( 'acf/blocks/default_block_version', 'abic_cap_default_block_version', 10, 3 );
function abic_cap_default_block_version( $version, $context = null, $source = null ) {
	return 'php' === $source ? 1 : 2;
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
	
add_action( 'admin_head', function() {
    $screen = get_current_screen();
    if ( ! $screen || ! in_array( $screen->id, array( 'post', 'page' ), true ) ) {
        // Also covers custom post types via $screen->is_block_editor()
        if ( ! $screen || ! $screen->is_block_editor() ) {
            return;
        }
    }
	?>
<style>
/* ==========================================================
   ACF modal → left-docked, full-height editor panel
   ========================================================== */

:root {
  --acf-modal-right-offset: 280px;
}

/* No block sidebar rendered → no gap on the right */
html body:not(:has(.interface-interface-skeleton__sidebar .interface-complementary-area)) {
  --acf-modal-right-offset: 0px;
}

@media (max-width: 782px) {
  :root {
    --acf-modal-right-offset: 0px;
  }
}

/* ---------- Overlay: transparent fullscreen shell ---------- */
html body .components-modal__screen-overlay.acf-expanded-editor-panel-overlay {
  position: fixed !important;
  inset: 0 !important;
  width: auto !important;
  height: auto !important;
  background: transparent !important;
  pointer-events: none !important;
  display: block !important;
  z-index: 100000 !important;
}

/* ---------- Frame: fixed panel right of admin menu ---------- */
html body .components-modal__screen-overlay.acf-expanded-editor-panel-overlay > .components-modal__frame,
html body .components-modal__screen-overlay.acf-expanded-editor-panel-overlay > .components-modal__frame.acf-block-form-modal,
html body .components-modal__screen-overlay.acf-expanded-editor-panel-overlay > .acf-block-form-modal {
  position: fixed !important;
  top: var(--wp-admin--admin-bar--height, 32px) !important;
  left: 160px !important;
  right: var(--acf-modal-right-offset, 280px) !important;
  bottom: 0 !important;
  width: auto !important;
  height: auto !important;
  max-width: none !important;
  max-height: none !important;
  min-width: 0 !important;
  min-height: 0 !important;
  margin: 0 !important;
  padding: 0 !important;
  transform: none !important;
  align-self: auto !important;
  border-radius: 0 !important;
  box-shadow: none !important;
  pointer-events: auto !important;
  display: flex !important;
  flex-direction: column !important;
}

/* ---------- Content: column flex ---------- */
html body .components-modal__screen-overlay.acf-expanded-editor-panel-overlay > .components-modal__frame > .components-modal__content {
  flex: 1 1 auto !important;
  display: flex !important;
  flex-direction: column !important;
  min-height: 0 !important;
  height: auto !important;
  max-height: none !important;
  width: 100% !important;
  max-width: 100% !important;
  margin: 0 !important;
  padding: 0 !important;
  overflow: hidden !important;
}

/* ---------- Header: in flow ---------- */
html body .components-modal__screen-overlay.acf-expanded-editor-panel-overlay > .components-modal__frame > .components-modal__content > .components-modal__header {
  position: relative !important;
  flex-shrink: 0 !important;
  width: 100% !important;
  max-height: none !important;
  padding: 12px 20px !important;
  border-bottom: 1px solid #ddd !important;
  background: #fff !important;
}

/* ---------- Children container: scrolls ---------- */
html body .components-modal__screen-overlay.acf-expanded-editor-panel-overlay > .components-modal__frame > .components-modal__content > .components-modal__children-container {
  flex: 1 1 auto !important;
  min-height: 0 !important;
  overflow-y: auto !important;
  width: 100% !important;
  max-width: 100% !important;
  margin: 0 !important;
  padding: 0 !important;
}

/* ---------- Inner wrappers full width ---------- */
html body .components-modal__screen-overlay.acf-expanded-editor-panel-overlay .acf-modal-block-form-container,
html body .components-modal__screen-overlay.acf-expanded-editor-panel-overlay .acf-block-panel,
html body .components-modal__screen-overlay.acf-expanded-editor-panel-overlay .acf-block-fields {
  width: 100% !important;
  max-width: 100% !important;
  box-sizing: border-box !important;
}
html body .components-modal__screen-overlay.acf-expanded-editor-panel-overlay .acf-modal-block-form-container {
  padding: 16px 20px !important;
}

/* ---------- Repeater stretches, kill inline mini widths ---------- */
html body .components-modal__screen-overlay.acf-expanded-editor-panel-overlay .acf-repeater > table {
  width: 100% !important;
  table-layout: auto !important;
}
html body .components-modal__screen-overlay.acf-expanded-editor-panel-overlay .acf-repeater .acf-row > td.acf-row-handle {
  width: 30px !important;
  min-width: 30px !important;
}
html body .components-modal__screen-overlay.acf-expanded-editor-panel-overlay .acf-repeater .acf-row > td.acf-fields {
  width: auto !important;
}

/* ---------- Left offset variants ---------- */

/* Folded admin menu = 36px */
html body.folded .components-modal__screen-overlay.acf-expanded-editor-panel-overlay > .components-modal__frame {
  left: 36px !important;
}

/* Fullscreen / distraction-free = admin menu hidden */
html body.is-fullscreen-mode .components-modal__screen-overlay.acf-expanded-editor-panel-overlay > .components-modal__frame,
html body:has(.editor-editor-interface.is-distraction-free) .components-modal__screen-overlay.acf-expanded-editor-panel-overlay > .components-modal__frame {
  left: 0 !important;
}

/* Mobile: admin menu hidden, sidebar overlays */
@media (max-width: 782px) {
  html body .components-modal__screen-overlay.acf-expanded-editor-panel-overlay > .components-modal__frame {
    left: 0 !important;
  }
}
</style>    
    <?php
});