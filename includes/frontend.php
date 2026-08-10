<?php
/**
 * Frontend assets and consent interface.
 *
 * @package AIV_Consent
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', 'aiv_consent_enqueue_frontend_assets' );
add_action( 'wp_footer', 'aiv_consent_render_frontend' );
add_action( 'init', 'aiv_consent_register_shortcode' );

/**
 * Returns whether the frontend consent manager is enabled.
 *
 * @return bool
 */
function aiv_consent_is_enabled() {
	$options = aiv_consent_get_options();

	return ! empty( $options['enabled'] );
}

/**
 * Enqueues the single frontend script and stylesheet.
 *
 * @return void
 */
function aiv_consent_enqueue_frontend_assets() {
	if ( ! aiv_consent_is_enabled() ) {
		return;
	}

	$options           = aiv_consent_get_options();
	$categories        = aiv_consent_get_categories();
	$client_categories = array();

	foreach ( $categories as $key => $category ) {
		$client_categories[ $key ] = array(
			'required' => ! empty( $category['required'] ),
		);
	}

	$config = array(
		'cookieName'        => aiv_consent_get_cookie_name(),
		'version'           => (string) $options['consent_version'],
		'lifetimeDays'      => (int) $options['cookie_lifetime'],
		'secure'            => is_ssl(),
		'repromptOnVersion' => ! empty( $options['reprompt_on_version_change'] ),
		'reloadOnRevoke'    => ! empty( $options['reload_after_consent_revocation'] ),
		'categories'        => $client_categories,
		'categoryCookies'   => aiv_consent_get_category_cookies(),
	);

	wp_enqueue_style( 'aiv-consent', AIV_CONSENT_URL . 'assets/css/consent.css', array(), AIV_CONSENT_VERSION );
	wp_enqueue_script(
		'aiv-consent',
		AIV_CONSENT_URL . 'assets/js/consent.js',
		array(),
		AIV_CONSENT_VERSION,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
	wp_add_inline_script( 'aiv-consent', 'window.AIVConsentConfig = ' . wp_json_encode( $config ) . ';', 'before' );
}

/**
 * Registers the settings button shortcode.
 *
 * @return void
 */
function aiv_consent_register_shortcode() {
	add_shortcode( 'aiv_consent_settings', 'aiv_consent_settings_shortcode' );
}

/**
 * Returns an accessible button that reopens consent settings.
 *
 * @param string $label Optional button label.
 * @return string
 */
function aiv_consent_get_settings_button( $label = '' ) {
	if ( '' === $label ) {
		$label = __( 'Настройки cookie', 'aiv-consent' );
	}

	return sprintf(
		'<button type="button" class="aiv-consent-open-settings" data-aiv-consent-open>%s</button>',
		esc_html( $label )
	);
}

/**
 * Handles [aiv_consent_settings].
 *
 * @param array<string, string> $attributes Shortcode attributes.
 * @return string
 */
function aiv_consent_settings_shortcode( $attributes ) {
	if ( ! aiv_consent_is_enabled() ) {
		return '';
	}

	$attributes = shortcode_atts(
		array(
			'label' => __( 'Настройки cookie', 'aiv-consent' ),
		),
		$attributes,
		'aiv_consent_settings'
	);

	return aiv_consent_get_settings_button( sanitize_text_field( $attributes['label'] ) );
}

/**
 * Renders optional policy links.
 *
 * @return void
 */
function aiv_consent_render_policy_links() {
	$options     = aiv_consent_get_options();
	$privacy_url = aiv_consent_get_privacy_policy_url();
	$cookie_url  = (string) $options['cookie_policy_url'];

	if ( '' === $privacy_url && '' === $cookie_url ) {
		return;
	}
	?>
	<div class="aiv-consent-policy-links">
		<?php if ( '' !== $privacy_url ) : ?>
			<a href="<?php echo esc_url( $privacy_url ); ?>"><?php esc_html_e( 'Политика конфиденциальности', 'aiv-consent' ); ?></a>
		<?php endif; ?>
		<?php if ( '' !== $cookie_url ) : ?>
			<a href="<?php echo esc_url( $cookie_url ); ?>"><?php echo esc_html( $options['cookie_policy_label'] ); ?></a>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Renders the banner and accessible settings dialog.
 *
 * @return void
 */
function aiv_consent_render_frontend() {
	if ( ! aiv_consent_is_enabled() ) {
		return;
	}

	$options    = aiv_consent_get_options();
	$categories = aiv_consent_get_categories();
	$state      = aiv_consent_get_current_state();
	?>
	<div class="aiv-consent-root" data-aiv-consent-root>
		<section class="aiv-consent-banner" data-aiv-consent-banner aria-labelledby="aiv-consent-banner-title" aria-describedby="aiv-consent-banner-description"<?php echo ! empty( $state['valid'] ) ? ' hidden' : ''; ?>>
			<div class="aiv-consent-banner-inner">
				<div class="aiv-consent-banner-content">
					<h2 id="aiv-consent-banner-title" class="aiv-consent-title"><?php echo esc_html( $options['banner_title'] ); ?></h2>
					<p id="aiv-consent-banner-description" class="aiv-consent-text"><?php echo esc_html( $options['banner_description'] ); ?></p>
					<?php aiv_consent_render_policy_links(); ?>
				</div>
				<div class="aiv-consent-actions">
					<button type="button" class="aiv-consent-button aiv-consent-button-primary" data-aiv-consent-accept><?php echo esc_html( $options['accept_all_label'] ); ?></button>
					<button type="button" class="aiv-consent-button aiv-consent-button-secondary" data-aiv-consent-reject><?php echo esc_html( $options['reject_optional_label'] ); ?></button>
					<button type="button" class="aiv-consent-button aiv-consent-button-tertiary" data-aiv-consent-customize><?php echo esc_html( $options['customize_label'] ); ?></button>
				</div>
			</div>
		</section>

		<div class="aiv-consent-backdrop" data-aiv-consent-backdrop hidden>
			<section class="aiv-consent-settings" data-aiv-consent-dialog role="dialog" aria-modal="true" aria-labelledby="aiv-consent-modal-title" aria-describedby="aiv-consent-modal-description" tabindex="-1">
				<div class="aiv-consent-settings-header">
					<h2 id="aiv-consent-modal-title" class="aiv-consent-title"><?php echo esc_html( $options['modal_title'] ); ?></h2>
					<button type="button" class="aiv-consent-close" data-aiv-consent-close aria-label="<?php esc_attr_e( 'Закрыть настройки cookie', 'aiv-consent' ); ?>">&times;</button>
				</div>
				<p id="aiv-consent-modal-description" class="aiv-consent-text"><?php esc_html_e( 'Выберите, какие необязательные категории разрешить. Необходимые cookie отключить нельзя.', 'aiv-consent' ); ?></p>
				<form class="aiv-consent-form" data-aiv-consent-form>
					<div class="aiv-consent-categories">
						<?php foreach ( $categories as $key => $category ) : ?>
							<div class="aiv-consent-category">
								<div class="aiv-consent-category-copy">
									<h3 class="aiv-consent-category-title"><?php echo esc_html( $category['label'] ); ?></h3>
									<p><?php echo esc_html( $category['description'] ); ?></p>
								</div>
								<?php if ( ! empty( $category['required'] ) ) : ?>
									<span class="aiv-consent-required"><?php esc_html_e( 'Всегда включены', 'aiv-consent' ); ?></span>
								<?php else : ?>
									<label class="aiv-consent-toggle">
										<span class="screen-reader-text"><?php echo esc_html( $category['label'] ); ?></span>
										<input type="checkbox" name="aiv-consent-category" value="<?php echo esc_attr( $key ); ?>" data-aiv-consent-category="<?php echo esc_attr( $key ); ?>">
										<span class="aiv-consent-toggle-track" aria-hidden="true"></span>
									</label>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
					<?php aiv_consent_render_policy_links(); ?>
					<div class="aiv-consent-actions">
						<button type="submit" class="aiv-consent-button aiv-consent-button-primary"><?php echo esc_html( $options['save_settings_label'] ); ?></button>
						<button type="button" class="aiv-consent-button aiv-consent-button-secondary" data-aiv-consent-accept><?php echo esc_html( $options['accept_all_label'] ); ?></button>
						<button type="button" class="aiv-consent-button aiv-consent-button-tertiary" data-aiv-consent-reject><?php echo esc_html( $options['reject_optional_label'] ); ?></button>
					</div>
				</form>
			</section>
		</div>
	</div>
	<?php
}
