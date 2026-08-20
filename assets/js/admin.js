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

	form.querySelectorAll( '[data-aiv-custom-integrations]' ).forEach( function ( manager ) {
		var list = manager.querySelector( '[data-aiv-custom-list]' );
		var template = manager.querySelector( '[data-aiv-custom-template]' );
		var nextIndex = list.querySelectorAll( '[data-aiv-custom-row]' ).length;

		manager.querySelector( '[data-aiv-custom-add]' ).addEventListener( 'click', function () {
			var html = template.innerHTML.replaceAll( '__INDEX__', String( nextIndex ) );
			var holder = document.createElement( 'div' );

			nextIndex += 1;
			holder.innerHTML = html.trim();
			list.appendChild( holder.firstElementChild );
		} );

		manager.addEventListener( 'click', function ( event ) {
			var remove = event.target.closest( '[data-aiv-custom-remove]' );

			if ( remove ) {
				remove.closest( '[data-aiv-custom-row]' ).remove();
			}
		} );
	} );
}() );
