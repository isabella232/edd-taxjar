<?php
/*
Plugin Name: Easy Digital Downloads - TaxJar
Plugin URI: https://easydigitaldownloads.com/extensions/taxjar
Description: Calculate sales tax through automatically TaxJar.com
Version: 1.0.1
Author: Easy Digital Downloads
Author URI: https://easydigitaldownloads.com
*/

/**
 * EDD_TaxJar Class
 *
 * @since 1.0.0
 */
class EDD_TaxJar {

	/**
	 * The API token from Tax Jar.
	 *
	 * @since  1.0.0
	 * @var string
	 */
	private $api_token;

	/**
	 * API variable.
	 *
	 * @since  1.0.0
	 * @var string
	 * @return  void
	 */
	private $api;

	/**
	 * Get things started
	 *
	 * @since   1.0.0
	 */
	public function __construct() {

		if ( ! function_exists( 'edd_get_option' ) ) {
			return;
		}

		$this->api_token = trim( edd_get_option( 'edd_taxjar_api_token', '' ) );

		add_action( 'init', array( $this, 'textdomain' ) );
		add_filter( 'edd_settings_sections_taxes', array( $this, 'subsection' ), 10, 1 );
		add_filter( 'edd_settings_taxes', array( $this, 'settings' ) );
		add_filter( 'edd_tax_rate', array( $this, 'get_tax_rate' ) );
		add_action( 'edd_payment_saved', array( $this, 'store_taxjar_data_on_payment' ), 10, 2 );

		$this->load_sdk();
	}

	/**
	 * Set up the text domains for translation.
	 *
	 * @since   1.0.0
	 * @return  void
	 */
	public function textdomain() {

		// Set filter for plugin's languages directory.
		$lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
		$lang_dir = apply_filters( 'edd_taxjar_languages_directory', $lang_dir );

		// Traditional WordPress plugin locale filter.
		$locale = apply_filters( 'plugin_locale', get_locale(), 'edd-taxjar' );
		$mofile = sprintf( '%1$s-%2$s.mo', 'edd-taxjar', $locale );

		// Setup paths to current locale file.
		$mofile_local  = $lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/edd-taxjar/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			load_textdomain( 'edd-taxjar', $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			load_textdomain( 'edd-taxjar', $mofile_local );
		} else {
			// Load the default language files.
			load_plugin_textdomain( 'edd-taxjar', false, $lang_dir );
		}

	}

	/**
	 * Create a subsection for the nax in EDD settings.
	 *
	 * @since   1.0.0
	 * @param   array $sections The subsections currently in this settings section.
	 * @return  array $sections The modified subsections currently in this settings section.
	 */
	public function subsection( $sections ) {
		$sections['taxjar'] = __( 'TaxJar', 'edd-taxjar' );
		return $sections;
	}

	/**
	 * Add the settings for Tax Jar to the settings array in EDD core.
	 *
	 * @since   1.0.0
	 * @param   array $settings The settings currently in this settings section.
	 * @return  array $settings The settings currently in this settings section.
	 */
	public function settings( $settings ) {

		$taxjar_settings = array(
			array(
				'id'   => 'edd_taxjar_header',
				'name' => '<strong>' . __( 'TaxJar', 'edd-taxjar' ) . '</strong>',
				'desc' => '',
				'type' => 'header',
				'size' => 'regular',
			),
			array(
				'id'   => 'edd_taxjar_api_token',
				'name' => __( 'API Token', 'edd-taxjar' ),
				'desc' => __( 'Enter your TaxJar API Token' ),
				'type' => 'text',
			),
		);

		if ( version_compare( EDD_VERSION, 2.5, '>=' ) ) {
			$taxjar_settings = array( 'taxjar' => $taxjar_settings );
		}

		$settings                                 = array_merge( $settings, $taxjar_settings );
		$settings['main']['enable_taxes']['desc'] = $settings['main']['enable_taxes']['desc'] . ' ' . __( '<strong>Note</strong>: with TaxJar enabled, all tax rates are calculated automatically for supported countries. For countries not supported by TaxJar, the rates below will be used if applicable.', 'edd-taxjar' );
		$settings['rates']['tax_rates']['desc']   = $settings['main']['enable_taxes']['desc'];

		return $settings;

	}

	/**
	 * Load the Tax Jar SDK.
	 *
	 * @since   1.0.0
	 * @return  void
	 */
	private function load_sdk() {

		if ( empty( $this->api_token ) ) {
			return;
		}

		require __DIR__ . '/vendor/autoload.php';

		$this->api = TaxJar\Client::withApiKey( $this->api_token );

	}

	/**
	 * This function filters to the edd_tax_rate filter to override the tax rate with the one from Tax Jar.
	 *
	 * @since 1.0.0
	 * @param int    $rate The tax rate.
	 * @param string $country The country for the tax rate.
	 * @param string $state The state for the tax rate.
	 * @return  int The tax rate.
	 */
	public function get_tax_rate( $rate = 0, $country = false, $state = false ) {

		global $edd_taxjar;

		$zip = isset( $_POST['card_zip'] ) ? sanitize_text_field( wp_unslash( $_POST['card_zip'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

		if ( ! empty( $zip ) ) {

			$zip = apply_filters( 'edd_tax_jar_zip', $zip, $country, $state, $rate );

			$country = isset( $_POST['billing_country'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_country'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
			$country = apply_filters( 'edd_tax_jar_country', $country, $zip, $state, $rate );

			if ( ! empty( $edd_taxjar ) ) {

				if ( $zip === $edd_taxjar->zip && $country === $edd_taxjar->country ) {
					return $edd_taxjar->combined_rate;
				}
			}

			try {

				$rates = $this->api->ratesForLocation(
					$zip,
					array(
						'country' => $country,
					)
				);

				if ( ! empty( $rates->combined_rate ) ) {

					$edd_taxjar = $rates;

					EDD()->session->set( 'taxjar', wp_json_encode( $rates ) );

					edd_debug_log( 'TaxJar API Response: ' . var_export( $rates, true ) ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export

					return $rates->combined_rate;

				}
			} catch ( Exception $e ) {

				edd_debug_log( 'TaxJar API Exception: ' . $e->getMessage() );

			}
		}

		return $rate;
	}

	/**
	 * When a payment is saved, save the tax jar information about the tax rate to the payment's meta.
	 *
	 * @since 1.0.0
	 * @param int         $payment_id The ID of the payment on which to store the tax jar data.
	 * @param EDD_Payment $payment The EDD Payment object.
	 * @return void
	 */
	public function store_taxjar_data_on_payment( $payment_id, $payment ) {

		$tax_data = EDD()->session->get( 'taxjar' );

		if ( $tax_data ) {
			$payment->add_meta( 'edd_taxjar_data', $tax_data );
			EDD()->session->set( 'taxjar', null );
		}

	}
}

/**
 * Get the tax jar class running.
 *
 * @since 1.0.0
 * @return void
 */
function edd_taxjar_init() {
	$taxjar = new EDD_TaxJar();
	unset( $taxjar );
}
add_action( 'plugins_loaded', 'edd_taxjar_init' );
