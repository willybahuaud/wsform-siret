<?php
/**
 * Réponses de l'API, réduites aux champs que lit le plugin.
 *
 * @package WSForm_Siret\Tests
 */

/**
 * Entreprises tirées de réponses réelles du 2026-11-08.
 */
class WSForm_Siret_Api_Fixtures {

	/**
	 * Entreprise active, cherchée par le SIRET de son siège.
	 *
	 * @return array
	 */
	public static function active_head_office() {
		return array(
			'siren'                   => '418166096',
			'nom_complet'             => 'OCTO-TECHNOLOGY',
			'etat_administratif'      => 'A',
			'siege'                   => self::establishment( '41816609600069', 'A', true ),
			'matching_etablissements' => array( self::establishment( '41816609600069', 'A', true ) ),
		);
	}

	/**
	 * Entreprise active, cherchée par le SIRET d'un établissement fermé.
	 *
	 * @return array
	 */
	public static function closed_establishment() {
		return array(
			'siren'                   => '328725940',
			'nom_complet'             => 'BOULANGERIE PATISSERIE ASSOCIEES ANGERS (BPAA)',
			'etat_administratif'      => 'A',
			'siege'                   => self::establishment( '32872594000030', 'A', true ),
			'matching_etablissements' => array( self::establishment( '32872594000014', 'F', false ) ),
		);
	}

	/**
	 * Entreprise cessée.
	 *
	 * @return array
	 */
	public static function ceased_company() {
		return array(
			'siren'                   => '790763601',
			'nom_complet'             => 'BOULANGERIE D ANGERS (LE BON PAIN)',
			'etat_administratif'      => 'C',
			'siege'                   => self::establishment( '79076360100016', 'A', true ),
			'matching_etablissements' => array(),
		);
	}

	/**
	 * Construit un établissement.
	 *
	 * @param string $siret      SIRET.
	 * @param string $state      État : A ou F.
	 * @param bool   $is_head    Siège ou non.
	 * @return array
	 */
	private static function establishment( $siret, $state, $is_head ) {
		return array(
			'siret'              => $siret,
			'etat_administratif' => $state,
			'est_siege'          => $is_head,
		);
	}
}
