<?php
/**
 * Réglages du champ dans l'éditeur de formulaire.
 *
 * @package WSForm_Siret
 */

namespace WSForm_Siret;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Déclare les réglages propres au champ et les textes que chaque champ peut remplacer.
 */
class Field_Settings {

	const ESTABLISHMENT_HEAD_OFFICE = 'head_office';
	const ESTABLISHMENT_ANY         = 'any';

	/**
	 * Branche les réglages sur la configuration de WS Form.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_filter( 'wsf_config_meta_keys', array( __CLASS__, 'register_meta_keys' ), 10, 1 );
	}

	/**
	 * Déclare les réglages du champ.
	 *
	 * @param array $meta_keys Réglages connus de WS Form.
	 * @return array
	 */
	public static function register_meta_keys( $meta_keys ) {
		return array_merge( $meta_keys, self::get_mapping_meta_keys(), self::get_behavior_meta_keys(), self::get_text_meta_keys() );
	}

	/**
	 * Lit un réglage texte d'un champ, ou son texte par défaut s'il est vide.
	 *
	 * @param object $field    Champ WS Form.
	 * @param string $text_key Clé du texte, comme dans get_default_texts().
	 * @return string
	 */
	public static function get_field_text( $field, $text_key ) {
		$defaults = self::get_default_texts();
		$meta_key = array_search( $text_key, self::get_text_keys(), true );
		$value    = false === $meta_key ? '' : \WS_Form_Common::get_object_meta_value( $field, $meta_key, '' );

		return is_string( $value ) && '' !== trim( $value ) ? $value : $defaults[ $text_key ];
	}

	/**
	 * Retourne les textes par défaut, par clé attendue du script du champ.
	 *
	 * @return string[]
	 */
	public static function get_default_texts() {
		return array(
			'invalid'     => __( 'Choose a company from the list, or enter a valid 14-digit SIRET number.', 'wsform-siret' ),
			'closed'      => __( 'This establishment is closed.', 'wsform-siret' ),
			'unknown'     => __( 'This SIRET number is not listed in the public directory of companies.', 'wsform-siret' ),
			'noResults'   => __( 'No company found. Check the spelling, or enter the SIRET number.', 'wsform-siret' ),
			'notListed'   => __( 'This number is valid, but the company is not in the public directory. Some companies choose not to be listed.', 'wsform-siret' ),
			'unavailable' => __( 'Company search is unavailable right now. You can still enter the SIRET number.', 'wsform-siret' ),
			'attribution' => __( 'Source: Sirene directory (Insee), via the Recherche d’entreprises API, Licence Ouverte 2.0', 'wsform-siret' ),
		);
	}

	/**
	 * Associe chaque réglage texte à sa clé de texte.
	 *
	 * @return string[]
	 */
	private static function get_text_keys() {
		return array(
			'wsfsiret_text_invalid'     => 'invalid',
			'wsfsiret_text_closed'      => 'closed',
			'wsfsiret_text_unknown'     => 'unknown',
			'wsfsiret_text_no_results'  => 'noResults',
			'wsfsiret_text_not_listed'  => 'notListed',
			'wsfsiret_text_unavailable' => 'unavailable',
			'wsfsiret_text_attribution' => 'attribution',
		);
	}

	/**
	 * Retourne les réglages des textes, vides par défaut pour suivre les traductions.
	 *
	 * @return array
	 */
	public static function get_text_meta_keys() {
		$defaults  = self::get_default_texts();
		$labels    = array(
			'invalid'     => __( 'Invalid number', 'wsform-siret' ),
			'closed'      => __( 'Closed establishment', 'wsform-siret' ),
			'unknown'     => __( 'Number not listed (on submit)', 'wsform-siret' ),
			'noResults'   => __( 'No suggestion', 'wsform-siret' ),
			'notListed'   => __( 'Number not listed (while typing)', 'wsform-siret' ),
			'unavailable' => __( 'Search unavailable', 'wsform-siret' ),
			'attribution' => __( 'Source mention', 'wsform-siret' ),
		);
		$meta_keys = array();

		foreach ( self::get_text_keys() as $meta_key => $text_key ) {
			$meta_keys[ $meta_key ] = array(
				'label'           => $labels[ $text_key ],
				'type'            => 'text',
				'default'         => '',
				'placeholder'     => $defaults[ $text_key ],
				'variable_helper' => true,
			);
		}

		return $meta_keys;
	}

