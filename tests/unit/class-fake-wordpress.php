<?php
/**
 * État simulé de WordPress pour les tests unitaires.
 *
 * @package WSForm_Siret\Tests
 */

/**
 * Transients, requêtes HTTP et actions, réinitialisés avant chaque test.
 */
class WSForm_Siret_Fake_WordPress {

	/**
	 * Transients enregistrés, avec leur durée.
	 *
	 * @var array<string,array{value:mixed,expiration:int}>
	 */
	public static $transients = array();

	/**
	 * Réponse à renvoyer : tableau de WordPress, ou WP_Error.
	 *
	 * @var array|WP_Error
	 */
	public static $http_response = array();

	/**
	 * Requêtes envoyées : adresse et arguments.
	 *
	 * @var array<int,array{url:string,args:array}>
	 */
	public static $http_requests = array();

	/**
	 * Actions déclenchées, avec leurs arguments.
	 *
	 * @var array<int,array>
	 */
	public static $actions = array();

	/**
	 * Remet l'état à zéro.
	 *
	 * @return void
	 */
	public static function reset() {
		self::$transients    = array();
		self::$http_response = self::response( 200, array( 'results' => array() ) );
		self::$http_requests = array();
		self::$actions       = array();
	}

	/**
	 * Construit une réponse HTTP au format de WordPress.
	 *
	 * @param int          $status_code Code HTTP.
	 * @param array|string $body        Corps, encodé en JSON si c'est un tableau.
	 * @param array        $headers     En-têtes, en minuscules.
	 * @return array
	 */
	public static function response( $status_code, $body, $headers = array() ) {
		return array(
			'response' => array( 'code' => $status_code ),
			'headers'  => $headers,
			'body'     => is_array( $body ) ? json_encode( $body ) : $body, // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- No WordPress here.
		);
	}
}
