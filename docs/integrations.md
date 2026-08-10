# Integration examples

AIV Consent does not request third-party resources by itself. Optional scripts must be marked declaratively or explicitly mapped by a developer. Test every integration in a browser with the Network panel open.

## Declarative external analytics script

Because `data-src` is inert, the browser does not request this URL until `analytics` consent is true:

```html
<script
  type="text/plain"
  data-aiv-consent="analytics"
  data-src="https://analytics.example.com/client.js">
</script>
<script type="text/plain" data-aiv-consent="analytics">
  window.exampleAnalyticsInit();
</script>
```

Scripts are activated in document order where practical. A failed external request does not permanently stop later eligible scripts.

For a module, preserve its intended type explicitly:

```html
<script
  type="text/plain"
  data-aiv-type="module"
  data-aiv-consent="analytics"
  data-src="https://analytics.example.com/module.js">
</script>
```

## Yandex Metrika

The request for `tag.js` is held in `data-src` and is not made before analytics consent. Replace `YOUR_METRIKA_ID` during site integration:

```html
<script
  type="text/plain"
  data-aiv-consent="analytics"
  data-src="https://mc.yandex.ru/metrika/tag.js">
</script>
<script type="text/plain" data-aiv-consent="analytics">
  window.ym( YOUR_METRIKA_ID, 'init', {
    clickmap: true,
    trackLinks: true,
    accurateTrackBounce: true
  } );
</script>
```

If the vendor's current installation snippet creates its own network request, place the entire initialization snippet in the inert inline script. Verify the vendor's current documentation before production use.

## Marketing pixel

```html
<script type="text/plain" data-aiv-consent="marketing">
  var pixel = document.createElement( 'img' );
  pixel.alt = '';
  pixel.width = 1;
  pixel.height = 1;
  pixel.src = 'https://marketing.example.com/pixel?id=YOUR_PIXEL_ID';
  document.body.appendChild( pixel );
</script>
```

The pixel URL is assigned only after marketing consent.

## Public JavaScript API

```js
document.addEventListener( 'aiv-consent-ready', function () {
  if ( window.AIVConsent.hasConsent( 'analytics' ) ) {
    // Start an integration that cannot use declarative markup.
  }
} );

document.addEventListener( 'aiv-consent-change', function ( event ) {
  console.log( event.detail.categories );
} );
```

## WordPress script handles

```php
add_filter(
	'aiv_consent_script_categories',
	function ( $scripts ) {
		$scripts['my-analytics-handle'] = 'analytics';
		$scripts['my-pixel-handle']     = 'marketing';

		return $scripts;
	}
);
```

Only mapped external tags are converted to inert markup. Do not map WordPress core, checkout-critical, dependency-provider, or otherwise functional handles. Inline `before`/`after` data added with `wp_add_inline_script()` cannot be guaranteed safe by tag filtering; use declarative markup for those integrations. Complex dependency chains should use a purpose-built integration triggered through the public API.

## Cookie cleanup registry

```php
add_filter(
	'aiv_consent_category_cookies',
	function ( $cookies ) {
		$cookies['analytics'][] = '_analytics_cookie';
		$cookies['marketing'][] = '_marketing_cookie';

		return $cookies;
	}
);
```

Version 1 supports exact first-party cookie names. Cleanup is best-effort: browser path/domain rules and third-party domains can prevent deletion.

