document.addEventListener( 'DOMContentLoaded', function () {
	[ 'siteurl', 'home' ].forEach( function ( id ) {
		var input = document.getElementById( id );

		// Skip missing fields, or fields core already locked via WP_SITEURL / WP_HOME.
		if ( ! input || input.disabled ) {
			return;
		}

		input.disabled = true;
		input.classList.add( 'disabled' );

		var text = window.wlds_s ? window.wlds_s.field_text : '';

		// Reuse core's existing description (e.g. #home-description) so only one line shows.
		var existing = document.getElementById( id + '-description' );
		if ( existing ) {
			existing.textContent = text;
			return;
		}

		var notice = document.createElement( 'p' );
		notice.className = 'description';
		notice.textContent = text;
		input.parentNode.appendChild( notice );
	} );
} );
