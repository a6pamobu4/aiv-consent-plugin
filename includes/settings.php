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
		'general'    => __( 'Основные настройки', 'aiv-consent' ),
		'banner'     => __( 'Содержимое баннера', 'aiv-consent' ),
		'modal'      => __( 'Содержимое панели настроек', 'aiv-consent' ),
		'appearance' => __( 'Внешний вид', 'aiv-consent' ),
		'behavior'   => __( 'Поведение', 'aiv-consent' ),
	);

	foreach ( $sections as $section => $title ) {
		$callback = 'appearance' === $section ? 'aiv_consent_render_appearance_section' : '__return_false';
		add_settings_section( 'aiv_consent_' . $section, $title, $callback, 'aiv-consent' );
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
				'min'       => $field['min'] ?? null,
				'max'       => $field['max'] ?? null,
				'step'      => $field['step'] ?? null,
				'suffix'    => $field['suffix'] ?? '',
				'choices'   => $field['choices'] ?? array(),
			)
		);
	}
}

/**
 * Returns admin field definitions.
 *
 * @return array<string, array<string, mixed>>
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
			'min'     => 1,
			'max'     => 3650,
			'step'    => 1,
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
		'style_background'                => array(
			'section' => 'appearance',
			'type'    => 'color',
			'label'   => __( 'Фон окна', 'aiv-consent' ),
		),
		'style_color'                     => array(
			'section' => 'appearance',
			'type'    => 'color',
			'label'   => __( 'Основной текст', 'aiv-consent' ),
		),
		'style_muted_color'               => array(
			'section' => 'appearance',
			'type'    => 'color',
			'label'   => __( 'Вторичный текст', 'aiv-consent' ),
		),
		'style_border_color'              => array(
			'section' => 'appearance',
			'type'    => 'color',
			'label'   => __( 'Рамки', 'aiv-consent' ),
		),
		'style_accent'                    => array(
			'section' => 'appearance',
			'type'    => 'color',
			'label'   => __( 'Акцент и ссылки', 'aiv-consent' ),
		),
		'style_button_background'         => array(
			'section' => 'appearance',
			'type'    => 'color',
			'label'   => __( 'Фон основной кнопки', 'aiv-consent' ),
		),
		'style_button_color'              => array(
			'section' => 'appearance',
			'type'    => 'color',
			'label'   => __( 'Текст основной кнопки', 'aiv-consent' ),
		),
		'style_font_family'               => array(
			'section' => 'appearance',
			'type'    => 'select',
			'label'   => __( 'Шрифт', 'aiv-consent' ),
			'choices' => aiv_consent_get_font_choices(),
		),
		'style_font_size'                 => array(
			'section' => 'appearance',
			'type'    => 'number',
			'label'   => __( 'Базовый размер текста', 'aiv-consent' ),
			'min'     => 12,
			'max'     => 24,
			'step'    => 1,
			'suffix'  => 'px',
		),
		'style_radius'                    => array(
			'section' => 'appearance',
			'type'    => 'number',
			'label'   => __( 'Скругление углов', 'aiv-consent' ),
			'min'     => 0,
			'max'     => 32,
			'step'    => 1,
			'suffix'  => 'px',
		),
		'style_max_width'                 => array(
			'section' => 'appearance',
			'type'    => 'number',
			'label'   => __( 'Максимальная ширина баннера', 'aiv-consent' ),
			'min'     => 320,
			'max'     => 1600,
			'step'    => 1,
			'suffix'  => 'px',
		),
		'style_shadow'                    => array(
			'section' => 'appearance',
			'type'    => 'select',
			'label'   => __( 'Тень', 'aiv-consent' ),
			'choices' => aiv_consent_get_shadow_choices(),
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
	$colors     = array( 'style_background', 'style_color', 'style_muted_color', 'style_border_color', 'style_accent', 'style_button_background', 'style_button_color' );

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

	foreach ( $colors as $key ) {
		$color          = isset( $input[ $key ] ) && is_string( $input[ $key ] ) ? sanitize_hex_color( $input[ $key ] ) : '';
		$output[ $key ] = $color ? $color : $defaults[ $key ];
	}

	$output['cookie_lifetime']    = isset( $input['cookie_lifetime'] ) ? min( 3650, max( 1, absint( $input['cookie_lifetime'] ) ) ) : $defaults['cookie_lifetime'];
	$output['cookie_policy_url']  = isset( $input['cookie_policy_url'] ) ? esc_url_raw( $input['cookie_policy_url'] ) : '';
	$output['privacy_policy_url'] = isset( $input['privacy_policy_url'] ) ? esc_url_raw( $input['privacy_policy_url'] ) : '';
	$output['style_font_size']    = isset( $input['style_font_size'] ) && is_scalar( $input['style_font_size'] ) ? min( 24, max( 12, absint( $input['style_font_size'] ) ) ) : $defaults['style_font_size'];
	$output['style_radius']       = isset( $input['style_radius'] ) && is_scalar( $input['style_radius'] ) ? min( 32, absint( $input['style_radius'] ) ) : $defaults['style_radius'];
	$output['style_max_width']    = isset( $input['style_max_width'] ) && is_scalar( $input['style_max_width'] ) ? min( 1600, max( 320, absint( $input['style_max_width'] ) ) ) : $defaults['style_max_width'];

	$font_choices                = aiv_consent_get_font_choices();
	$shadow_choices              = aiv_consent_get_shadow_choices();
	$output['style_font_family'] = isset( $input['style_font_family'] ) && is_string( $input['style_font_family'] ) && isset( $font_choices[ $input['style_font_family'] ] ) ? $input['style_font_family'] : $defaults['style_font_family'];
	$output['style_shadow']      = isset( $input['style_shadow'] ) && is_string( $input['style_shadow'] ) && isset( $shadow_choices[ $input['style_shadow'] ] ) ? $input['style_shadow'] : $defaults['style_shadow'];

	if ( '' === $output['consent_version'] ) {
		$output['consent_version'] = $defaults['consent_version'];
		add_settings_error( 'aiv_consent_options', 'aiv_consent_empty_version', __( 'Версия согласия не может быть пустой. Восстановлено значение по умолчанию.', 'aiv-consent' ) );
	}

	/**
	 * Filters sanitized settings so integrations can sanitize their own fields.
	 *
	 * @param array<string, mixed> $output   Sanitized options.
	 * @param array<string, mixed> $input    Submitted options.
	 * @param array<string, mixed> $defaults Default options.
	 */
	return apply_filters( 'aiv_consent_sanitized_options', $output, $input, $defaults );
}

