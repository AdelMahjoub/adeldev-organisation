/**
 * Organisation details page: clicking a shortcode (under the fields and in the Help
 * tabs) copies it to the clipboard.
 */
( function ( wp ) {
	const { __, sprintf } = wp.i18n;

	const copy = ( text ) => {
		if ( navigator.clipboard && window.isSecureContext ) {
			return navigator.clipboard.writeText( text );
		}
		// Fallback outside HTTPS.
		const area = document.createElement( 'textarea' );
		area.value = text;
		area.style.position = 'fixed';
		area.style.opacity = '0';
		document.body.appendChild( area );
		area.select();
		document.execCommand( 'copy' );
		area.remove();
		return Promise.resolve();
	};

	const activate = ( code ) => {
		const text = code.textContent.trim();
		copy( text ).then( () => {
			code.title = __( 'Copied', 'adeldev-organisation' );
			code.style.outline = '2px solid #00a32a';
			/* translators: %s: the copied shortcode. */
			wp.a11y.speak( sprintf( __( 'Copied: %s', 'adeldev-organisation' ), text ) );
			setTimeout( () => {
				code.title = code.dataset.label;
				code.style.outline = '';
			}, 1500 );
		} );
	};

	document.addEventListener( 'DOMContentLoaded', () => {
		document.querySelectorAll( 'code.adeldev-org-copy' ).forEach( ( code ) => {
			code.dataset.label = __( 'Click to copy', 'adeldev-organisation' );
			code.title = code.dataset.label;
			code.style.cursor = 'pointer';
			code.setAttribute( 'role', 'button' );
			code.setAttribute( 'tabindex', '0' );
			code.addEventListener( 'click', () => activate( code ) );
			code.addEventListener( 'keydown', ( event ) => {
				if ( 'Enter' === event.key || ' ' === event.key ) {
					event.preventDefault();
					activate( code );
				}
			} );
		} );
	} );
} )( window.wp );
