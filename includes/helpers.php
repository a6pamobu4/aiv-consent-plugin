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
		'style_background'                => '#ffffff',
		'style_color'                     => '#1f2933',
		'style_muted_color'               => '#52606d',
		'style_border_color'              => '#cbd2d9',
		'style_accent'                    => '#2457d6',
		'style_button_background'         => '#2457d6',
		'style_button_color'              => '#ffffff',
		'style_font_family'               => 'inherit',
		'style_font_size'                 => 16,
		'style_radius'                    => 8,
		'style_max_width'                 => 1152,
		'style_shadow'                    => 'soft',
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

/**
 * Returns allowed system font families.
 *
 * Keys are stored in the database; CSS values are never accepted from users.
 *
 * @return array<string, array<string, string>>
 */
function aiv_consent_get_font_choices() {
	return array(
		'inherit' => array(
			'label' => __( 'Наследовать от темы', 'aiv-consent' ),
			'css'   => 'inherit',
		),
		'system'  => array(
			'label' => __( 'Системный интерфейс', 'aiv-consent' ),
			'css'   => 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
		),
		'arial'   => array(
			'label' => 'Arial',
			'css'   => 'Arial, sans-serif',
		),
		'verdana' => array(
			'label' => 'Verdana',
			'css'   => 'Verdana, sans-serif',
		),
		'georgia' => array(
			'label' => 'Georgia',
			'css'   => 'Georgia, serif',
		),
		'mono'    => array(
			'label' => __( 'Моноширинный', 'aiv-consent' ),
			'css'   => 'ui-monospace, "Cascadia Code", Consolas, monospace',
		),
	);
}

/**
 * Returns allowed shadow presets.
 *
 * @return array<string, array<string, string>>
 */
function aiv_consent_get_shadow_choices() {
	return array(
		'none'   => array(
			'label' => __( 'Без тени', 'aiv-consent' ),
			'css'   => 'none',
		),
		'soft'   => array(
			'label' => __( 'Мягкая', 'aiv-consent' ),
			'css'   => '0 0.75rem 2.5rem rgb(15 23 42 / 18%)',
		),
		'strong' => array(
			'label' => __( 'Выраженная', 'aiv-consent' ),
			'css'   => '0 1rem 3rem rgb(15 23 42 / 32%)',
		),
	);
}

/**
 * Builds safe CSS custom properties from appearance options.
 *
 * @return string
 */
function aiv_consent_get_appearance_css() {
	$options    = aiv_consent_get_options();
	$defaults   = aiv_consent_get_default_options();
	$fonts      = aiv_consent_get_font_choices();
	$shadows    = aiv_consent_get_shadow_choices();
	$color_map  = array(
		'--aiv-consent-background'        => 'style_background',
		'--aiv-consent-color'             => 'style_color',
		'--aiv-consent-muted-color'       => 'style_muted_color',
		'--aiv-consent-border-color'      => 'style_border_color',
		'--aiv-consent-accent'            => 'style_accent',
		'--aiv-consent-button-background' => 'style_button_background',
		'--aiv-consent-button-color'      => 'style_button_color',
	);
	$properties = array();

	foreach ( $color_map as $property => $option_key ) {
		$color        = is_string( $options[ $option_key ] ) ? sanitize_hex_color( $options[ $option_key ] ) : '';
		$properties[] = $property . ':' . ( $color ? $color : $defaults[ $option_key ] );
	}

	$font_key   = is_string( $options['style_font_family'] ) && isset( $fonts[ $options['style_font_family'] ] ) ? $options['style_font_family'] : $defaults['style_font_family'];
	$shadow_key = is_string( $options['style_shadow'] ) && isset( $shadows[ $options['style_shadow'] ] ) ? $options['style_shadow'] : $defaults['style_shadow'];
	$font_size  = is_scalar( $options['style_font_size'] ) ? min( 24, max( 12, absint( $options['style_font_size'] ) ) ) : $defaults['style_font_size'];
	$radius     = is_scalar( $options['style_radius'] ) ? min( 32, absint( $options['style_radius'] ) ) : $defaults['style_radius'];
	$max_width  = is_scalar( $options['style_max_width'] ) ? min( 1600, max( 320, absint( $options['style_max_width'] ) ) ) : $defaults['style_max_width'];

	$properties[] = '--aiv-consent-font-family:' . $fonts[ $font_key ]['css'];
	$properties[] = '--aiv-consent-font-size:' . $font_size . 'px';
	$properties[] = '--aiv-consent-radius:' . $radius . 'px';
	$properties[] = '--aiv-consent-max-width:' . $max_width . 'px';
	$properties[] = '--aiv-consent-shadow:' . $shadows[ $shadow_key ]['css'];

	return '.aiv-consent-root{' . implode( ';', $properties ) . '}';
}
