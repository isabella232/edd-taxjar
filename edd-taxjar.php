<?php
/*
Plugin Name: Easy Digital Downloads - TaxJar
Plugin URI: https://easydigitaldownloads.com/extensions/taxjar
Description: Calculate sales tax through automatically TaxJar.com
Version: 1.0
Author: Easy Digital Downloads
Author URI: https://easydigitaldownloads.com
*/

class EDD_TaxJar {

	private $api_token;
	private $api;

	public function __construct() {

		if( ! function_exists( 'edd_get_option' ) ) {
			return;
		}

		$this->api_token = trim( edd_get_option( 'edd_taxjar_api_token', '' ) );

		add_action( 'init',                        array( $this, 'textdomain' ) );
		add_filter( 'edd_settings_sections_taxes', array( $this, 'subsection' ), 10, 1 );
		add_filter( 'edd_settings_taxes',          array( $this, 'settings' ) );
		add_filter( 'edd_tax_rate',                array( $this, 'get_tax_rate' ) );
		add_action( 'edd_payment_saved',           array( $this, 'store_taxjar_data_on_payment' ), 10, 2 );

		$this->load_sdk();
	}

	public function textdomain() {

		// Set filter for plugin's languages directory
		$lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
		$lang_dir = apply_filters( 'edd_taxjar_languages_directory', $lang_dir );

		// Traditional WordPress plugin locale filter
		$locale        = apply_filters( 'plugin_locale',  get_locale(), 'edd-taxjar' );
		$mofile        = sprintf( '%1$s-%2$s.mo', 'edd-taxjar', $locale );

		// Setup paths to current locale file
		$mofile_local  = $lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/edd-taxjar/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			load_textdomain( 'edd-taxjar', $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			load_textdomain( 'edd-taxjar', $mofile_local );
		} else {
			// Load the default language files
			load_plugin_textdomain( 'edd-taxjar', false, $lang_dir );
		}

	}

	public function subsection( $sections ) {
		$sections['taxjar'] = __( 'TaxJar', 'edd-taxjar' );
		return $sections;
	}

	public function settings( $settings ) {

		$taxjar_settings = array(
			array(
				'id'      => 'edd_taxjar_header',
				'name'    => '<strong>' . __( 'TaxJar', 'edd-taxjar' ) . '</strong>',
				'desc'    => '',
				'type'    => 'header',
				'size'    => 'regular'
			),
			array(
				'id'      => 'edd_taxjar_api_token',
				'name'    => __( 'API Token', 'edd-taxjar' ),
				'desc'    => __( 'Enter your TaxJar API Token' ),
				'type'    => 'text'
			),
		);

		if ( version_compare( EDD_VERSION, 2.5, '>=' ) ) {
			$taxjar_settings = array( 'taxjar' => $taxjar_settings );
		}


		return array_merge( $settings, $taxjar_settings );

	}

	private function load_sdk() {

		if( empty( $this->api_token ) ) {
			return;
		}

		require __DIR__ . '/vendor/autoload.php';

		$this->api = TaxJar\Client::withApiKey( $this->api_token );

	}

	public function get_tax_rate( $rate = 0, $country = false, $state = false ) {

		if( ! empty( $_POST['card_zip'] ) ) {

			$zip     = isset( $_POST['card_zip'] )        ? sanitize_text_field( $_POST['card_zip'] )        : '';
			$country = isset( $_POST['billing_country'] ) ? sanitize_text_field( $_POST['billing_country'] ) : '';

			try {

				$rates = $this->api->ratesForLocation( $zip, array(
					'country' => $country,
				) );

				if( ! empty( $rates->combined_rate ) ) {

					EDD()->session->set( 'taxjar', json_encode( $rates ) );

					return $rates->combined_rate;

				}

			} catch ( Exception $e ) {

				edd_debug_log( 'TaxJar API Exception: ' . $e->getMessage() );

			}

		}

		return $rate;
	}

	public function store_taxjar_data_on_payment( $payment_id, $payment ) {

		$tax_data = EDD()->session->get( 'taxjar' );

		if( $tax_data ) {
			$payment->add_meta( 'edd_taxjar_data', $tax_data );
			EDD()->session->set( 'taxjar', null );
		}

	}
}

function edd_taxjar_init() {
	$taxjar = new EDD_TaxJar;
	unset( $taxjar );
}
add_action( 'plugins_loaded', 'edd_taxjar_init' );
