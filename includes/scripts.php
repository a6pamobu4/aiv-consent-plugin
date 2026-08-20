<?php
/**
 * Conservative WordPress script-handle integration.
 *
 * @package AIV_Consent
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'script_loader_tag', 'aiv_consent_filter_script_loader_tag', 20, 3 );

/**
 * Returns validated handle-to-category mappings.
 *
 * @return array<string, string>
 */
function aiv_consent_get_script_categories() {
	/**
	 * Filters explicitly consent-managed WordPress script handles.
	 *
	 * @param array<string, string> $scripts Handle-to-category map.
	 */
	$scripts    = apply_filters( 'aiv_consent_script_categories', array() );
	$categories = aiv_consent_get_categories();
	$validated  = array();

	if ( ! is_array( $scripts ) ) {
		return $validated;
	}

	foreach ( $scripts as $handle => $category ) {
		$handle   = sanitize_key( $handle );
		$category = sanitize_key( $category );

		if ( '' !== $handle && isset( $categories[ $category ] ) && empty( $categories[ $category ]['required'] ) ) {
			$validated[ $handle ] = $category;
		}
	}

	return $validated;
}

/**
 * Converts explicitly mapped optional script handles into inert markup.
 *
 * This deliberately ignores the request cookie. Cached HTML is therefore
 * identical for consented and unconsented visitors; the browser activates the
 * script only after validating its local consent state.
 *
 * @param string $tag    Script HTML.
 * @param string $handle Registered handle.
 * @param string $src    Script URL.
 * @return string
 */
function aiv_consent_filter_script_loader_tag( $tag, $handle, $src ) {
	if ( is_admin() || ! aiv_consent_is_enabled() ) {
		return $tag;
	}

	$mappings = aiv_consent_get_script_categories();

	if ( ! isset( $mappings[ $handle ] ) ) {
		return $tag;
	}

	if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
		return $tag;
	}

	$processor = new WP_HTML_Tag_Processor( $tag );

	if ( ! $processor->next_tag( 'script' ) ) {
		return $tag;
	}

	$original_type = $processor->get_attribute( 'type' );

	if ( is_string( $original_type ) && '' !== $original_type && 'text/javascript' !== $original_type ) {
		$processor->set_attribute( 'data-aiv-type', $original_type );
	}

	$processor->set_attribute( 'type', 'text/plain' );
	$processor->set_attribute( 'data-aiv-consent', $mappings[ $handle ] );
	$processor->set_attribute( 'data-src', $src );
	$processor->remove_attribute( 'src' );

	return $processor->get_updated_html();
}
