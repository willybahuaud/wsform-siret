<?php
/**
 * Erreur de WordPress simulée pour les tests unitaires.
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Cette classe remplace celle de WordPress.
 *
 * @package WSForm_Siret\Tests
 */

/**
 * Erreur de WordPress, réduite à son message.
 */
class WP_Error {

	/**
	 * Message.
	 *
	 * @var string
	 */
	public $message;

	/**
	 * Constructeur.
	 *
	 * @param string $code    Code.
	 * @param string $message Message.
	 */
	public function __construct( $code = '', $message = '' ) {
		$this->message = $message;
	}
}
