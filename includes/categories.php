<?php
/**
 * Consent categories.
 *
 * @package AIV_Consent
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returns validated consent categories.
 *
 * @return array<string, array<string, mixed>>
 */
function aiv_consent_get_categories() {
	$options    = aiv_consent_get_options();
	$categories = array(
		'necessary' => array(
			'label'       => __( 'Необходимые', 'aiv-consent' ),
			'description' => $options['necessary_description'],
			'required'    => true,
		),
		'analytics' => array(
			'label'       => __( 'Аналитические', 'aiv-consent' ),
			'description' => $options['analytics_description'],
			'required'    => false,
		),
		'marketing' => array(
			'label'       => __( 'Маркетинговые', 'aiv-consent' ),
			'description' => $options['marketing_description'],
			'required'    => false,
		),
	);

	/**
	 * Filters the consent category registry.
	 *
	 * Each category must have a sanitized key plus label, description and required values.
	 * The necessary category is always restored as required.
	 *
	 * @param array<string, array<string, mixed>> $categories Consent categories.
	 */
	$filtered = apply_filters( 'aiv_consent_categories', $categories );

	if ( ! is_array( $filtered ) ) {
		$filtered = $categories;
	}

	$validated = array();

	foreach ( $filtered as $key => $category ) {
		$sanitized_key = sanitize_key( $key );

		if ( '' === $sanitized_key || ! is_array( $category ) ) {
			continue;
		}

		$validated[ $sanitized_key ] = array(
			'label'       => isset( $category['label'] ) ? sanitize_text_field( $category['label'] ) : $sanitized_key,
			'description' => isset( $category['description'] ) ? sanitize_textarea_field( $category['description'] ) : '',
			'required'    => ! empty( $category['required'] ),
		);
	}

	$validated['necessary'] = $categories['necessary'];

	return array( 'necessary' => $validated['necessary'] ) + $validated;
}
