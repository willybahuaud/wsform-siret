<?php
/**
 * Déclaration du type de champ auprès de WS Form.
 *
 * @package WSForm_Siret
 */

namespace WSForm_Siret;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Type de champ wsfsiret : un champ texte enrichi, dont la valeur enregistrée est le SIRET.
 *
 * Le visiteur peut y taper un nom : le champ le remplace par le SIRET de l'entreprise
 * choisie. Une valeur simple reste lisible partout, sans filtre d'affichage.
 */
class Field_Type {

	const FIELD_TYPE = 'wsfsiret';

	/**
	 * Groupe partagé avec Carte IGN pour WS Form : installés ensemble, les deux
	 * plugins rangent leurs champs sous le même titre au lieu de deux homonymes.
	 */
	const FIELD_GROUP = 'wsfign';

	/**
	 * Branche le champ sur la configuration de WS Form.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_filter( 'wsf_config_field_types', array( __CLASS__, 'register_field_type' ) );
	}

	/**
	 * Ajoute le type de champ au groupe, en créant le groupe au besoin.
	 *
	 * @param array $field_types Types de champs connus de WS Form.
	 * @return array
	 */
	public static function register_field_type( $field_types ) {
		if ( ! isset( $field_types[ self::FIELD_GROUP ] ) ) {
			$field_types[ self::FIELD_GROUP ] = array(
				'label' => __( 'French public services', 'wsform-siret' ),
				'types' => array(),
			);
		}

		$field_types[ self::FIELD_GROUP ]['types'][ self::FIELD_TYPE ] = self::get_config();

		return $field_types;
	}

	/**
	 * Retourne la définition du type de champ.
	 *
	 * Le masque pose la liste de suggestions et le résumé de l'entreprise : le
	 * script les remplit, avec du texte seulement, jamais du HTML venu de l'API.
	 *
	 * @return array
	 */
	private static function get_config() {
		return array(
			'label'                       => __( 'Company (SIRET)', 'wsform-siret' ),
			'label_default'               => __( 'Company', 'wsform-siret' ),
			'icon'                        => 'text',
			'submit_save'                 => true,
			'submit_edit'                 => true,
			'submit_array'                => false,
			'calc_in'                     => false,
			'calc_out'                    => false,
			'text_in'                     => false,
			'text_out'                    => true,
			'value_out'                   => true,
			'mappable'                    => true,
			'has_required'                => true,
			'progress'                    => true,
			'keyword'                     => __( 'siret siren company business entreprise société sirene', 'wsform-siret' ),
			'conditional'                 => array(
				'logics_enabled'  => array( 'equals', 'equals_not', 'contains', 'contains_not', 'starts', 'starts_not', 'blank', 'blank_not', 'field_match', 'field_match_not', 'validate', 'validate_not', 'focus', 'blur', 'change', 'input' ),
				'actions_enabled' => array( 'visibility', 'required', 'focus', 'value', 'disabled', 'readonly', 'set_custom_validity', 'class_add_wrapper', 'class_remove_wrapper', 'class_add_field', 'class_remove_field', 'reset', 'clear' ),
				'condition_event' => 'change input',
			),
			'events'                      => array(
				'event'        => 'change',
				'event_action' => __( 'Field', 'wsform-siret' ),
			),
			'trigger'                     => 'change',
			'mask_field'                  => '#pre_label#pre_help<input type="text" id="#id" name="#name" value="#value" spellcheck="false" data-wsfsiret#attributes /><div class="wsfsiret-anchor"><ul class="wsfsiret-listbox" role="listbox" hidden></ul></div><div class="wsfsiret-summary" hidden></div>#post_label#invalid_feedback#post_help',
			'mask_field_attributes'       => array( 'class', 'disabled', 'readonly', 'required', 'placeholder', 'aria_describedby', 'aria_labelledby', 'aria_label', 'custom_attributes', 'hidden_bypass' ),
			'mask_field_label'            => '<label id="#label_id" for="#id"#attributes>#label</label>',
			'mask_field_label_attributes' => array( 'class' ),
			'fieldsets'                   => self::get_fieldsets(),
		);
	}

	/**
	 * Retourne les onglets de réglages du champ.
	 *
	 * @return array
	 */
	private static function get_fieldsets() {
		return array(
			'basic'    => array(
				'label'     => __( 'Basic', 'wsform-siret' ),
				'meta_keys' => array( 'label', 'label_render', 'required', 'hidden', 'hidden_warning', 'placeholder', 'help' ),
				'fieldsets' => array_merge(
					array(
						array(
							'label'     => __( 'Company Fields', 'wsform-siret' ),
							'meta_keys' => array( 'wsfsiret_field_mapping' ),
						),
						array(
							'label'     => __( 'Suggestions', 'wsform-siret' ),
							'meta_keys' => array( 'wsfsiret_establishment' ),
						),
					),
					Ign_Link::get_fieldsets(),
					self::get_end_of_basic_fieldsets()
				),
			),
			'advanced' => self::get_advanced_tab(),
		);
	}

	/**
	 * Retourne les groupes de fin de l'onglet Général : contrôles, source, options du cœur.
	 *
	 * @return array[]
	 */
	private static function get_end_of_basic_fieldsets() {
		return array(
			array(
				'label'     => __( 'Checks on Submit', 'wsform-siret' ),
				'meta_keys' => array( 'wsfsiret_reject_closed', 'wsfsiret_reject_unknown' ),
			),
			array(
				'label'     => __( 'Data Source', 'wsform-siret' ),
				'meta_keys' => array( 'wsfsiret_attribution' ),
			),
			array(
				'label'     => __( 'Accessibility', 'wsform-siret' ),
				'meta_keys' => array( 'aria_label' ),
			),
			array(
				'label'     => __( 'Exclusions', 'wsform-siret' ),
				'meta_keys' => array( 'exclude_email' ),
			),
			array(
				'label'     => __( 'Hidden Behavior', 'wsform-siret' ),
				'meta_keys' => array( 'hidden_bypass' ),
			),
		);
	}

	/**
	 * Retourne l'onglet Avancé.
	 *
	 * @return array
	 */
	private static function get_advanced_tab() {
		return array(
			'label'     => __( 'Advanced', 'wsform-siret' ),
			'fieldsets' => array(
				array(
					'label'     => __( 'Style', 'wsform-siret' ),
					'meta_keys' => array( 'label_position_no_inside', 'label_column_width', 'help_position', 'class_single_vertical_align' ),
				),
				array(
					'label'     => __( 'Classes', 'wsform-siret' ),
					'meta_keys' => array( 'class_field_wrapper', 'class_field' ),
				),
				array(
					'label'     => __( 'Labels', 'wsform-siret' ),
					'meta_keys' => array_keys( Field_Settings::get_text_meta_keys() ),
				),
				array(
					'label'     => __( 'Restrictions', 'wsform-siret' ),
					'meta_keys' => array( 'field_user_status', 'field_user_roles', 'field_user_capabilities' ),
				),
				array(
					'label'     => __( 'Validation', 'wsform-siret' ),
					'meta_keys' => array( 'invalid_feedback_render', 'validate_inline', 'invalid_feedback' ),
				),
				array(
					'label'     => __( 'Custom Attributes', 'wsform-siret' ),
					'meta_keys' => array( 'custom_attributes' ),
				),
				array(
					'label'     => __( 'Breakpoints', 'wsform-siret' ),
					'meta_keys' => array( 'breakpoint_sizes' ),
					'class'     => array( 'wsf-fieldset-panel' ),
				),
			),
		);
	}
}
