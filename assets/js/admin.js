( function () {
	'use strict';

	var form = document.querySelector( '.aiv-consent-admin form' );

	if ( ! form ) {
		return;
	}

	form.addEventListener( 'input', function () {
		form.dataset.aivConsentDirty = 'true';
	}, { once: true } );

	form.querySelectorAll( '.aiv-consent-color-field' ).forEach( function ( input ) {
		input.addEventListener( 'input', function () {
			var value = input.parentElement.querySelector( '.aiv-consent-color-value' );

			if ( value ) {
				value.textContent = input.value;
			}
		} );
	} );
}() );
