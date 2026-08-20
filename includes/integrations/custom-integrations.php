<?php
/**
 * Repeatable custom script integrations.
 *
 * @package AIV_Consent
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'aiv_consent_default_options', 'aiv_consent_custom_integrations_defaults' );
add_filter( 'aiv_consent_integrations', 'aiv_consent_register_custom_integrations', 20 );
add_filter( 'aiv_consent_sanitized_options', 'aiv_consent_sanitize_custom_integrations', 20, 3 );

/**
 * Adds defaults.
 *
 * @param array<string, mixed> $defaults Defaults.
 * @return array<string, mixed>
 */
function aiv_consent_custom_integrations_defaults( $defaults ) {
	$defaults['custom_integrations'] = array();
	return $defaults;
}

/**
 * Registers the manager and each saved custom integration.
 *
 * @param array<string, array<string, mixed>> $integrations Integrations.
 * @return array<string, array<string, mixed>>
 */
function aiv_consent_register_custom_integrations( $integrations ) {
	$integrations['custom_integrations'] = array(
		'label'       => __( 'Пользовательские скрипты', 'aiv-consent' ),
		'description' => __( 'Дополнительные внешние или встроенные скрипты.', 'aiv-consent' ),
		'category'    => 'analytics',
		'enabled'     => false,
		'fields'      => array(
			'custom_integrations' => array(
				'label'           => __( 'Скрипты', 'aiv-consent' ),
				'render_callback' => 'aiv_consent_render_custom_integrations_field',
			),
		),
	);

	$options = aiv_consent_get_options();
	$rows    = isset( $options['custom_integrations'] ) && is_array( $options['custom_integrations'] ) ? $options['custom_integrations'] : array();

	foreach ( $rows as $index => $row ) {
		if ( ! is_array( $row ) || empty( $row['key'] ) || empty( $row['name'] ) || empty( $row['category'] ) ) {
			continue;
		}

		$id = 'custom_' . sanitize_key( $row['key'] );

		$integrations[ $id ] = array(
			'label'           => $row['name'],
			'description'     => __( 'Пользовательская интеграция.', 'aiv-consent' ),
			'category'        => $row['category'],
			'enabled'         => ! empty( $row['enabled'] ) && ( ! empty( $row['script_url'] ) || ! empty( $row['inline_code'] ) ),
			'cookies'         => array(),
			'render_callback' => 'aiv_consent_render_custom_integration',
			'custom_data'     => $row,
		);
	}

	return $integrations;
}

/**
 * Renders the repeatable settings control.
 *
 * @return void
 */
