# AIV Consent

A lightweight WordPress plugin that provides technical infrastructure for managing consent for optional cookies and scripts. It uses no frontend framework, external library, CDN, service, or server-side visitor log.

## Requirements

- PHP 8.1+
- WordPress 7.0+
- Node.js/npm only for SCSS development
- Composer only for development linting

The repository is named `aiv-consent-plugin`; distributable plugin builds should use the folder slug `aiv-consent`.

## Architecture

`aiv-consent.php` defines constants and loads `includes/bootstrap.php`. Procedural modules separate defaults/helpers, category registration, cookie validation, Settings API fields, integrations, admin UI, frontend rendering, and conservative WordPress script-handle filtering. Provider modules live in `includes/integrations/`. `assets/js/consent.js` is the single frontend runtime, and SCSS compiles into one frontend and one admin stylesheet.

Configuration is stored in the single `aiv_consent_options` option. Visitor decisions are stored only in the first-party `aiv_consent` browser cookie. No custom tables, IP addresses, fingerprints, browser identifiers, or individual server-side consent records are created.

## Categories and first visit

Defaults are:

- `necessary`: always true and cannot be disabled;
- `analytics`: false until explicit consent;
- `marketing`: false until explicit consent.

Extend categories with `aiv_consent_categories`. A category contains `label`, `description`, and `required`. The plugin always restores `necessary` as required.

On a fresh visit, the fixed neutral banner offers Accept all, Reject optional, and Customize. It is hidden only after a valid explicit decision. The modal uses a real form control for every optional category and includes keyboard focus management.

## Settings

Go to **Settings > AIV Consent**. The WordPress Settings API registers and sanitizes:

- enabled state, consent version, cookie lifetime (default 180 days);
- cookie and privacy policy URLs;
- banner title, description, action labels, and cookie-policy link label;
- modal title, save label, and default category descriptions;
- window colors, system font, text size, corner radius, maximum width, and shadow;
- re-prompt after version changes and reload after revocation.

When no custom privacy URL exists, the configured WordPress Privacy Policy URL is used. An empty cookie policy URL produces no broken link.
Policy links open in a new browser tab and include safe `noopener noreferrer` relationship attributes.

### Integrations

The **Integrations** settings group includes built-in Yandex Metrica and Google Analytics 4 modules. Both are disabled by default. Entering a valid provider ID is sufficient; vendor snippets do not need to be pasted. Enabled services are listed compactly under their category in the visitor preferences panel.

Yandex Metrica uses its queue/bootstrap initialization model and emits no tracking image fallback. Google Analytics uses strict prior blocking: Google Consent Mode and cookieless pings are not enabled. The Russian-language GA4 notice is informational; consent alone does not resolve data localization or cross-border-transfer obligations.

Additional integrations accept a name, optional consent category, HTTP(S) script URL, inline JavaScript source, and `normal`, `async`, or `defer` strategy. Arbitrary integrations can never use the necessary category. Only users with `unfiltered_html` can create or change inline source; it is trusted executable code and must be reviewed like a plugin change. The field rejects `<script>` tags and does not execute code in wp-admin.

## Consent cookie

The `aiv_consent` cookie uses `Path=/`, `SameSite=Lax`, an administrator-controlled expiration, and `Secure` on HTTPS. It stores compact JSON:

```json
{"v":"1.0","t":1234567890,"c":{"necessary":true,"analytics":false,"marketing":false}}
```

`v`, `t`, and `c` mean version, Unix timestamp, and categories. Both PHP and JavaScript validate the structure and known category keys. When version re-prompting is enabled, a mismatched version makes the decision stale and shows the banner again.

## Blocking optional scripts

The recommended declarative integration makes a script inert before consent:

```html
<script type="text/plain" data-aiv-consent="analytics" data-src="https://example.com/analytics.js"></script>
<script type="text/plain" data-aiv-consent="analytics">
  window.exampleAnalyticsInit();
</script>
```

The browser does not request `data-src`. After consent, AIV Consent creates a real script, copies a conservative set of safe attributes, and replaces the inert node. Inline code is copied to a real script without `eval()` or `new Function()`. Eligible scripts are processed in document order and are not activated twice.

See [docs/integrations.md](docs/integrations.md) for Yandex Metrika, generic analytics, marketing pixel, module, cookie cleanup, and WordPress handle examples.

## WordPress handles

Map only explicitly optional, self-contained handles:

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

Mapped external tags always become inert through `script_loader_tag`, even when the PHP request contains a consent cookie; unrelated tags are untouched. This makes optional-script HTML safe to reuse from WP Rocket, nginx, reverse-proxy, or CDN full-page caches. The browser validates its own consent cookie before activation. Do not map WordPress core, functional/checkout scripts, dependency providers, or handles with required inline `before`/`after` data. Declarative markup is safer for complex dependency chains.

