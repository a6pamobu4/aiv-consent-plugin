<?php
/**
 * Plugin bootstrap.
 *
 * @package AIV_Consent
 */

defined( 'ABSPATH' ) || exit;

$aiv_consent_includes = array(
	'helpers.php',
	'categories.php',
	'consent.php',
	'settings.php',
	'admin.php',
	'frontend.php',
	'scripts.php',
);

foreach ( $aiv_consent_includes as $aiv_consent_include ) {
	require_once AIV_CONSENT_PATH . 'includes/' . $aiv_consent_include;
}

unset( $aiv_consent_include, $aiv_consent_includes );

add_action( 'init', 'aiv_consent_load_textdomain' );

/**
 * Loads plugin translations.
 *
 * @return void
 */
function aiv_consent_load_textdomain() {
	load_plugin_textdomain( 'aiv-consent', false, dirname( plugin_basename( AIV_CONSENT_FILE ) ) . '/languages' );
}

/**
 * Creates default options during activation without replacing saved settings.
 *
 * @return void
 */
function aiv_consent_activate() {
	if ( false === get_option( 'aiv_consent_options', false ) ) {
		add_option( 'aiv_consent_options', aiv_consent_get_default_options() );
	}
}