function aiv_consent_render_custom_integrations_field() {
	$options     = aiv_consent_get_options();
	$rows        = isset( $options['custom_integrations'] ) && is_array( $options['custom_integrations'] ) ? $options['custom_integrations'] : array();
	$can_edit_js = current_user_can( 'unfiltered_html' );
	?>
	<div class="aiv-consent-custom-integrations" data-aiv-custom-integrations>
		<div data-aiv-custom-list>
			<?php foreach ( $rows as $index => $row ) : ?>
				<?php aiv_consent_render_custom_integration_row( $row, (string) $index, $can_edit_js ); ?>
			<?php endforeach; ?>
		</div>
		<button type="button" class="button" data-aiv-custom-add><?php esc_html_e( 'Добавить интеграцию', 'aiv-consent' ); ?></button>
		<template data-aiv-custom-template>
			<?php aiv_consent_render_custom_integration_row( array(), '__INDEX__', $can_edit_js ); ?>
		</template>
		<?php if ( ! $can_edit_js ) : ?>
			<p class="description"><?php esc_html_e( 'Вашей роли недоступно изменение встроенного JavaScript. Сохранённый код показан только для чтения и будет сохранён без изменений.', 'aiv-consent' ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Renders one repeatable row.
 *
 * @param array<string, mixed> $row         Row data.
 * @param string               $index       Form index.
 * @param bool                 $can_edit_js Whether inline JS is editable.
 * @return void
 */
function aiv_consent_render_custom_integration_row( $row, $index, $can_edit_js ) {
	$defaults   = array(
		'key'           => '',
		'name'          => '',
		'enabled'       => false,
		'category'      => 'analytics',
		'script_url'    => '',
		'inline_code'   => '',
		'load_strategy' => 'defer',
	);
	$row        = array_merge( $defaults, is_array( $row ) ? $row : array() );
	$prefix     = 'aiv_consent_options[custom_integrations][' . $index . ']';
	$categories = aiv_consent_get_categories();
	?>
	<fieldset class="aiv-consent-custom-row" data-aiv-custom-row>
		<legend class="screen-reader-text"><?php esc_html_e( 'Пользовательская интеграция', 'aiv-consent' ); ?></legend>
		<input type="hidden" name="<?php echo esc_attr( $prefix . '[key]' ); ?>" value="<?php echo esc_attr( $row['key'] ); ?>">
		<p><label><?php esc_html_e( 'Название', 'aiv-consent' ); ?><br><input type="text" class="regular-text" name="<?php echo esc_attr( $prefix . '[name]' ); ?>" value="<?php echo esc_attr( $row['name'] ); ?>"></label></p>
		<p><label><input type="checkbox" name="<?php echo esc_attr( $prefix . '[enabled]' ); ?>" value="1" <?php checked( true, (bool) $row['enabled'] ); ?>> <?php esc_html_e( 'Включено', 'aiv-consent' ); ?></label></p>
		<p><label><?php esc_html_e( 'Категория согласия', 'aiv-consent' ); ?><br><select name="<?php echo esc_attr( $prefix . '[category]' ); ?>">
			<?php foreach ( $categories as $category_key => $category ) : ?>
				<?php if ( empty( $category['required'] ) ) : ?>
					<option value="<?php echo esc_attr( $category_key ); ?>" <?php selected( $row['category'], $category_key ); ?>><?php echo esc_html( $category['label'] ); ?></option>
				<?php endif; ?>
			<?php endforeach; ?>
		</select></label></p>
		<p><label><?php esc_html_e( 'URL скрипта', 'aiv-consent' ); ?><br><input type="url" class="large-text" name="<?php echo esc_attr( $prefix . '[script_url]' ); ?>" value="<?php echo esc_attr( $row['script_url'] ); ?>" placeholder="https://example.com/script.js"></label></p>
		<p><label><?php esc_html_e( 'Встроенный JavaScript (без тегов script)', 'aiv-consent' ); ?><br><textarea class="large-text code" rows="6" name="<?php echo esc_attr( $prefix . '[inline_code]' ); ?>"<?php echo $can_edit_js ? '' : ' readonly'; ?>><?php echo esc_textarea( $row['inline_code'] ); ?></textarea></label></p>
		<p><label><?php esc_html_e( 'Стратегия загрузки', 'aiv-consent' ); ?><br><select name="<?php echo esc_attr( $prefix . '[load_strategy]' ); ?>">
			<option value="normal" <?php selected( $row['load_strategy'], 'normal' ); ?>><?php esc_html_e( 'Обычная', 'aiv-consent' ); ?></option>
			<option value="async" <?php selected( $row['load_strategy'], 'async' ); ?>>async</option>
			<option value="defer" <?php selected( $row['load_strategy'], 'defer' ); ?>>defer</option>
		</select></label></p>
		<button type="button" class="button-link-delete" data-aiv-custom-remove><?php esc_html_e( 'Удалить интеграцию', 'aiv-consent' ); ?></button>
	</fieldset>
	<?php
}

/**
 * Sanitizes repeatable rows and protects inline JS by capability.
 *
 * @param array<string, mixed> $output Sanitized options.
 * @param array<string, mixed> $input  Submitted options.
 * @return array<string, mixed>
 */
function aiv_consent_sanitize_custom_integrations( $output, $input ) {
	$submitted  = isset( $input['custom_integrations'] ) && is_array( $input['custom_integrations'] ) ? $input['custom_integrations'] : array();
	$stored     = get_option( 'aiv_consent_options', array() );
	$stored     = is_array( $stored ) && isset( $stored['custom_integrations'] ) && is_array( $stored['custom_integrations'] ) ? $stored['custom_integrations'] : array();
	$old_by_key = array();

	foreach ( $stored as $old_row ) {
		if ( is_array( $old_row ) && ! empty( $old_row['key'] ) ) {
			$old_by_key[ sanitize_key( $old_row['key'] ) ] = $old_row;
		}
	}

	$rows       = array();
	$categories = aiv_consent_get_categories();
	$can_edit   = current_user_can( 'unfiltered_html' );

	foreach ( $submitted as $index => $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$name = isset( $row['name'] ) ? sanitize_text_field( $row['name'] ) : '';
		if ( '' === $name ) {
			continue;
		}

		$key = isset( $row['key'] ) ? sanitize_key( $row['key'] ) : '';
		if ( '' === $key ) {
			$key = substr( hash( 'sha256', $name . '|' . microtime() . '|' . $index ), 0, 12 );
		}

		$category = isset( $row['category'] ) ? sanitize_key( $row['category'] ) : '';
		if ( ! isset( $categories[ $category ] ) || ! empty( $categories[ $category ]['required'] ) ) {
			$category = 'analytics';
		}

		$url      = isset( $row['script_url'] ) ? esc_url_raw( $row['script_url'], array( 'http', 'https' ) ) : '';
		$strategy = isset( $row['load_strategy'] ) && in_array( $row['load_strategy'], array( 'normal', 'async', 'defer' ), true ) ? $row['load_strategy'] : 'defer';
		$inline   = isset( $old_by_key[ $key ]['inline_code'] ) ? (string) $old_by_key[ $key ]['inline_code'] : '';

		if ( $can_edit ) {
			$candidate = isset( $row['inline_code'] ) ? wp_unslash( (string) $row['inline_code'] ) : '';
			if ( preg_match( '/<\/?script\b/i', $candidate ) ) {
				add_settings_error( 'aiv_consent_options', 'aiv_consent_custom_script_tag_' . $key, __( 'Теги <script> во встроенном JavaScript запрещены. Предыдущее значение сохранено.', 'aiv-consent' ) );
			} else {
				$inline = $candidate;
			}
		}

		$rows[] = array(
			'key'           => $key,
			'name'          => $name,
			'enabled'       => isset( $row['enabled'] ) && '1' === (string) $row['enabled'],
			'category'      => $category,
			'script_url'    => $url,
			'inline_code'   => $inline,
			'load_strategy' => $strategy,
		);
	}

	$output['custom_integrations'] = $rows;
	return $output;
}

/**
 * Renders one custom integration as inert markup.
 *
 * @param array<string, mixed> $integration Integration definition.
 * @return void
 */
function aiv_consent_render_custom_integration( $integration ) {
	$row       = $integration['custom_data'];
	$category  = $integration['category'];
	$strategy  = isset( $row['load_strategy'] ) && in_array( $row['load_strategy'], array( 'normal', 'async', 'defer' ), true ) ? $row['load_strategy'] : 'defer';
	$attribute = 'normal' === $strategy ? '' : ' ' . $strategy;

	if ( ! empty( $row['script_url'] ) ) {
		printf( '<script type="text/plain" data-aiv-consent="%1$s" data-src="%2$s"%3$s></script>', esc_attr( $category ), esc_url( $row['script_url'] ), esc_attr( $attribute ) ); // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- Deliberately inert consent markup, not an executable script.
	}

	if ( ! empty( $row['inline_code'] ) ) {
		printf( '<script type="text/plain" data-aiv-consent="%1$s">%2$s</script>', esc_attr( $category ), $row['inline_code'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Stored only for users with unfiltered_html; script tags are rejected.
	}
}
