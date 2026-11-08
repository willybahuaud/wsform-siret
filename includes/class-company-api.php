<?php
/**
 * Appel de l'API Recherche d'entreprises depuis le serveur.
 *
 * @package WSForm_Siret
 */

namespace WSForm_Siret;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Vérifie un SIRET auprès de l'API, à la soumission d'un formulaire.
 *
 * L'API limite chaque IP à 7 requêtes par seconde, sans débit garanti : les
 * réponses sont gardées une heure, et un 429 suspend les appels le temps demandé.
 */
class Company_Api {

	const SEARCH_URL     = 'https://recherche-entreprises.api.gouv.fr/search';
	const CACHE_PREFIX   = 'wsform_siret_company_';
	const PAUSE_KEY      = 'wsform_siret_api_paused';
	const CACHE_DURATION = 3600;
	const DEFAULT_PAUSE  = 10;
	const MAX_PAUSE      = 300;
	const TIMEOUT        = 3;

	const FOUND       = 'found';
	const NOT_FOUND   = 'not_found';
	const UNAVAILABLE = 'unavailable';

	/**
	 * Cherche l'établissement d'un SIRET.
	 *
	 * @param string $siret SIRET valide, sans espace.
	 * @return array{status:string,establishment:array|null}
	 *     status vaut found, not_found (absent de l'API, peut-être non diffusible)
	 *     ou unavailable (API injoignable, en erreur ou en limite de débit).
	 */
	public static function lookup( $siret ) {
		$cached = get_transient( self::CACHE_PREFIX . $siret );

		if ( is_array( $cached ) && isset( $cached['status'] ) ) {
			return $cached;
		}

		if ( get_transient( self::PAUSE_KEY ) ) {
			return self::build_result( self::UNAVAILABLE );
		}

		$lookup = self::request( $siret );

		if ( self::UNAVAILABLE !== $lookup['status'] ) {
			set_transient( self::CACHE_PREFIX . $siret, $lookup, self::CACHE_DURATION );
		}

		return $lookup;
	}

	/**
	 * Interroge l'API.
	 *
	 * @param string $siret SIRET.
	 * @return array{status:string,establishment:array|null}
	 */
	private static function request( $siret ) {
		$response = wp_safe_remote_get(
			self::SEARCH_URL . '?' . http_build_query( self::get_query( $siret ), '', '&' ),
			array(
				'timeout' => self::TIMEOUT,
				'headers' => array(
					'Accept'     => 'application/json',
					'User-Agent' => 'wsform-siret/' . Plugin::VERSION . ' (+https://github.com/willybahuaud/wsform-siret)',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return self::build_result( self::UNAVAILABLE );
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );

		if ( 429 === $status_code ) {
			self::pause( wp_remote_retrieve_header( $response, 'retry-after' ) );
		}

		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $status_code || ! is_array( $body ) || ! isset( $body['results'] ) || ! is_array( $body['results'] ) ) {
			return self::build_result( self::UNAVAILABLE );
		}

		$establishment = Company_Record::find_establishment( $body['results'], $siret );

		return self::build_result( null === $establishment ? self::NOT_FOUND : self::FOUND, $establishment );
	}

	/**
	 * Construit les paramètres de la recherche.
	 *
	 * La recherche par SIRET passe par le même paramètre q que la recherche par nom.
	 * La réponse minimale suffit : seuls le siège et l'établissement trouvé servent.
	 *
	 * @param string $siret SIRET.
	 * @return array
	 */
	private static function get_query( $siret ) {
		return array(
			'q'        => $siret,
			'minimal'  => 'true',
			'include'  => 'siege,matching_etablissements',
			'per_page' => 1,
		);
	}

	/**
	 * Suspend les appels après un 429, le temps indiqué par Retry-After.
	 *
	 * @param mixed $retry_after Valeur de l'en-tête.
	 * @return void
	 */
	private static function pause( $retry_after ) {
		$seconds = is_numeric( $retry_after ) ? (int) $retry_after : self::DEFAULT_PAUSE;

		set_transient( self::PAUSE_KEY, 1, max( 1, min( $seconds, self::MAX_PAUSE ) ) );
	}

	/**
	 * Construit le résultat d'une recherche.
	 *
	 * @param string     $status        Statut.
	 * @param array|null $establishment Établissement trouvé.
	 * @return array{status:string,establishment:array|null}
	 */
	private static function build_result( $status, $establishment = null ) {
		return array(
			'status'        => $status,
			'establishment' => $establishment,
		);
	}
}
