<?php
/**
 * Settings registration and fields.
 *
 * @package AIV_Consent
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_init', 'aiv_consent_register_settings' );

/**
 * Registers the structured option, sections and fields.
 *
 * @return void
 */
function aiv_consent_register_settings() {
	register_setting(
		'aiv_consent',
		'aiv_consent_options',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'aiv_consent_sanitize_options',
			'default'           => aiv_consent_get_default_options(),
		)
	);

	$sections = array(
		'general'  => __( 'Основные настройки', 'aiv-consent' ),
		'banner'   => __( 'Содержимое баннера', 'aiv-consent' ),
		'modal'    => __( 'Содержимое панели настроек', 'aiv-consent' ),
		'behavior' => __( 'Поведение', 'aiv-consent' ),
	);

	foreach ( $sections as $section => $title ) {
		add_settings_section( 'aiv_consent_' . $section, $title, '__return_false', 'aiv-consent' );
	}

	$fields = aiv_consent_get_settings_fields();

	foreach ( $fields as $key => $field ) {
		add_settings_field(
			'aiv_consent_' . $key,
			$field['label'],
			'aiv_consent_render_settings_field',
			'aiv-consent',
			'aiv_consent_' . $field['section'],
			array(
				'key'       => $key,
				'type'      => $field['type'],
				'label_for' => 'aiv-consent-' . str_replace( '_', '-', $key ),
			)
		);
	}
}

/**
 * Returns admin field definitions.
 *
 * @return array<string, array<string, string>>
 */
function aiv_consent_get_settings_fields() {
	return array(
		'enabled'                         => array(
			'section' => 'general',
			'type'    => 'checkbox',
			'label'   => __( 'Включить баннер согласия', 'aiv-consent' ),
		),
		'consent_version'                 => array(
			'section' => 'general',
			'type'    => 'text',
			'label'   => __( 'Версия согласия', 'aiv-consent' ),
		),
		'cookie_lifetime'                 => array(
			'section' => 'general',
			'type'    => 'number',
			'label'   => __( 'Срок хранения cookie (дни)', 'aiv-consent' ),
		),
		'cookie_policy_url'               => array(
			'section' => 'general',
			'type'    => 'url',
			'label'   => __( 'URL политики cookie', 'aiv-consent' ),
		),
		'privacy_policy_url'              => array(
			'section' => 'general',
			'type'    => 'url',
			'label'   => __( 'URL политики конфиденциальности', 'aiv-consent' ),
		),
		'banner_title'                    => array(
			'section' => 'banner',
			'type'    => 'text',
			'label'   => __( 'Заголовок', 'aiv-consent' ),
		),
		'banner_description'              => array(
			'section' => 'banner',
			'type'    => 'textarea',
			'label'   => __( 'Описание', 'aiv-consent' ),
		),
		'accept_all_label'                => array(
			'section' => 'banner',
			'type'    => 'text',
			'label'   => __( 'Кнопка «Принять все»', 'aiv-consent' ),
		),
		'reject_optional_label'           => array(
			'section' => 'banner',
			'type'    => 'text',
			'label'   => __( 'Кнопка «Отклонить необязательные»', 'aiv-consent' ),
		),
		'customize_label'                 => array(
			'section' => 'banner',
			'type'    => 'text',
			'label'   => __( 'Кнопка «Настроить»', 'aiv-consent' ),
		),
		'cookie_policy_label'             => array(
			'section' => 'banner',
			'type'    => 'text',
			'label'   => __( 'Текст ссылки политики cookie', 'aiv-consent' ),
		),
		'modal_title'                     => array(
			'section' => 'modal',
			'type'    => 'text',
			'label'   => __( 'Заголовок панели', 'aiv-consent' ),
		),
		'save_settings_label'             => array(
			'section' => 'modal',
			'type'    => 'text',
			'label'   => __( 'Кнопка сохранения', 'aiv-consent' ),
		),
		'necessary_description'           => array(
			'section' => 'modal',
			'type'    => 'textarea',
			'label'   => __( 'Описание необходимых cookie', 'aiv-consent' ),
		),
		'analytics_description'           => array(
			'section' => 'modal',
			'type'    => 'textarea',
			'label'   => __( 'Описание аналитических cookie', 'aiv-consent' ),
		),
		'marketing_description'           => array(
			'section' => 'modal',
			'type'    => 'textarea',
			'label'   => __( 'Описание маркетинговых cookie', 'aiv-consent' ),
		),
		'reprompt_on_version_change'      => array(
			'section' => 'behavior',
			'type'    => 'checkbox',
			'label'   => __( 'Повторно показать баннер при изменении версии', 'aiv-consent' ),
		),
		'reload_after_consent_revocation' => array(
			'section' => 'behavior',
			'type'    => 'checkbox',
			'label'   => __( 'Перезагрузить страницу после отзыва согласия', 'aiv-consent' ),
		),
	);
}

