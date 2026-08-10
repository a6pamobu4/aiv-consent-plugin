<?php
/**
 * Administration screen.
 *
 * @package AIV_Consent
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', 'aiv_consent_add_settings_page' );
add_action( 'admin_enqueue_scripts', 'aiv_consent_enqueue_admin_assets' );

/**
 * Adds Settings > AIV Consent.
 *
 * @return void
 */
function aiv_consent_add_settings_page() {
	add_options_page(
		__( 'AIV Consent', 'aiv-consent' ),
		__( 'AIV Consent', 'aiv-consent' ),
		'manage_options',
		'aiv-consent',
		'aiv_consent_render_settings_page'
	);
}

/**
 * Loads admin styles only on the plugin settings screen.
 *
 * @param string $hook_suffix Current admin screen hook.
 * @return void
 */
function aiv_consent_enqueue_admin_assets( $hook_suffix ) {
	if ( 'settings_page_aiv-consent' !== $hook_suffix ) {
		return;
	}

	wp_enqueue_style( 'aiv-consent-admin', AIV_CONSENT_URL . 'assets/css/admin.css', array(), AIV_CONSENT_VERSION );
	wp_enqueue_script( 'aiv-consent-admin', AIV_CONSENT_URL . 'assets/js/admin.js', array(), AIV_CONSENT_VERSION, true );
}

/**
 * Renders the settings page.
 *
 * @return void
 */
function aiv_consent_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap aiv-consent-admin">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<p><?php esc_html_e( 'Техническая инфраструктура управления согласием. Юридические тексты и итоговая настройка остаются ответственностью владельца сайта.', 'aiv-consent' ); ?></p>
		<?php settings_errors(); ?>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'aiv_consent' );
			do_settings_sections( 'aiv-consent' );
			submit_button();
			?>
		</form>
	</div>
	<?php
}

