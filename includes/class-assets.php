<?php
/**
 * Chargement à la demande des scripts du champ.
 *
 * @package WSForm_Siret
 */

namespace WSForm_Siret;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ne charge les fichiers que si un formulaire de la page contient le champ.
 */
class Assets {

	/**
	 * Fiche de la base Sirene sur data.gouv.fr, citée par la mention de la source.
	 */
	const SOURCE_URL = 'https://www.data.gouv.fr/datasets/base-sirene-des-entreprises-et-de-leurs-etablissements-siren-siret';

	/**
	 * Branche le chargement sur WS Form.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'wsf_form_pre_process_field', array( __CLASS__, 'maybe_enqueue' ), 10, 1 );
	}

	/**
	 * Charge les fichiers quand WS Form parcourt un champ SIRET.
	 *
	 * Ce crochet ne se déclenche que pour les formulaires réellement rendus,
	 * contrairement à wsf_enqueue_scripts qui ne dit rien de leur contenu.
	 *
	 * @param object $field Champ en cours de traitement.
	 * @return void
	 */
	public static function maybe_enqueue( $field ) {
		// Appelé pour chaque champ : un second passage dupliquerait les réglages dans la page.
		if ( ! isset( $field->type ) || Field_Type::FIELD_TYPE !== $field->type || wp_script_is( 'wsfsiret-field', 'enqueued' ) ) {
			return;
		}

		wp_enqueue_style( 'wsfsiret-field', Plugin::get_url( 'assets/css/wsfsiret-field.css' ), array(), Plugin::VERSION );

		wp_enqueue_script( 'wsfsiret-fields', Plugin::get_url( 'assets/js/wsfsiret-fields.js' ), array( 'jquery' ), Plugin::VERSION, true );
		wp_enqueue_script( 'wsfsiret-number', Plugin::get_url( 'assets/js/wsfsiret-number.js' ), array(), Plugin::VERSION, true );
		wp_enqueue_script( 'wsfsiret-api-client', Plugin::get_url( 'assets/js/wsfsiret-api-client.js' ), array(), Plugin::VERSION, true );
		wp_enqueue_script( 'wsfsiret-company', Plugin::get_url( 'assets/js/wsfsiret-company.js' ), array(), Plugin::VERSION, true );
		wp_enqueue_script( 'wsfsiret-field', Plugin::get_url( 'assets/js/wsfsiret-field.js' ), array( 'jquery', 'wsfsiret-fields', 'wsfsiret-number', 'wsfsiret-api-client', 'wsfsiret-company' ), Plugin::VERSION, true );

		wp_localize_script( 'wsfsiret-field', 'wsfsiretSettings', self::get_settings() );
	}

	/**
	 * Retourne les textes et l'adresse de la source destinés au script.
	 *
	 * @return array
	 */
	private static function get_settings() {
		return array(
			'sourceUrl' => self::SOURCE_URL,
			'i18n'      => array_merge(
				Field_Settings::get_default_texts(),
				array(
					'suggestions' => __( 'Company suggestions', 'wsform-siret' ),
					'oneResult'   => __( '1 company found. Use the arrow keys to browse.', 'wsform-siret' ),
					/* translators: %d: number of suggestions. */
					'manyResults' => __( '%d companies found. Use the arrow keys to browse.', 'wsform-siret' ),
					'active'      => __( 'Active', 'wsform-siret' ),
					'closedLabel' => __( 'Closed', 'wsform-siret' ),
					'headOffice'  => __( 'head office', 'wsform-siret' ),
					/* translators: %s: SIRET number. */
					'siretLabel'  => __( 'SIRET %s', 'wsform-siret' ),
				)
			),
		);
	}
}
