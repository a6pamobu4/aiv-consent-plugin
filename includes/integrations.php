<?php
/**
 * Consent-aware integration registry and shared rendering.
 *
 * @package AIV_Consent
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_init', 'aiv_consent_register_integration_settings', 20 );
add_action( 'wp_body_open', 'aiv_consent_render_integrations', 6 );
add_action( 'wp_footer', 'aiv_consent_render_integrations', 0 );
add_filter( 'aiv_consent_category_cookies', 'aiv_consent_add_integration_cookies' );

/**
 * Registers an integration programmatically.
 *
 * @param string               $id         Unique integration ID.
 * @param array<string, mixed> $definition Integration definition.
 * @return bool
 */
function aiv_consent_register_integration( $id, $definition ) {
	global $aiv_consent_registered_integrations;

	$id = sanitize_key( $id );

	if ( '' === $id || ! is_array( $definition ) ) {
		return false;
	}

	if ( ! is_array( $aiv_consent_registered_integrations ) ) {
		$aiv_consent_registered_integrations = array();
	}

	$definition['id']                           = $id;
	$aiv_consent_registered_integrations[ $id ] = $definition;

	return true;
}

/**
 * Returns all valid integration definitions.
 *
 * @return array<string, array<string, mixed>>
 */
function aiv_consent_get_integrations() {
	global $aiv_consent_registered_integrations;

	$registered = is_array( $aiv_consent_registered_integrations ) ? $aiv_consent_registered_integrations : array();

	/**
	 * Filters registered consent integrations.
	 *
	 * @param array<string, array<string, mixed>> $registered Integrations keyed by ID.
	 */
	$integrations = apply_filters( 'aiv_consent_integrations', $registered );
	$categories   = aiv_consent_get_categories();
	$validated    = array();

	if ( ! is_array( $integrations ) ) {
		return $validated;
	}

	foreach ( $integrations as $id => $integration ) {
		$id       = sanitize_key( $id );
		$category = is_array( $integration ) && isset( $integration['category'] ) ? sanitize_key( $integration['category'] ) : '';

		if ( '' === $id || ! is_array( $integration ) || ! isset( $categories[ $category ] ) || ! empty( $categories[ $category ]['required'] ) ) {
			continue;
		}

		$integration['id']           = $id;
		$integration['category']     = $category;
		$integration['label']        = isset( $integration['label'] ) ? sanitize_text_field( $integration['label'] ) : $id;
		$integration['description']  = isset( $integration['description'] ) ? sanitize_textarea_field( $integration['description'] ) : '';
		$integration['cookies']      = isset( $integration['cookies'] ) && is_array( $integration['cookies'] ) ? $integration['cookies'] : array();
		$admin_fields                = isset( $integration['admin_fields'] ) ? $integration['admin_fields'] : ( $integration['fields'] ?? array() );
		$integration['admin_fields'] = is_array( $admin_fields ) ? $admin_fields : array();
		$integration['fields']       = $integration['admin_fields'];
		$validated[ $id ]            = $integration;
	}

	return $validated;
}

/**
 * Returns configured and enabled integrations.
 *
 * @return array<string, array<string, mixed>>
 */
function aiv_consent_get_enabled_integrations() {
	$options = aiv_consent_get_options();
	$enabled = array();

	foreach ( aiv_consent_get_integrations() as $id => $integration ) {
		$is_enabled = false;

		if ( isset( $integration['is_enabled'] ) && is_callable( $integration['is_enabled'] ) ) {
			$is_enabled = (bool) call_user_func( $integration['is_enabled'], $options, $integration );
		} elseif ( isset( $integration['enabled_option'] ) ) {
			$is_enabled = ! empty( $options[ $integration['enabled_option'] ] );
		} elseif ( isset( $integration['enabled'] ) ) {
			$is_enabled = (bool) $integration['enabled'];
		}

		if ( $is_enabled ) {
			$enabled[ $id ] = $integration;
		}
	}

	return $enabled;
}

/**
 * Registers integration sections and fields on the existing settings page.
 *
 * @return void
 */