## JavaScript API

```js
AIVConsent.hasConsent( 'analytics' );
AIVConsent.getState();
AIVConsent.openSettings();
AIVConsent.acceptAll();
AIVConsent.rejectOptional();
```

The document dispatches `aiv-consent-ready` after local state is loaded and `aiv-consent-change` after a decision changes. `event.detail.categories` contains the current booleans.

## PHP API and reopening settings

Public functions:

- `aiv_consent_get_options()`
- `aiv_consent_get_categories()`
- `aiv_consent_get_cookie_name()`
- `aiv_consent_get_current_state()`
- `aiv_consent_has_category( $category )`
- `aiv_consent_get_settings_button( $label )`
- `aiv_consent_register_integration( $id, $definition )`
- `aiv_consent_get_integrations()`
- `aiv_consent_get_enabled_integrations()`

Use `[aiv_consent_settings]` in content or `[aiv_consent_settings label="Cookie settings"]` for a custom label. Themes may call `AIVConsent.openSettings()` instead. No floating icon is forced onto the site.

## Revocation and cleanup

When an allowed optional category is revoked, the plugin saves the new state, attempts deletion of registered first-party cookies, and reloads by default. `aiv_consent_category_cookies` still accepts legacy exact-name strings and also accepts safe `{ type: "exact"|"prefix", value: "..." }` descriptors. Yandex Metrica and GA4 register their known first-party families. Reloading is necessary because already-running third-party JavaScript usually cannot be safely unloaded. Cleanup cannot reliably remove HttpOnly cookies, local storage, unknown paths/domains, or cookies owned by third-party domains.

## Theme customization

The **Appearance** settings provide safe controls for the banner and preferences panel. They use native color inputs, bounded numeric values, and a whitelist of local system fonts, so saving a design never generates an external font request or stores arbitrary CSS.

The plugin emits the saved values as CSS custom properties attached to its enqueued stylesheet. Themes can still override these properties near the plugin root:

```css
.aiv-consent-root {
  --aiv-consent-background: #fff;
  --aiv-consent-color: #1f2933;
  --aiv-consent-border-color: #cbd2d9;
  --aiv-consent-accent: #2457d6;
  --aiv-consent-button-background: #2457d6;
  --aiv-consent-button-color: #fff;
	--aiv-consent-font-family: inherit;
	--aiv-consent-font-size: 16px;
  --aiv-consent-radius: 0.5rem;
  --aiv-consent-max-width: 72rem;
}
```

SCSS in `assets/scss/` is the source of truth. Do not edit generated CSS.

## Accessibility and performance

The modal uses dialog semantics, labels/descriptions, real checkboxes, a focus trap, Escape handling, focus restoration, visible focus states, internal small-viewport scrolling, and reduced-motion handling. The frontend loads one local JS and one local CSS file only when enabled. State is read locally; there are no REST calls, polling loops, recurring timers, or third-party requests from the plugin itself.

## Development

```sh
composer install
composer run fix:php
composer run lint:php
npm install
npm run build
node --check assets/js/consent.js
node --check assets/js/admin.js
```

Use `npm run start` for Sass watch mode.

## Production checklist

- Review all labels, descriptions, policy links, categories, and legal documents.
- Increase the consent version when the decision materially changes.
- Confirm no optional request occurs before consent in the browser Network panel.
- Test the same cached HTML with and without a browser consent cookie; optional tags must remain `text/plain` in both responses.
- Test every mapped handle and dependency chain.
- Register known first-party cookies for best-effort revocation cleanup.
- Test fresh, accept, reject, customize, reload, stale-version, and revoke flows.
- Test keyboard-only use, reduced motion, and widths around 320, 375, 390, and 430 px.

## Known limitations

- There is no automatic scanner, classification, geolocation, IAB TCF, Google Consent Mode, or consent-history service.
- Scripts not marked or mapped by the site developer cannot be blocked.
- Dynamically injected inert scripts added after initialization require an integration-triggered consent event or page load; no mutation observer/polling is used.
- WordPress inline `before`/`after` script data is not safely intercepted by the external-tag filter.
- Already-running third-party code cannot be reliably unloaded; cleanup is best-effort.
- Server-side uninstall deletes only `aiv_consent_options`; it cannot delete visitor cookies from browsers that do not make another request.

The manual provider and cache checklist is in [docs/manual-testing.md](docs/manual-testing.md).

## Legal notice

AIV Consent provides technical consent-management infrastructure. Installing it does not automatically make a website compliant with GDPR, ePrivacy, or any other jurisdiction. Legal text, policies, categories, integrations, lawful bases, and final configuration remain the responsibility of the site operator and their qualified advisers.

## License

GPL-2.0-or-later.
