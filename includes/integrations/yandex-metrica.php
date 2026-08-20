<?php
/**
 * Yandex Metrica integration.
 *
 * @package AIV_Consent
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'aiv_consent_default_options', 'aiv_consent_yandex_metrica_defaults' );
add_filter( 'aiv_consent_integrations', 'aiv_consent_register_yandex_metrica' );
add_filter( 'aiv_consent_sanitized_options', 'aiv_consent_sanitize_yandex_metrica', 10, 3 );

/**
 * Adds defaults.
 *
 * @param array<string, mixed> $defaults Defaults.
 * @return array<string, mixed>
 */
function aiv_consent_yandex_metrica_defaults( $defaults ) {
	return array_merge(
		$defaults,
		array(
			'yandex_metrica_enabled'               => false,
			'yandex_metrica_id'                    => '',
			'yandex_metrica_webvisor'              => false,
			'yandex_metrica_clickmap'              => true,
			'yandex_metrica_track_links'           => true,
			'yandex_metrica_accurate_track_bounce' => true,
		)
	);
}

/**
 * Registers the integration definition.
 *
 * @param array<string, array<string, mixed>> $integrations Integrations.
 * @return array<string, array<string, mixed>>
 */
function aiv_consent_register_yandex_metrica( $integrations ) {
	$integrations['yandex_metrica'] = array(
		'label'            => __( 'Яндекс Метрика', 'aiv-consent' ),
		'description'      => __( 'Аналитика посещений сайта от Яндекса.', 'aiv-consent' ),
		'category'         => 'analytics',
		'enabled_option'   => 'yandex_metrica_enabled',
		'is_enabled'       => 'aiv_consent_yandex_metrica_is_enabled',
		'render_callback'  => 'aiv_consent_render_yandex_metrica',
		'section_callback' => 'aiv_consent_yandex_metrica_notice',
		'cookies'          => array(
			'_ym_metrika_enabled',
			'_ym_isad',
			'_ym_uid',
			'_ym_fa',
			'_ym_d',
			'_ym_ucs',
			'_ym_debug',
			'_ym_hostIndex',
			'_ym_sup_debug',
			array(
				'type'  => 'prefix',
				'value' => '_ym_visorc_',
			),
		),
		'fields'           => array(
			'yandex_metrica_enabled'               => array(
				'type'  => 'checkbox',
				'label' => __( 'Включить Яндекс Метрику', 'aiv-consent' ),
			),
			'yandex_metrica_id'                    => array(
				'type'        => 'text',
				'label'       => __( 'Номер счётчика', 'aiv-consent' ),
				'placeholder' => '12345678',
				'description' => __( 'Только цифры.', 'aiv-consent' ),
			),
			'yandex_metrica_webvisor'              => array(
				'type'  => 'checkbox',
				'label' => __( 'Вебвизор', 'aiv-consent' ),
			),
			'yandex_metrica_clickmap'              => array(
				'type'  => 'checkbox',
				'label' => __( 'Карта кликов', 'aiv-consent' ),
			),
			'yandex_metrica_track_links'           => array(
				'type'  => 'checkbox',
				'label' => __( 'Отслеживать внешние ссылки', 'aiv-consent' ),
			),
			'yandex_metrica_accurate_track_bounce' => array(
				'type'  => 'checkbox',
				'label' => __( 'Точный показатель отказов', 'aiv-consent' ),
			),
		),
	);

	return $integrations;
}

/**
 * Returns whether Metrica has a usable configuration.
 *
 * @param array<string, mixed> $options Options.
 * @return bool
 */
function aiv_consent_yandex_metrica_is_enabled( $options ) {
	return ! empty( $options['yandex_metrica_enabled'] ) && ! empty( $options['yandex_metrica_id'] ) && ctype_digit( (string) $options['yandex_metrica_id'] );
}

/**
 * Sanitizes Metrica fields.
 *
 * @param array<string, mixed> $output   Sanitized options.
 * @param array<string, mixed> $input    Submitted options.
 * @param array<string, mixed> $defaults Defaults.
 * @return array<string, mixed>
 */
function aiv_consent_sanitize_yandex_metrica( $output, $input, $defaults ) {
	$checkboxes = array( 'yandex_metrica_enabled', 'yandex_metrica_webvisor', 'yandex_metrica_clickmap', 'yandex_metrica_track_links', 'yandex_metrica_accurate_track_bounce' );

	foreach ( $checkboxes as $key ) {
		$output[ $key ] = isset( $input[ $key ] ) && '1' === (string) $input[ $key ];
	}

	$id                          = isset( $input['yandex_metrica_id'] ) ? preg_replace( '/\D+/', '', (string) $input['yandex_metrica_id'] ) : '';
	$output['yandex_metrica_id'] = is_string( $id ) ? $id : $defaults['yandex_metrica_id'];

	return $output;
}

/**
 * Shows a privacy reminder in settings.
 *
 * @return void
 */
function aiv_consent_yandex_metrica_notice() {
	echo '<p>' . esc_html__( 'Для проектов, где это применимо, проверьте настройки конфиденциальности счётчика в Яндекс Метрике, включая опцию «Не сохранять полные IP-адреса посетителей». Эта настройка сама по себе не гарантирует соблюдение требований законодательства.', 'aiv-consent' ) . '</p>';
	echo '<p><a href="https://yandex.ru/support/metrica/general/confidential-data.html" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Требования Яндекса к конфиденциальности', 'aiv-consent' ) . '<span class="screen-reader-text"> ' . esc_html__( '(откроется в новой вкладке)', 'aiv-consent' ) . '</span></a></p>';
}

/**
 * Renders the official queue bootstrap as inert inline JavaScript.
 *
 * @return void
 */
function aiv_consent_render_yandex_metrica() {
	$options = aiv_consent_get_options();
	$id      = (int) $options['yandex_metrica_id'];
	$init    = array(
		'clickmap'            => ! empty( $options['yandex_metrica_clickmap'] ),
		'trackLinks'          => ! empty( $options['yandex_metrica_track_links'] ),
		'accurateTrackBounce' => ! empty( $options['yandex_metrica_accurate_track_bounce'] ),
		'webvisor'            => ! empty( $options['yandex_metrica_webvisor'] ),
	);
	$code    = '(function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};m[i].l=1*new Date();for(var j=0;j<document.scripts.length;j++){if(document.scripts[j].src===r){return;}}k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})(window,document,"script","https://mc.yandex.ru/metrika/tag.js","ym");ym(' . $id . ',"init",' . wp_json_encode( $init ) . ');';

	printf( '<script type="text/plain" data-aiv-consent="analytics">%s</script>', $code ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Generated only from validated values and JSON.
}
