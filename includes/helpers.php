<?php
/**
 * Shared helpers.
 *
 * @package AIV_Consent
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returns default plugin options.
 *
 * @return array<string, mixed>
 */
function aiv_consent_get_default_options() {
	return array(
		'enabled'                         => true,
		'consent_version'                 => '1.0',
		'cookie_lifetime'                 => 180,
		'cookie_policy_url'               => '',
		'privacy_policy_url'              => '',
		'banner_title'                    => __( 'Настройки cookie', 'aiv-consent' ),
		'banner_description'              => __( 'Мы используем необходимые cookie для работы сайта и, с вашего согласия, дополнительные cookie для аналитики и маркетинга. Вы можете принять все cookie, отклонить необязательные или изменить настройки.', 'aiv-consent' ),
		'accept_all_label'                => __( 'Принять все', 'aiv-consent' ),
		'reject_optional_label'           => __( 'Отклонить необязательные', 'aiv-consent' ),
		'customize_label'                 => __( 'Настроить', 'aiv-consent' ),
		'cookie_policy_label'             => __( 'Подробнее о cookie', 'aiv-consent' ),
		'modal_title'                     => __( 'Настройки cookie', 'aiv-consent' ),
		'save_settings_label'             => __( 'Сохранить настройки', 'aiv-consent' ),
		'necessary_description'           => __( 'Нужны для базовой работы сайта, безопасности и сохранения выбранных настроек.', 'aiv-consent' ),
		'analytics_description'           => __( 'Помогают понять, как посетители используют сайт и какие страницы можно улучшить.', 'aiv-consent' ),
		'marketing_description'           => __( 'Используются для рекламных кампаний, оценки их эффективности и связанных маркетинговых инструментов.', 'aiv-consent' ),
		'reprompt_on_version_change'      => true,
		'reload_after_consent_revocation' => true,
	);
}

/**
 * Returns normalized plugin options.
 *
 * @return array<string, mixed>
 */
function aiv_consent_get_options() {
	$saved = get_option( 'aiv_consent_options', array() );

	if ( ! is_array( $saved ) ) {
		$saved = array();
	}

	return array_merge( aiv_consent_get_default_options(), $saved );
}

/**
 * Returns the configured privacy policy URL or the WordPress default.
 *
 * @return string
 */
function aiv_consent_get_privacy_policy_url() {
	$options = aiv_consent_get_options();

	if ( ! empty( $options['privacy_policy_url'] ) ) {
		return (string) $options['privacy_policy_url'];
	}

	return get_privacy_policy_url();
}
