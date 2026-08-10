# AIV Consent development rules

- Use current WordPress Developer Resources as the source of truth.
- Follow WordPress Coding Standards and support PHP 8.1 or newer.
- Keep the main plugin file as a loader and procedural modules in `includes/`.
- Prefix every public PHP function and hook integration helper with `aiv_consent_`.
- Treat SCSS in `assets/scss/` as the source of truth; never edit compiled CSS manually.
- Run `npm run build` after SCSS changes.
- Use vanilla JavaScript. Do not add jQuery or third-party frontend dependencies.
- Keep frontend code performance-first, event-driven and accessibility-first.
- Use prefixed, kebab-case CSS classes without BEM underscores or camelCase.
- Keep public PHP/JavaScript APIs backward compatible whenever practical.
- Intercept only WordPress script handles explicitly mapped by developers.
- Never globally rewrite unrelated script tags or assume dependencies can be activated safely.
- Keep plugin-core styling neutral and theme-independent; expose CSS custom properties.
- Do not claim that the plugin automatically provides legal compliance.
- Run `composer run lint:php`, `npm run build`, and JavaScript syntax checks before delivery.