function aiv_consent_register_integration_settings() {
	add_settings_section(
		'aiv_consent_integrations',
		__( 'Интеграции', 'aiv-consent' ),
		'aiv_consent_render_integrations_section',
		'aiv-consent'
	);

	foreach ( aiv_consent_get_integrations() as $id => $integration ) {
		if ( empty( $integration['fields'] ) ) {
			continue;
		}

		$section_id = 'aiv_consent_integration_' . $id;
		$callback   = isset( $integration['section_callback'] ) && is_callable( $integration['section_callback'] ) ? $integration['section_callback'] : '__return_false';

		add_settings_section(
			$section_id,
			sprintf( /* translators: %s: integration name. */ __( 'Интеграции — %s', 'aiv-consent' ), $integration['label'] ),
			$callback,
			'aiv-consent'
		);

		foreach ( $integration['fields'] as $key => $field ) {
			if ( ! is_array( $field ) || empty( $field['label'] ) ) {
				continue;
			}

			add_settings_field(
				'aiv_consent_' . sanitize_key( $key ),
				$field['label'],
				'aiv_consent_render_integration_field',
				'aiv-consent',
				$section_id,
				array_merge( $field, array( 'key' => $key ) )
			);
		}
	}
}

/**
 * Introduces the integration settings group.
 *
 * @return void
 */
function aiv_consent_render_integrations_section() {
	echo '<p>' . esc_html__( 'Включённые сервисы запускаются только после согласия на соответствующую необязательную категорию.', 'aiv-consent' ) . '</p>';
}

/**
 * Renders a common integration field or delegates to a custom renderer.
 *
 * @param array<string, mixed> $args Field arguments.
 * @return void
 */
function aiv_consent_render_integration_field( $args ) {
	if ( isset( $args['render_callback'] ) && is_callable( $args['render_callback'] ) ) {
		call_user_func( $args['render_callback'], $args );
		return;
	}

	$options = aiv_consent_get_options();
	$key     = sanitize_key( $args['key'] );
	$type    = isset( $args['type'] ) ? $args['type'] : 'text';
	$value   = $options[ $key ] ?? '';
	$id      = 'aiv-consent-' . str_replace( '_', '-', $key );
	$name    = 'aiv_consent_options[' . $key . ']';

	if ( 'checkbox' === $type ) {
		printf( '<label><input id="%1$s" name="%2$s" type="checkbox" value="1" %3$s> %4$s</label>', esc_attr( $id ), esc_attr( $name ), checked( true, (bool) $value, false ), esc_html__( 'Включено', 'aiv-consent' ) );
	} else {
		printf( '<input id="%1$s" name="%2$s" type="text" value="%3$s" class="regular-text"%4$s>', esc_attr( $id ), esc_attr( $name ), esc_attr( $value ), isset( $args['placeholder'] ) ? ' placeholder="' . esc_attr( $args['placeholder'] ) . '"' : '' );
	}

	if ( ! empty( $args['description'] ) ) {
		printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
	}
}

/**
 * Outputs enabled integrations as inert script elements.
 *
 * @return void
 */
function aiv_consent_render_integrations() {
	static $rendered = false;

	if ( $rendered || ! aiv_consent_is_enabled() ) {
		return;
	}

	$rendered = true;

	foreach ( aiv_consent_get_enabled_integrations() as $integration ) {
		if ( isset( $integration['render_callback'] ) && is_callable( $integration['render_callback'] ) ) {
			call_user_func( $integration['render_callback'], $integration );
		}
	}
}

/**
 * Adds integration cleanup descriptors to the registry.
 *
 * @param array<string, array<int, mixed>> $registry Existing registry.
 * @return array<string, array<int, mixed>>
 */
function aiv_consent_add_integration_cookies( $registry ) {
	$registry = is_array( $registry ) ? $registry : array();

	foreach ( aiv_consent_get_integrations() as $integration ) {
		$category              = $integration['category'];
		$registry[ $category ] = array_merge( $registry[ $category ] ?? array(), $integration['cookies'] );
	}

	return $registry;
}

/**
 * Lists enabled services for a category inside the preferences dialog.
 *
 * @param string $category Category key.
 * @return void
 */
function aiv_consent_render_category_services( $category ) {
	$services = array();

	foreach ( aiv_consent_get_enabled_integrations() as $integration ) {
		if ( $category === $integration['category'] ) {
			$services[] = $integration;
		}
	}

	if ( empty( $services ) ) {
		return;
	}

	echo '<div class="aiv-consent-services"><strong>' . esc_html__( 'Используемые сервисы:', 'aiv-consent' ) . '</strong><ul>';
	foreach ( $services as $service ) {
		echo '<li><strong>' . esc_html( $service['label'] ) . '</strong>';
		if ( '' !== $service['description'] ) {
			echo '<span>' . esc_html( $service['description'] ) . '</span>';
		}
		echo '</li>';
	}
	echo '</ul></div>';
}
