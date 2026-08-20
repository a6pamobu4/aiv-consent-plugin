# Integration guide

AIV Consent uses strict prior blocking. Optional provider URLs stay in `data-src`, so the browser makes no analytics or marketing request until the corresponding local consent category is true.

## Declarative scripts

```html
<script type="text/plain" data-aiv-consent="analytics" data-src="https://analytics.example.com/client.js"></script>
<script type="text/plain" data-aiv-consent="analytics">
  window.exampleAnalyticsInit();
</script>
```

Scripts activate in document order where practical. A failed external request does not permanently stop later eligible scripts. Modules can preserve their type with `data-aiv-type="module"`.

## Built-in Yandex Metrica

Prefer **Settings > AIV Consent > Integrations — Yandex Metrica**. The module builds the `ym` queue, records its initialization timestamp, inserts `tag.js`, and queues the configured counter initialization as one inert bootstrap. Nothing from `mc.yandex.ru` is requested before analytics consent, and no `<noscript>` tracking image is emitted.

The settings include the numeric counter ID, Webvisor, click map, external-link tracking, and accurate bounce tracking. Review the informational privacy note in the admin before enabling the service.

## Built-in Google Analytics 4

Prefer the GA4 admin module. It holds `gtag.js` in `data-src`, then initializes `dataLayer` and the validated `G-...` measurement ID after the external script activates. It does not implement Google Consent Mode or pre-consent cookieless pings.

The Russian-market warning is informational. Obtain appropriate advice before enabling GA4 where 152-FZ, localization, or cross-border transfer rules apply; a consent choice alone does not settle those questions.

## Custom integrations

The repeatable control supports a name, enabled state, registered optional category, HTTP(S) script URL, inline JavaScript source, and `normal`, `async`, or `defer` strategy. The necessary category is never available.

Inline source is privileged trusted code. Only a user with `unfiltered_html` can create or modify it. Users without the capability see it read-only, and the sanitizer preserves the stored source. Never paste `<script>` tags; the field accepts JavaScript source only and rejects script tags. The admin does not execute this code.

The frontend loader never uses `eval()` or `new Function()`. It creates a real script element only after consent.

## WordPress script handles and caches

```php
add_filter(
	'aiv_consent_script_categories',
	function ( $scripts ) {
		$scripts['my-analytics-handle'] = 'analytics';
		return $scripts;
	}
);
```

Mapped external tags remain inert server-side regardless of the request cookie. The same HTML can therefore be served by WP Rocket, nginx, reverse-proxy, or CDN caches to visitors with different local choices. Do not map WordPress core, checkout-critical, dependency-provider, or functional handles.

Inline `before`/`after` data added through `wp_add_inline_script()` cannot be guaranteed safe by external-tag filtering. Use declarative markup or a purpose-built registry integration for those cases.

## Cookie cleanup

Legacy strings remain exact-name descriptors. Structured descriptors support only `exact` and `prefix`; administrator-supplied regular expressions are not accepted.

```php
add_filter(
	'aiv_consent_category_cookies',
	function ( $cookies ) {
		$cookies['analytics'][] = '_analytics_cookie';
		$cookies['marketing'][] = array(
			'type'  => 'prefix',
			'value' => '_marketing_',
		);
		return $cookies;
	}
);
```

Cleanup is best-effort. Cookie path/domain scope, HttpOnly flags, local storage, and third-party domains can prevent deletion.

## Integration registry

Register modules with `aiv_consent_register_integration()` or filter `aiv_consent_integrations`. A normalized definition contains `id`, `label`, `description`, optional `category`, `enabled` or `is_enabled`, `admin_fields`, `cookies`, and `render_callback`. Necessary categories are rejected.

```php
add_filter(
	'aiv_consent_integrations',
	function ( $integrations ) {
		$integrations['example_analytics'] = array(
			'label'           => 'Example Analytics',
			'description'     => 'Traffic statistics.',
			'category'        => 'analytics',
			'enabled'         => true,
			'admin_fields'    => array(),
			'cookies'         => array( '_example' ),
			'render_callback' => 'my_example_consent_markup',
		);
		return $integrations;
	}
);
```

The render callback must output inert, consent-labelled markup. `aiv_consent_get_enabled_integrations()` exposes only configured active services. The existing `aiv-consent-ready` and `aiv-consent-change` events remain suitable for integrations that cannot use declarative markup.
