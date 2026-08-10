<?php
/**
 * Uninstall cleanup for AIV Consent.
 *
 * @package AIV_Consent
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'aiv_consent_options' );