	/**
	 * Retourne les réglages du mapping des données de l'entreprise.
	 *
	 * @return array
	 */
	private static function get_mapping_meta_keys() {
		return array(
			'wsfsiret_field_mapping' => array(
				'label'            => __( 'Field Mapping', 'wsform-siret' ),
				'type'             => 'repeater',
				'meta_keys'        => array( 'wsfsiret_component', 'ws_form_field_wsfsiret' ),
				'meta_keys_unique' => array( 'ws_form_field' ),
				'default'          => array(
					self::build_mapping_row( 'name' ),
					self::build_mapping_row( 'street' ),
					self::build_mapping_row( 'postcode' ),
					self::build_mapping_row( 'city' ),
				),
				'help'             => __( 'Fields to fill in when the visitor chooses a company. They are emptied if the visitor changes the SIRET afterwards.', 'wsform-siret' ),
			),
			'wsfsiret_component'     => array(
				'label'   => __( 'Company Data', 'wsform-siret' ),
				'type'    => 'select',
				'options' => self::get_component_options(),
			),
			// Le suffixe suit la convention du cœur : la valeur est rangée sous la clé ws_form_field.
			'ws_form_field_wsfsiret' => array(
				'label'              => __( 'Form Field', 'wsform-siret' ),
				'type'               => 'select',
				'options'            => 'fields',
				'options_blank'      => __( 'Select...', 'wsform-siret' ),
				'fields_filter_type' => array( 'text', 'textarea', 'select', 'hidden', 'tel' ),
				'key'                => 'ws_form_field',
			),
		);
	}

	/**
	 * Construit une ligne de mapping par défaut, sans champ choisi.
	 *
	 * @param string $component Donnée de l'entreprise.
	 * @return array
	 */
	private static function build_mapping_row( $component ) {
		return array(
			'wsfsiret_component' => $component,
			'ws_form_field'      => '',
		);
	}

	/**
	 * Retourne les données proposées au mapping.
	 *
	 * Les valeurs correspondent aux clés produites par wsfsiret-company.js.
	 *
	 * @return array
	 */
	private static function get_component_options() {
		$components = array(
			'name'       => __( 'Company name', 'wsform-siret' ),
			'legal_name' => __( 'Legal name', 'wsform-siret' ),
			'trade_name' => __( 'Trade name of the establishment', 'wsform-siret' ),
			'siret'      => __( 'SIRET', 'wsform-siret' ),
			'siren'      => __( 'SIREN', 'wsform-siret' ),
			'address'    => __( 'Full address', 'wsform-siret' ),
			'street'     => __( 'Street address', 'wsform-siret' ),
			'postcode'   => __( 'Postal code', 'wsform-siret' ),
			'city'       => __( 'City', 'wsform-siret' ),
			'naf'        => __( 'NAF code (main activity)', 'wsform-siret' ),
			'vat'        => __( 'VAT number (when known)', 'wsform-siret' ),
			'status'     => __( 'Administrative status', 'wsform-siret' ),
		);
		$options    = array();

		foreach ( $components as $value => $text ) {
			$options[] = array(
				'value' => $value,
				'text'  => $text,
			);
		}

		return $options;
	}

	/**
	 * Retourne les réglages de comportement : suggestions, contrôles, mention de la source.
	 *
	 * @return array
	 */
	private static function get_behavior_meta_keys() {
		return array(
			'wsfsiret_establishment'  => array(
				'label'   => __( 'Establishment', 'wsform-siret' ),
				'type'    => 'select',
				'default' => self::ESTABLISHMENT_HEAD_OFFICE,
				'options' => array(
					array(
						'value' => self::ESTABLISHMENT_HEAD_OFFICE,
						'text'  => __( 'Head office', 'wsform-siret' ),
					),
					array(
						'value' => self::ESTABLISHMENT_ANY,
						'text'  => __( 'Let the visitor choose an establishment', 'wsform-siret' ),
					),
				),
				'help'    => __( 'Which establishments the suggestions offer. A SIRET typed by the visitor is always kept.', 'wsform-siret' ),
			),
			'wsfsiret_reject_closed'  => array(
				'label'   => __( 'Refuse a closed establishment', 'wsform-siret' ),
				'type'    => 'checkbox',
				'default' => '',
				'help'    => __( 'Checked with the public directory on submit. If it does not answer, the form is accepted.', 'wsform-siret' ),
			),
			'wsfsiret_reject_unknown' => array(
				'label'   => __( 'Refuse a SIRET missing from the directory', 'wsform-siret' ),
				'type'    => 'checkbox',
				'default' => '',
				'help'    => __( 'Careful: companies that refuse publication are not in the directory, and would be refused too.', 'wsform-siret' ),
			),
			'wsfsiret_attribution'    => array(
				'label'   => __( 'Show the data source', 'wsform-siret' ),
				'type'    => 'checkbox',
				'default' => 'on',
				'help'    => __( 'The Licence Ouverte of the data asks to credit the source. The text can be changed in Advanced > Labels.', 'wsform-siret' ),
			),
		);
	}
}