/**
 * Sanitizes the full plugin option.
 *
 * @param mixed $input Submitted value.
 * @return array<string, mixed>
 */
function aiv_consent_sanitize_options( $input ) {
	$defaults = aiv_consent_get_default_options();
	$input    = is_array( $input ) ? $input : array();
	$output   = $defaults;

	$checkboxes = array( 'enabled', 'reprompt_on_version_change', 'reload_after_consent_revocation' );
	$text       = array( 'consent_version', 'banner_title', 'accept_all_label', 'reject_optional_label', 'customize_label', 'cookie_policy_label', 'modal_title', 'save_settings_label' );
	$textareas  = array( 'banner_description', 'necessary_description', 'analytics_description', 'marketing_description' );

	foreach ( $checkboxes as $key ) {
		$output[ $key ] = isset( $input[ $key ] ) && '1' === (string) $input[ $key ];
	}

	foreach ( $text as $key ) {
		if ( isset( $input[ $key ] ) ) {
			$output[ $key ] = sanitize_text_field( $input[ $key ] );
		}
	}

	foreach ( $textareas as $key ) {
		if ( isset( $input[ $key ] ) ) {
			$output[ $key ] = sanitize_textarea_field( $input[ $key ] );
		}
	}

	$output['cookie_lifetime']    = isset( $input['cookie_lifetime'] ) ? min( 3650, max( 1, absint( $input['cookie_lifetime'] ) ) ) : $defaults['cookie_lifetime'];
	$output['cookie_policy_url']  = isset( $input['cookie_policy_url'] ) ? esc_url_raw( $input['cookie_policy_url'] ) : '';
	$output['privacy_policy_url'] = isset( $input['privacy_policy_url'] ) ? esc_url_raw( $input['privacy_policy_url'] ) : '';

	if ( '' === $output['consent_version'] ) {
		$output['consent_version'] = $defaults['consent_version'];
		add_settings_error( 'aiv_consent_options', 'aiv_consent_empty_version', __( 'Версия согласия не может быть пустой. Восстановлено значение по умолчанию.', 'aiv-consent' ) );
	}

	return $output;
}

/**
 * Renders a settings field.
 *
 * @param array<string, string> $args Field arguments.
 * @return void
 */
function aiv_consent_render_settings_field( $args ) {
	$options = aiv_consent_get_options();
	$key     = $args['key'];
	$type    = $args['type'];
	$id      = 'aiv-consent-' . str_replace( '_', '-', $key );
	$name    = 'aiv_consent_options[' . $key . ']';
	$value   = $options[ $key ];

	if ( 'checkbox' === $type ) {
		printf( '<label><input id="%1$s" name="%2$s" type="checkbox" value="1" %3$s> %4$s</label>', esc_attr( $id ), esc_attr( $name ), checked( true, (bool) $value, false ), esc_html__( 'Включено', 'aiv-consent' ) );
		return;
	}

	if ( 'textarea' === $type ) {
		printf( '<textarea id="%1$s" name="%2$s" class="large-text" rows="4">%3$s</textarea>', esc_attr( $id ), esc_attr( $name ), esc_textarea( $value ) );
		return;
	}

	$attributes = 'number' === $type ? ' min="1" max="3650" step="1"' : '';
	printf( '<input id="%1$s" name="%2$s" type="%3$s" value="%4$s" class="regular-text"%5$s>', esc_attr( $id ), esc_attr( $name ), esc_attr( $type ), esc_attr( $value ), $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static, controlled attributes.

	if ( 'privacy_policy_url' === $key ) {
		echo '<p class="description">' . esc_html__( 'Если поле пустое, используется страница политики конфиденциальности WordPress.', 'aiv-consent' ) . '</p>';
	}
}
