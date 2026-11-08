<?php
/**
 * Contrôle des numéros SIREN et SIRET.
 *
 * @package WSForm_Siret
 */

namespace WSForm_Siret;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Vérifie la clé de contrôle d'un numéro, sans appel réseau.
 *
 * Même règle que assets/js/wsfsiret-number.js : le navigateur et le serveur
 * doivent refuser exactement les mêmes numéros.
 */
class Siret_Number {

	/**
	 * SIREN de La Poste. Faute de numéros disponibles, ses SIRET ne suivent pas Luhn :
	 * la somme de leurs chiffres est un multiple de 5. Règle reprise de python-stdnum,
	 * et vérifiée sur 91 SIRET de La Poste renvoyés par l'API le 2026-11-08.
	 */
	const LA_POSTE_SIREN = '356000000';

	/**
	 * Siège de La Poste : seul de ses SIRET à suivre Luhn.
	 */
	const LA_POSTE_HEAD_OFFICE = '35600000000048';

	/**
	 * Retire les espaces qu'un visiteur tape ou colle entre les groupes de chiffres.
	 *
	 * @param mixed $value Valeur saisie.
	 * @return string
	 */
	public static function normalize( $value ) {
		return is_string( $value ) ? (string) preg_replace( '/[\s\x{00A0}\x{202F}]+/u', '', $value ) : '';
	}

	/**
	 * Indique si un SIRET est bien formé.
	 *
	 * @param string $number SIRET, sans espace.
	 * @return bool
	 */
	public static function is_valid_siret( $number ) {
		if ( ! self::is_digits( $number, 14 ) ) {
			return false;
		}

		if ( 0 === strpos( $number, self::LA_POSTE_SIREN ) && self::LA_POSTE_HEAD_OFFICE !== $number ) {
			return 0 === array_sum( str_split( $number ) ) % 5;
		}

		return self::has_valid_luhn_key( $number );
	}

	/**
	 * Indique si un SIREN est bien formé.
	 *
	 * @param string $number SIREN, sans espace.
	 * @return bool
	 */
	public static function is_valid_siren( $number ) {
		return self::is_digits( $number, 9 ) && self::has_valid_luhn_key( $number );
	}

	/**
	 * Vérifie la longueur et refuse le numéro nul, que Luhn laisserait passer.
	 *
	 * @param mixed $number Numéro.
	 * @param int   $length Longueur attendue.
	 * @return bool
	 */
	private static function is_digits( $number, $length ) {
		return is_string( $number ) && 1 === preg_match( '/^\d{' . $length . '}$/', $number ) && '' !== trim( $number, '0' );
	}

	/**
	 * Applique l'algorithme de Luhn : un chiffre sur deux, en partant de la droite, est doublé.
	 *
	 * @param string $number Numéro.
	 * @return bool
	 */
	private static function has_valid_luhn_key( $number ) {
		$sum    = 0;
		$digits = array_reverse( str_split( $number ) );

		foreach ( $digits as $position => $digit ) {
			$value = (int) $digit * ( 1 === $position % 2 ? 2 : 1 );
			$sum  += $value > 9 ? $value - 9 : $value;
		}

		return 0 === $sum % 10;
	}
}
