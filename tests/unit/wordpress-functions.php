<?php
/**
 * Fonctions de WordPress simulées pour les tests unitaires.
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Ces fonctions remplacent celles de WordPress.
 *
 * @package WSForm_Siret\Tests
 */

/**
 * Indique si une valeur est une erreur.
 *
 * @param mixed $thing Valeur.
 * @return bool
 */
function is_wp_error( $thing ) {
	return $thing instanceof WP_Error;
}

/**
 * Lit un transient.
 *
 * @param string $key Clé.
 * @return mixed
 */
function get_transient( $key ) {
	return WSForm_Siret_Fake_WordPress::$transients[ $key ]['value'] ?? false;
}

/**
 * Enregistre un transient.
 *
 * @param string $key        Clé.
 * @param mixed  $value      Valeur.
 * @param int    $expiration Durée en secondes.
 * @return bool
 */
function set_transient( $key, $value, $expiration = 0 ) {
	WSForm_Siret_Fake_WordPress::$transients[ $key ] = array(
		'value'      => $value,
		'expiration' => $expiration,
	);

	return true;
}

/**
 * Note la requête et renvoie la réponse choisie par le test.
 *
 * @param string $url  Adresse.
 * @param array  $args Arguments.
 * @return array|WP_Error
 */
function wp_safe_remote_get( $url, $args = array() ) {
	WSForm_Siret_Fake_WordPress::$http_requests[] = array(
		'url'  => $url,
		'args' => $args,
	);

	return WSForm_Siret_Fake_WordPress::$http_response;
}

/**
 * Lit le code HTTP d'une réponse.
 *
 * @param array $response Réponse.
 * @return int|string
 */
function wp_remote_retrieve_response_code( $response ) {
	return $response['response']['code'] ?? '';
}

/**
 * Lit un en-tête d'une réponse.
 *
 * @param array  $response Réponse.
 * @param string $header   En-tête, en minuscules.
 * @return string
 */
function wp_remote_retrieve_header( $response, $header ) {
	return $response['headers'][ $header ] ?? '';
}

/**
 * Lit le corps d'une réponse.
 *
 * @param array $response Réponse.
 * @return string
 */
function wp_remote_retrieve_body( $response ) {
	return $response['body'] ?? '';
}

/**
 * Note une action déclenchée.
 *
 * @param string $hook_name Nom de l'action.
 * @param mixed  ...$args   Arguments.
 * @return void
 */
function do_action( $hook_name, ...$args ) {
	WSForm_Siret_Fake_WordPress::$actions[] = array_merge( array( $hook_name ), $args );
}
