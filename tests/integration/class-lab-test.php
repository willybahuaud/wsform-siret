<?php
/**
 * Assertions des scénarios d'intégration, lancés dans un site WordPress.
 *
 * @package WSForm_Siret\Tests
 */

/**
 * Compte les vérifications et garde celles qui échouent.
 *
 * Pas de PHPUnit ici : WS Form ne s'installe pas dans un lanceur de tests, les
 * scénarios tournent avec « wp eval-file » sur un site local.
 */
class WSForm_Siret_Lab_Test {

	/**
	 * Vérifications en échec.
	 *
	 * @var array<int,string>
	 */
	public static $failures = array();

	/**
	 * Nombre de vérifications.
	 *
	 * @var int
	 */
	public static $assertions = 0;

	/**
	 * Vérifie une condition.
	 *
	 * @param bool   $condition   Condition.
	 * @param string $description Ce qui est vérifié.
	 * @return void
	 */
	public static function check( $condition, $description ) {
		++self::$assertions;
		if ( ! $condition ) {
			self::$failures[] = $description;
		}
	}

	/**
	 * Vérifie que deux valeurs sont identiques.
	 *
	 * @param mixed  $expected    Valeur attendue.
	 * @param mixed  $actual      Valeur obtenue.
	 * @param string $description Ce qui est vérifié.
	 * @return void
	 */
	public static function same( $expected, $actual, $description ) {
		self::check( $expected === $actual, sprintf( '%s (expected %s, got %s)', $description, wp_json_encode( $expected ), wp_json_encode( $actual ) ) );
	}
}
