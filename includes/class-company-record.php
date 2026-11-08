<?php
/**
 * Lecture d'un établissement dans une réponse de l'API.
 *
 * @package WSForm_Siret
 */

namespace WSForm_Siret;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retrouve un SIRET parmi les entreprises renvoyées par l'API Recherche d'entreprises.
 *
 * Le serveur n'a besoin que de l'existence et de l'état : le reste des données
 * est lu par le navigateur, dans assets/js/wsfsiret-company.js.
 */
class Company_Record {

	const COMPANY_CEASED       = 'C';
	const ESTABLISHMENT_CLOSED = 'F';

	/**
	 * Cherche l'établissement d'un SIRET dans les résultats d'une recherche.
	 *
	 * Le siège et les établissements correspondants (matching_etablissements) sont
	 * deux listes distinctes : le SIRET cherché peut être dans l'une ou l'autre.
	 *
	 * @param mixed  $results Liste « results » de la réponse.
	 * @param string $siret   SIRET cherché.
	 * @return array{siret:string,siren:string,is_closed:bool}|null Null si le SIRET n'y est pas.
	 */
	public static function find_establishment( $results, $siret ) {
		foreach ( (array) $results as $company ) {
			if ( ! is_array( $company ) ) {
				continue;
			}

			foreach ( self::get_establishments( $company ) as $establishment ) {
				if ( is_array( $establishment ) && ( $establishment['siret'] ?? '' ) === $siret ) {
					return array(
						'siret'     => $siret,
						'siren'     => (string) ( $company['siren'] ?? '' ),
						'is_closed' => self::is_closed( $company, $establishment ),
					);
				}
			}
		}

		return null;
	}

	/**
	 * Retourne le siège suivi des établissements correspondants.
	 *
	 * @param array $company Entreprise renvoyée par l'API.
	 * @return array
	 */
	private static function get_establishments( array $company ) {
		$head_office = isset( $company['siege'] ) && is_array( $company['siege'] ) ? array( $company['siege'] ) : array();
		$matching    = isset( $company['matching_etablissements'] ) && is_array( $company['matching_etablissements'] ) ? $company['matching_etablissements'] : array();

		return array_merge( $head_office, $matching );
	}

	/**
	 * Indique si l'établissement est fermé, ou si l'entreprise a cessé son activité.
	 *
	 * L'API note « F » un établissement fermé et « C » une entreprise cessée.
	 *
	 * @param array $company       Entreprise.
	 * @param array $establishment Établissement.
	 * @return bool
	 */
	private static function is_closed( array $company, array $establishment ) {
		return self::COMPANY_CEASED === ( $company['etat_administratif'] ?? '' )
			|| self::ESTABLISHMENT_CLOSED === ( $establishment['etat_administratif'] ?? '' );
	}
}
