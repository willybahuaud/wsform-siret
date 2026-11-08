<?php
/**
 * Liaison avec Carte IGN pour WS Form.
 *
 * @package WSForm_Siret
 */

namespace WSForm_Siret;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Place l'entreprise choisie sur une carte IGN du même formulaire.
 *
 * Les deux plugins restent indépendants : sans Carte IGN pour WS Form, ces
 * réglages n'apparaissent pas. Le point est posé avec les coordonnées de l'API
 * Recherche d'entreprises, sans géocodage de plus.
 */
class Ign_Link {

	/**
	 * Types de champs de Carte IGN pour WS Form.
	 */
	const MAP_FIELD_TYPE     = 'wsfign_map';
	const ADDRESS_FIELD_TYPE = 'wsfign_address';

	/**
	 * Zoom de la carte une fois l'entreprise choisie : la rue reste lisible autour du point.
	 */
	const DEFAULT_ZOOM = '17';

	/**
	 * Branche les réglages sur la configuration de WS Form.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_filter( 'wsf_config_meta_keys', array( __CLASS__, 'register_meta_keys' ), 10, 1 );
	}

	/**
	 * Indique si Carte IGN pour WS Form est actif.
	 *
	 * @return bool
	 */
	public static function is_available() {
		return class_exists( 'WSFIGN_Map_Field' );
	}

	/**
	 * Retourne le groupe de réglages de l'onglet Général, vide sans Carte IGN.
	 *
	 * @return array[]
	 */
	public static function get_fieldsets() {
		if ( ! self::is_available() ) {
			return array();
		}

		return array(
			array(
				'label'     => __( 'IGN Map', 'wsform-siret' ),
				'meta_keys' => array( 'wsfsiret_map', 'wsfsiret_map_zoom' ),
			),
		);
	}

	/**
	 * Déclare les réglages de la liaison.
	 *
	 * @param array $meta_keys Réglages connus de WS Form.
	 * @return array
	 */
	public static function register_meta_keys( $meta_keys ) {
		$meta_keys['wsfsiret_map']      = array(
			'label'              => __( 'Field', 'wsform-siret' ),
			'type'               => 'select',
			'options'            => 'fields',
			'options_blank'      => __( 'Select...', 'wsform-siret' ),
			'fields_filter_type' => array( self::MAP_FIELD_TYPE ),
			'help'               => __( 'IGN Map field where the chosen establishment is placed.', 'wsform-siret' ),
		);
		$meta_keys['wsfsiret_map_zoom'] = array(
			'label'           => __( 'Zoom', 'wsform-siret' ),
			'type'            => 'text',
			'variable_helper' => true,
			'default'         => self::DEFAULT_ZOOM,
			'placeholder'     => self::DEFAULT_ZOOM,
			'help'            => __( 'Zoom of the map once an establishment is chosen.', 'wsform-siret' ),
		);

		return $meta_keys;
	}
}
