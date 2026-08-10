<?php
/**
 * Consent state and cookie API.
 *
 * @package AIV_Consent
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returns the consent cookie name.
 *
 * @return string
 */
function aiv_consent_get_cookie_name() {
	return 'aiv_consent';
}

/**
 * Returns a safe default state for the known categories.
 *
 * @return array<string, mixed>
 */
function aiv_consent_get_default_state() {
	$options    = aiv_consent_get_options();
	$categories = array();

	foreach ( aiv_consent_get_categories() as $key => $category ) {
		$categories[ $key ] = ! empty( $category['required'] );
	}

	return array(
		'version'    => (string) $options['consent_version'],
		'timestamp'  => 0,
		'categories' => $categories,
		'valid'      => false,
	);
}

/**
 * Validates and normalizes decoded consent data.
 *
 * Compact cookie keys v, t and c are accepted alongside descriptive keys.
 *
 * @param mixed $data Decoded cookie data.
 * @return array<string, mixed>
 */
function aiv_consent_validate_state( $data ) {
	$default = aiv_consent_get_default_state();

	if ( ! is_array( $data ) ) {
		return $default;
	}

	$version    = isset( $data['v'] ) ? $data['v'] : ( $data['version'] ?? null );
	$timestamp  = isset( $data['t'] ) ? $data['t'] : ( $data['timestamp'] ?? null );
	$categories = isset( $data['c'] ) ? $data['c'] : ( $data['categories'] ?? null );

	if ( ! is_string( $version ) || ! is_numeric( $timestamp ) || (int) $timestamp <= 0 || ! is_array( $categories ) ) {
		return $default;
	}

	$options = aiv_consent_get_options();
	$version = sanitize_text_field( $version );

	if ( ! empty( $options['reprompt_on_version_change'] ) && ! hash_equals( (string) $options['consent_version'], $version ) ) {
		return $default;
	}

	$normalized = array();

	foreach ( aiv_consent_get_categories() as $key => $category ) {
		if ( ! empty( $category['required'] ) ) {
			$normalized[ $key ] = true;
			continue;
		}

		$normalized[ $key ] = isset( $categories[ $key ] ) && true === $categories[ $key ];
	}

	return array(
		'version'    => $version,
		'timestamp'  => (int) $timestamp,
		'categories' => $normalized,
		'valid'      => true,
	);
}

/**
 * Returns the consent state from the current request cookie.
 *
 * @return array<string, mixed>
 */
function aiv_consent_get_current_state() {
	$cookie_name = aiv_consent_get_cookie_name();

	if ( ! isset( $_COOKIE[ $cookie_name ] ) ) {
		return aiv_consent_get_default_state();
	}

	$raw     = rawurldecode( sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) ) );
	$decoded = json_decode( $raw, true, 8 );

	if ( JSON_ERROR_NONE !== json_last_error() ) {
		return aiv_consent_get_default_state();
	}

	return aiv_consent_validate_state( $decoded );
}

/**
 * Checks whether the current request has valid consent for a category.
 *
 * @param string $category Category key.
 * @return bool
 */
function aiv_consent_has_category( $category ) {
	$category = sanitize_key( $category );
	$state    = aiv_consent_get_current_state();

	return ! empty( $state['categories'][ $category ] );
}

/**
 * Returns the sanitized cookie cleanup registry.
 *
 * @return array<string, string[]>
 */
function aiv_consent_get_category_cookies() {
	/**
	 * Filters first-party cookie names to delete after category revocation.
	 *
	 * Exact cookie names are supported in version 1.
	 *
	 * @param array<string, string[]> $cookies Cookie registry by category.
	 */
	$registry = apply_filters( 'aiv_consent_category_cookies', array() );
	$result   = array();

	if ( ! is_array( $registry ) ) {
		return $result;
	}

	foreach ( aiv_consent_get_categories() as $category => $definition ) {
		if ( empty( $registry[ $category ] ) || ! is_array( $registry[ $category ] ) ) {
			continue;
		}

		foreach ( $registry[ $category ] as $cookie_name ) {
			if ( is_string( $cookie_name ) && preg_match( '/^[A-Za-z0-9_.-]+$/', $cookie_name ) ) {
				$result[ $category ][] = $cookie_name;
			}
		}
	}

	return $result;
}
