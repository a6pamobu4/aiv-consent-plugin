( function () {
	'use strict';

	var form = document.querySelector( '.aiv-consent-admin form' );

	if ( ! form ) {
		return;
	}

	form.addEventListener( 'input', function () {
		form.dataset.aivConsentDirty = 'true';
	}, { once: true } );
}() );

