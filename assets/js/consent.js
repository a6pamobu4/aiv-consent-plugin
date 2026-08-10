( function () {
	'use strict';

	var config = window.AIVConsentConfig || {};
	var root = null;
	var banner = null;
	var backdrop = null;
	var dialog = null;
	var lastFocused = null;
	var readyDispatched = false;
	var state = readState();

	function defaultCategories() {
		var categories = {};

		Object.keys( config.categories || {} ).forEach( function ( key ) {
			categories[ key ] = Boolean( config.categories[ key ].required );
		} );

		return categories;
	}

	function defaultState() {
		return {
			version: String( config.version || '' ),
			timestamp: 0,
			categories: defaultCategories(),
			valid: false
		};
	}

	function readCookie( name ) {
		var prefix = name + '=';
		var cookies = document.cookie ? document.cookie.split( '; ' ) : [];
		var match = cookies.find( function ( cookie ) {
			return cookie.indexOf( prefix ) === 0;
		} );

		return match ? match.slice( prefix.length ) : '';
	}

	function readState() {
		var fallback = defaultState();
		var raw = readCookie( String( config.cookieName || 'aiv_consent' ) );
		var parsed;
		var categories;
		var version;
		var timestamp;

		if ( ! raw ) {
			return fallback;
		}

		try {
			parsed = JSON.parse( decodeURIComponent( raw ) );
		} catch ( error ) {
			return fallback;
		}

		if ( ! parsed || typeof parsed !== 'object' ) {
			return fallback;
		}

		version = typeof parsed.v === 'string' ? parsed.v : parsed.version;
		timestamp = Number( parsed.t || parsed.timestamp );
		categories = parsed.c || parsed.categories;

		if ( typeof version !== 'string' || ! Number.isInteger( timestamp ) || timestamp <= 0 || ! categories || typeof categories !== 'object' ) {
			return fallback;
		}

		if ( config.repromptOnVersion && version !== String( config.version ) ) {
			return fallback;
		}

		fallback.version = version;
		fallback.timestamp = timestamp;
		fallback.valid = true;

		Object.keys( fallback.categories ).forEach( function ( key ) {
			fallback.categories[ key ] = Boolean( config.categories[ key ].required ) || categories[ key ] === true;
		} );

		return fallback;
	}

	function publicState() {
		return {
			version: state.version,
			timestamp: state.timestamp,
			categories: Object.assign( {}, state.categories ),
			valid: state.valid
		};
	}

	function writeCookie() {
		var lifetime = Math.max( 1, Number( config.lifetimeDays ) || 180 );
		var maxAge = Math.round( lifetime * 86400 );
		var expires = new Date( Date.now() + maxAge * 1000 ).toUTCString();
		var value = encodeURIComponent( JSON.stringify( {
			v: state.version,
			t: state.timestamp,
			c: state.categories
		} ) );
		var cookie = String( config.cookieName || 'aiv_consent' ) + '=' + value + '; Path=/; Max-Age=' + maxAge + '; Expires=' + expires + '; SameSite=Lax';

		if ( config.secure ) {
			cookie += '; Secure';
		}

		document.cookie = cookie;
	}

	function dispatch( name ) {
		document.dispatchEvent( new CustomEvent( name, {
			detail: {
				categories: Object.assign( {}, state.categories )
			}
		} ) );
	}

	function deleteCookie( name ) {
		var base = encodeURIComponent( name ) + '=; Max-Age=0; Expires=Thu, 01 Jan 1970 00:00:00 GMT; SameSite=Lax';
		var paths = [ '/' ];
		var segments = window.location.pathname.split( '/' ).filter( Boolean );
		var current = '';

		segments.forEach( function ( segment ) {
			current += '/' + segment;
			paths.push( current );
		} );

		paths.forEach( function ( path ) {
			document.cookie = base + '; Path=' + path;
			document.cookie = base + '; Path=' + path + '; Domain=' + window.location.hostname;
		} );
	}

	function cleanupRevoked( revoked ) {
		var registry = config.categoryCookies || {};

		revoked.forEach( function ( category ) {
			( registry[ category ] || [] ).forEach( deleteCookie );
		} );
	}

	function save( categories ) {
		var previous = Object.assign( {}, state.categories );
		var next = defaultCategories();
		var revoked = [];

		Object.keys( next ).forEach( function ( key ) {
			if ( ! config.categories[ key ].required ) {
				next[ key ] = categories[ key ] === true;
			}

			if ( previous[ key ] === true && next[ key ] === false ) {
				revoked.push( key );
			}
		} );

		state = {
			version: String( config.version || '' ),
			timestamp: Math.floor( Date.now() / 1000 ),
			categories: next,
			valid: true
		};

		writeCookie();
		cleanupRevoked( revoked );
		updateInterface();
		closeSettings();
		dispatch( 'aiv-consent-change' );
		activateBlockedScripts();

		if ( revoked.length && config.reloadOnRevoke ) {
			window.location.reload();
		}
	}

	function acceptAll() {
		var categories = {};

		Object.keys( config.categories || {} ).forEach( function ( key ) {
			categories[ key ] = true;
		} );

		save( categories );
	}

	function rejectOptional() {
		save( defaultCategories() );
	}

	function updateInterface() {
		if ( banner ) {
			banner.hidden = state.valid;
		}

		if ( root ) {
			root.querySelectorAll( '[data-aiv-consent-category]' ).forEach( function ( input ) {
				input.checked = state.categories[ input.value ] === true;
			} );
		}
	}

	function focusableElements() {
		if ( ! dialog ) {
			return [];
		}

		return Array.from( dialog.querySelectorAll( 'a[href], button:not([disabled]), input:not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])' ) );
	}

	function openSettings( trigger ) {
		if ( ! backdrop || ! dialog ) {
			return;
		}

		lastFocused = trigger instanceof HTMLElement ? trigger : document.activeElement;
		updateInterface();
		backdrop.hidden = false;
		document.documentElement.classList.add( 'aiv-consent-modal-open' );
		dialog.focus();
	}

	function closeSettings() {
		if ( ! backdrop || backdrop.hidden ) {
			return;
		}

		backdrop.hidden = true;
		document.documentElement.classList.remove( 'aiv-consent-modal-open' );

		if ( lastFocused && typeof lastFocused.focus === 'function' ) {
			lastFocused.focus();
		}
	}

	function trapFocus( event ) {
		var focusable;
		var first;
		var last;

		if ( ! backdrop || backdrop.hidden ) {
			return;
		}

		if ( event.key === 'Escape' ) {
			event.preventDefault();
			closeSettings();
			return;
		}

		if ( event.key !== 'Tab' ) {
			return;
		}

		focusable = focusableElements();

		if ( ! focusable.length ) {
			event.preventDefault();
			dialog.focus();
			return;
		}

		first = focusable[ 0 ];
		last = focusable[ focusable.length - 1 ];

		if ( event.shiftKey && ( document.activeElement === first || document.activeElement === dialog ) ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && document.activeElement === last ) {
			event.preventDefault();
			first.focus();
		}
	}

	function copySafeAttributes( source, target ) {
		[ 'id', 'nonce', 'integrity', 'crossorigin', 'referrerpolicy', 'fetchpriority' ].forEach( function ( attribute ) {
			if ( source.hasAttribute( attribute ) ) {
				target.setAttribute( attribute, source.getAttribute( attribute ) );
			}
		} );

		if ( source.hasAttribute( 'nomodule' ) ) {
			target.noModule = true;
		}

		if ( source.dataset.aivType ) {
			target.type = source.dataset.aivType;
		}
	}

	function activateScript( source ) {
		return new Promise( function ( resolve ) {
			var replacement = document.createElement( 'script' );
			var external = source.dataset.src;

			copySafeAttributes( source, replacement );
			source.dataset.aivConsentActivated = 'true';

			if ( external ) {
				replacement.async = source.hasAttribute( 'async' );
				replacement.src = external;
				replacement.addEventListener( 'load', resolve, { once: true } );
				replacement.addEventListener( 'error', resolve, { once: true } );
			} else {
				replacement.textContent = source.textContent;
			}

			source.replaceWith( replacement );

			if ( ! external ) {
				resolve();
			}
		} );
	}

	async function activateBlockedScripts() {
		var scripts = Array.from( document.querySelectorAll( 'script[type="text/plain"][data-aiv-consent]:not([data-aiv-consent-activated])' ) );

		for ( var index = 0; index < scripts.length; index += 1 ) {
			if ( state.categories[ scripts[ index ].dataset.aivConsent ] === true ) {
				await activateScript( scripts[ index ] );
			}
		}
	}

	function bindInterface() {
		root = document.querySelector( '[data-aiv-consent-root]' );

		if ( ! root ) {
			return;
		}

		banner = root.querySelector( '[data-aiv-consent-banner]' );
		backdrop = root.querySelector( '[data-aiv-consent-backdrop]' );
		dialog = root.querySelector( '[data-aiv-consent-dialog]' );

		root.querySelectorAll( '[data-aiv-consent-accept]' ).forEach( function ( button ) {
			button.addEventListener( 'click', acceptAll );
		} );
		root.querySelectorAll( '[data-aiv-consent-reject]' ).forEach( function ( button ) {
			button.addEventListener( 'click', rejectOptional );
		} );
		root.querySelector( '[data-aiv-consent-customize]' ).addEventListener( 'click', function ( event ) {
			openSettings( event.currentTarget );
		} );
		root.querySelector( '[data-aiv-consent-close]' ).addEventListener( 'click', closeSettings );
		root.querySelector( '[data-aiv-consent-form]' ).addEventListener( 'submit', function ( event ) {
			var categories = defaultCategories();

			event.preventDefault();
			root.querySelectorAll( '[data-aiv-consent-category]' ).forEach( function ( input ) {
				categories[ input.value ] = input.checked;
			} );
			save( categories );
		} );
		backdrop.addEventListener( 'click', function ( event ) {
			if ( event.target === backdrop ) {
				closeSettings();
			}
		} );
		document.addEventListener( 'keydown', trapFocus );
		updateInterface();
	}

	function initialize() {
		bindInterface();
		activateBlockedScripts();

		if ( ! readyDispatched ) {
			readyDispatched = true;
			dispatch( 'aiv-consent-ready' );
		}
	}

	document.addEventListener( 'click', function ( event ) {
		var trigger = event.target.closest( '[data-aiv-consent-open]' );

		if ( trigger ) {
			event.preventDefault();
			openSettings( trigger );
		}
	} );

	window.AIVConsent = Object.freeze( {
		hasConsent: function ( category ) {
			return state.categories[ String( category ) ] === true;
		},
		getState: publicState,
		openSettings: function () {
			openSettings();
		},
		acceptAll: acceptAll,
		rejectOptional: rejectOptional
	} );

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initialize, { once: true } );
	} else {
		initialize();
	}
}() );
