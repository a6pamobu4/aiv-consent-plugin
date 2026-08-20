<?php
/**
 * Google Analytics 4 integration.
 *
 * @package AIV_Consent
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'aiv_consent_default_options', 'aiv_consent_google_analytics_defaults' );
add_filter( 'aiv_consent_integrations', 'aiv_consent_register_google_analytics' );
add_filter( 'aiv_consent_sanitized_options', 'aiv_consent_sanitize_google_analytics', 10, 3 );

/**
 * Adds defaults.
 *
 * @param array<string, mixed> $defaults Defaults.
 * @return array<string, mixed>
 */
function aiv_consent_google_analytics_defaults( $defaults ) {
	$defaults['google_analytics_enabled']        = false;
	$defaults['google_analytics_measurement_id'] = '';
	return $defaults;
}

/**
 * Registers Google Analytics.
 *
 * @param array<string, array<string, mixed>> $integrations Integrations.
 * @return array<string, array<string, mixed>>
 */
function aiv_consent_register_google_analytics( $integrations ) {
	$integrations['google_analytics'] = array(
		'label'            => __( 'Google Analytics 4', 'aiv-consent' ),
		'description'      => __( 'Аналитика посещений сайта от Google.', 'aiv-consent' ),
		'category'         => 'analytics',
		'enabled_option'   => 'google_analytics_enabled',
		'is_enabled'       => 'aiv_consent_google_analytics_is_enabled',
		'render_callback'  => 'aiv_consent_render_google_analytics',
		'section_callback' => 'aiv_consent_google_analytics_notice',
		'cookies'          => array(
			'_ga',
			array(
				'type'  => 'prefix',
				'value' => '_ga_',
			),
		),
		'fields'           => array(
			'google_analytics_enabled'        => array(
				'type'  => 'checkbox',
				'label' => __( 'Включить Google Analytics 4', 'aiv-consent' ),
			),
			'google_analytics_measurement_id' => array(
				'type'        => 'text',
				'label'       => __( 'Measurement ID', 'aiv-consent' ),
				'placeholder' => 'G-XXXXXXXXXX',
				'description' => __( 'Идентификатор должен начинаться с G-.', 'aiv-consent' ),
			),
		),
	);

	return $integrations;
}

/**
 * Validates enabled configuration.
 *
 * @param array<string, mixed> $options Options.
 * @return bool
 */
function aiv_consent_google_analytics_is_enabled( $options ) {
	return ! empty( $options['google_analytics_enabled'] ) && isset( $options['google_analytics_measurement_id'] ) && 1 === preg_match( '/^G-[A-Z0-9]+$/', (string) $options['google_analytics_measurement_id'] );
}

/**
 * Sanitizes Google Analytics fields.
 *
 * @param array<string, mixed> $output Sanitized options.
 * @param array<string, mixed> $input  Submitted options.
 * @return array<string, mixed>
 */
function aiv_consent_sanitize_google_analytics( $output, $input ) {
	$output['google_analytics_enabled']        = isset( $input['google_analytics_enabled'] ) && '1' === (string) $input['google_analytics_enabled'];
	$id                                        = isset( $input['google_analytics_measurement_id'] ) ? strtoupper( sanitize_text_field( $input['google_analytics_measurement_id'] ) ) : '';
	$output['google_analytics_measurement_id'] = 1 === preg_match( '/^G-[A-Z0-9]+$/', $id ) ? $id : '';

	return $output;
}

/**
 * Shows a legal/privacy reminder.
 *
 * @return void
 */
function aiv_consent_google_analytics_notice() {
	echo '<p>' . esc_html__( 'Google Analytics запускается только после согласия на аналитику, без Consent Mode и cookieless-запросов. Само согласие не заменяет правовую оценку: проверьте требования 152-ФЗ, локализацию и трансграничную передачу персональных данных.', 'aiv-consent' ) . '</p>';
	echo '<p><a href="https://support.google.com/analytics/answer/7318509" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Защита данных в Google Analytics', 'aiv-consent' ) . '<span class="screen-reader-text"> ' . esc_html__( '(откроется в новой вкладке)', 'aiv-consent' ) . '</span></a></p>';
}

/**
 * Renders inert gtag.js and initialization tags.
 *
 * @return void
 */
function aiv_consent_render_google_analytics() {
	$options = aiv_consent_get_options();
	$id      = (string) $options['google_analytics_measurement_id'];
	$src     = 'https://www.googletagmanager.com/gtag/js?id=' . rawurlencode( $id );
	$code    = 'window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag("js",new Date());gtag("config",' . wp_json_encode( $id ) . ');';

	printf( '<script type="text/plain" data-aiv-consent="analytics" data-src="%s" async></script>', esc_url( $src ) ); // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- Deliberately inert consent markup, not an executable script.
	printf( '<script type="text/plain" data-aiv-consent="analytics">%s</script>', $code ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Generated only from a validated measurement ID and JSON.
}
