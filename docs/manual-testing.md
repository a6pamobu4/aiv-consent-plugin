# Manual integration test plan

Use a fresh Incognito profile and keep the browser Network panel open with cache disabled. Repeat the cache checks once with the page cache or CDN enabled.

## Yandex Metrica

1. Enable the module with a test counter ID.
2. Before consent, filter Network by `yandex`, `metrika`, and `mc.yandex`: expect zero requests.
3. Reject optional cookies and reload: expect zero requests.
4. Clear `aiv_consent`, accept all, and confirm `tag.js` loads once.
5. Confirm `typeof window.ym === 'function'` and no duplicate counter initialization.
6. Revoke analytics and verify the configured reload and best-effort removal of known `_ym_` first-party cookies.

## Google Analytics 4

1. Enable GA4 with a test `G-...` ID.
2. Before consent and after rejection, filter Network by `googletagmanager` and `google-analytics`: expect zero requests.
3. Accept analytics: expect one Google tag request and an existing `window.dataLayer`.
4. Reload with consent: expect one initialization, not duplicates.
5. Revoke analytics and inspect best-effort deletion of `_ga` and `_ga_*` first-party cookies.

## Custom integration

1. As a user with `unfiltered_html`, add “Test Analytics”, category `analytics`, and a harmless test script URL.
2. Reject analytics: the URL must not load. Accept analytics: it must load once.
3. Add inline initialization without `<script>` tags and verify it runs only after consent.
4. Try saving source containing a `<script>` tag: expect an admin error and preservation of the previous source.
5. As a user without `unfiltered_html`, verify the source is read-only and a forged change is not saved.
6. Revoke analytics and verify reload behavior and no subsequent activation.

## Full-page cache safety

1. Map a harmless WordPress handle through `aiv_consent_script_categories`.
2. Prime WP Rocket, nginx, reverse-proxy, or CDN HTML while the browser has analytics consent.
3. Fetch the same cached page from a clean browser without consent.
4. In both HTML responses, confirm the mapped tag is `type="text/plain"`, uses `data-src`, and has no executable `src`.
5. Confirm no provider request occurs in the clean browser until analytics is accepted.

## Consent state and regression

1. Test accept all, reject optional, granular save, reload, reopening through `[aiv_consent_settings]`, and revocation.
2. Set an old timestamp beyond `cookie_lifetime`: PHP and JavaScript must both treat it as invalid.
3. Test version re-prompt behavior and a timestamp more than five minutes in the future.
4. Verify `aiv-consent-ready`, `aiv-consent-change`, and all existing public JavaScript methods.
5. Verify keyboard focus trapping, Escape, focus restoration, and narrow viewport scrolling.