/**
 * Explains how appearance settings interact with theme overrides.
 *
 * @return void
 */
function aiv_consent_render_appearance_section() {
	echo '<p>' . esc_html__( 'Настройки применяются к баннеру и панели предпочтений. Используются только локальные системные шрифты; тема по-прежнему может переопределить CSS-переменные.', 'aiv-consent' ) . '</p>';
}

/**
 * Renders a settings field.
 *
 * @param array<string, mixed> $args Field arguments.
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

	if ( 'color' === $type ) {
		printf( '<input id="%1$s" name="%2$s" type="color" value="%3$s" class="aiv-consent-color-field"> <code class="aiv-consent-color-value">%3$s</code>', esc_attr( $id ), esc_attr( $name ), esc_attr( $value ) );
		return;
	}

	if ( 'select' === $type ) {
		printf( '<select id="%1$s" name="%2$s">', esc_attr( $id ), esc_attr( $name ) );
		foreach ( $args['choices'] as $choice_key => $choice ) {
			printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $choice_key ), selected( $value, $choice_key, false ), esc_html( $choice['label'] ) );
		}
		echo '</select>';
		return;
	}

	$attributes = '';
	if ( 'number' === $type ) {
		$attributes = sprintf( ' min="%1$s" max="%2$s" step="%3$s"', esc_attr( $args['min'] ), esc_attr( $args['max'] ), esc_attr( $args['step'] ) );
	}
	printf( '<input id="%1$s" name="%2$s" type="%3$s" value="%4$s" class="regular-text"%5$s>', esc_attr( $id ), esc_attr( $name ), esc_attr( $type ), esc_attr( $value ), $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static, controlled attributes.
	if ( '' !== $args['suffix'] ) {
		printf( ' <span>%s</span>', esc_html( $args['suffix'] ) );
	}

	if ( 'privacy_policy_url' === $key ) {
		echo '<p class="description">' . esc_html__( 'Если поле пустое, используется страница политики конфиденциальности WordPress.', 'aiv-consent' ) . '</p>';
	}
}
