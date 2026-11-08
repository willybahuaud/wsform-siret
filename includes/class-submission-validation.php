<?php
/**
 * Validation côté serveur du SIRET.
 *
 * @package WSForm_Siret
 */

namespace WSForm_Siret;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Le contrôle du navigateur se contourne : chaque soumission est revérifiée ici.
 */
class Submission_Validation {

	const ERROR_INVALID = 'invalid';
	const ERROR_CLOSED  = 'closed';
	const ERROR_UNKNOWN = 'unknown';

	/**
	 * Branche la validation sur WS Form.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_filter( 'wsf_submit_field_validate', array( __CLASS__, 'validate_field' ), 10, 6 );
		add_action( 'wsform_siret_lookup_unavailable', array( __CLASS__, 'log_unavailable_lookup' ) );
	}

	/**
	 * Note dans le journal de débogage un SIRET accepté sans vérification.
	 *
	 * Rien n'est écrit hors WP_DEBUG_LOG : le journal n'existe pas en production.
	 *
	 * @param string $siret SIRET accepté.
	 * @return void
	 */
	public static function log_unavailable_lookup( $siret ) {
		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log( 'wsform-siret: SIRET ' . $siret . ' accepted without checking, the Recherche d\'entreprises API did not answer.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Only with WP_DEBUG_LOG.
		}
	}

	/**
	 * Ajoute une erreur si un champ SIRET ne passe pas les contrôles.
	 *
	 * Une valeur vide passe : le caractère obligatoire est contrôlé par le cœur.
	 * Appelé une fois par ligne dans une section répétable.
	 *
	 * @param array      $actions                  Erreurs de validation déjà relevées.
	 * @param int|string $field_id                 Identifiant du champ.
	 * @param mixed      $field_value              Valeur envoyée.
	 * @param int|false  $section_repeatable_index Ligne de section répétable.
	 * @param string     $post_mode                Mode d'envoi.
	 * @param object     $submit                   Soumission.
	 * @return array
	 */
	public static function validate_field( $actions, $field_id, $field_value, $section_repeatable_index, $post_mode, $submit ) {
		if ( '' === $field_value ) {
			return $actions;
		}

		$field = self::get_siret_field( $field_id, $submit );

		if ( null === $field ) {
			return $actions;
		}

		$error = self::get_error(
			$field_value,
			array(
				'reject_closed'  => 'on' === \WS_Form_Common::get_object_meta_value( $field, 'wsfsiret_reject_closed', '' ),
				'reject_unknown' => 'on' === \WS_Form_Common::get_object_meta_value( $field, 'wsfsiret_reject_unknown', '' ),
			)
		);

		if ( '' !== $error ) {
			// Le cœur transforme une chaîne ajoutée à ce tableau en message sous le champ.
			$actions[] = Field_Settings::get_field_text( $field, $error );
		}

		return $actions;
	}

	/**
	 * Retourne l'erreur d'une valeur, ou une chaîne vide si elle est acceptée.
	 *
	 * L'API n'est appelée que si un contrôle la demande, et après la clé de contrôle.
	 * Si elle ne répond pas, la valeur passe : un service extérieur en panne ne doit
	 * pas bloquer le formulaire. Le crochet wsform_siret_lookup_unavailable le signale.
	 *
	 * @param mixed $field_value Valeur envoyée.
	 * @param array $options     Contrôles actifs : reject_closed, reject_unknown.
	 * @return string Une des constantes ERROR_*, ou une chaîne vide.
	 */
	public static function get_error( $field_value, array $options ) {
		$siret = Siret_Number::normalize( $field_value );

		if ( ! Siret_Number::is_valid_siret( $siret ) ) {
			return self::ERROR_INVALID;
		}

		if ( empty( $options['reject_closed'] ) && empty( $options['reject_unknown'] ) ) {
			return '';
		}

		$lookup = Company_Api::lookup( $siret );

		if ( Company_Api::UNAVAILABLE === $lookup['status'] ) {
			/**
			 * Signale qu'un SIRET a été accepté sans pouvoir être vérifié auprès de l'API.
			 *
			 * @param string $siret SIRET accepté.
			 */
			do_action( 'wsform_siret_lookup_unavailable', $siret );

			return '';
		}

		if ( Company_Api::NOT_FOUND === $lookup['status'] ) {
			return empty( $options['reject_unknown'] ) ? '' : self::ERROR_UNKNOWN;
		}

		return ! empty( $options['reject_closed'] ) && $lookup['establishment']['is_closed'] ? self::ERROR_CLOSED : '';
	}

	/**
	 * Retourne le champ s'il est de notre type : le filtre ne transmet pas le type.
	 *
	 * @param int|string $field_id Identifiant du champ.
	 * @param object     $submit   Soumission.
	 * @return object|null
	 */
	private static function get_siret_field( $field_id, $submit ) {
		$fields = \WS_Form_Common::get_fields_from_form( $submit->form_object );

		if ( ! isset( $fields[ $field_id ]->type ) || Field_Type::FIELD_TYPE !== $fields[ $field_id ]->type ) {
			return null;
		}

		return $fields[ $field_id ];
	}
}
