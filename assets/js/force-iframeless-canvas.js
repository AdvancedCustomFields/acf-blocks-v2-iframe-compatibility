/**
 * Force the WordPress block editor to render inline instead of inside its
 * iframe canvas.
 *
 * WordPress 7.1 mounts the editor inside an iframe by passing
 * shouldIframe: true to the editor's root component. ACF block version 2 was
 * built for the pre-iframe editor and does not work inside one.
 *
 * This shim wraps React's element factories (JSX runtime and
 * wp.element.createElement) and rewrites shouldIframe: true to
 * shouldIframe: false before the editor mounts.
 */
(function () {
	'use strict';

	function withoutIframe( props ) {
		if ( props && typeof props === 'object' && 'shouldIframe' in props && props.shouldIframe === true ) {
			return Object.assign( {}, props, { shouldIframe: false } );
		}
		return props;
	}

	function patchFn( obj, name ) {
		if ( ! obj || typeof obj[ name ] !== 'function' ) {
			return false;
		}
		var current = obj[ name ];
		if ( current.__abicPatched || current.__abicUnpatchable ) {
			return true;
		}
		var wrapper = function ( type, props ) {
			var args = Array.prototype.slice.call( arguments );
			args[ 1 ] = withoutIframe( props );
			return current.apply( this, args );
		};
		wrapper.__abicPatched = true;
		try {
			obj[ name ] = wrapper;
			if ( obj[ name ] === wrapper ) {
				return true;
			}
		} catch ( e ) {
			// Getter-only or non-writable slot — fall through to mark as unpatchable.
		}
		try {
			current.__abicUnpatchable = true;
		} catch ( e ) {
			// Frozen function object — can't mark it. patchAll still treats us
			// as done via the return below so the retry loop stops.
		}
		return true;
	}

	// Only stop polling once the critical factories are confirmed patched, not
	// just when the parent globals appear. In practice WordPress's UMD bundles
	// define each object's function slots in the same synchronous evaluation
	// that creates the object, but guarding on the slots directly costs nothing
	// and closes the "object present, function slot not yet assigned" window.
	function patchAll() {
		var jsxRuntimeReady = false;
		var wpElementReady  = false;

		if ( window.ReactJSXRuntime ) {
			jsxRuntimeReady = patchFn( window.ReactJSXRuntime, 'jsx' )
				&& patchFn( window.ReactJSXRuntime, 'jsxs' );
			patchFn( window.ReactJSXRuntime, 'jsxDEV' ); // Dev builds only; optional.
		}
		if ( window.wp && wp.element ) {
			wpElementReady = patchFn( wp.element, 'createElement' );
			patchFn( wp.element, 'jsx' );  // Optional (wp.element via JSX runtime).
			patchFn( wp.element, 'jsxs' ); // Optional.
		}

		return jsxRuntimeReady && wpElementReady;
	}

	if ( ! patchAll() ) {
		var tries = 0;
		var timer = window.setInterval( function () {
			tries += 1;
			if ( patchAll() || tries > 40 ) {
				window.clearInterval( timer );
			}
		}, 25 );
	}
})();
