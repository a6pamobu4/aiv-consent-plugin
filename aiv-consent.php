<?php
/**
 * Plugin Name:       AIV Consent
 * Plugin URI:        https://github.com/a6pamobu4/aiv-consent-plugin
 * Description:       Lightweight technical infrastructure for managing consent for optional cookies and scripts.
 * Version:           1.0.0
 * Requires at least: 7.0
 * Requires PHP:      8.1
 * Author:            AIV
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       aiv-consent
 * Domain Path:       /languages
 *
 * @package AIV_Consent
 */

defined( 'ABSPATH' ) || exit;

define( 'AIV_CONSENT_VERSION', '1.0.0' );
define( 'AIV_CONSENT_FILE', __FILE__ );
define( 'AIV_CONSENT_PATH', plugin_dir_path( __FILE__ ) );
define( 'AIV_CONSENT_URL', plugin_dir_url( __FILE__ ) );

require_once AIV_CONSENT_PATH . 'includes/bootstrap.php';

register_activation_hook( AIV_CONSENT_FILE, 'aiv_consent_activate' );
