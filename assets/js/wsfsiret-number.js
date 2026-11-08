/**
 * Contrôle des numéros SIREN et SIRET, sans appel réseau.
 *
 * Même règle que includes/class-siret-number.php : le navigateur et le serveur
 * doivent refuser exactement les mêmes numéros.
 *
 * @package WSForm_Siret
 */

( function wsfsiretNumberModule() {
	'use strict';

	// Faute de numéros disponibles, les SIRET de La Poste ne suivent pas Luhn,
	// sauf son siège : la somme de leurs chiffres est un multiple de 5.
	const LA_POSTE_SIREN = '356000000';
	const LA_POSTE_HEAD_OFFICE = '35600000000048';

	/**
	 * Retire les espaces qu'un visiteur tape ou colle entre les groupes de chiffres.
	 *
	 * @param {string} value Valeur saisie.
	 * @return {string} Valeur sans espace.
	 */
	function normalize( value ) {
		return String( value ).replace( /[\s  ]+/g, '' );
	}

	/**
	 * Indique si une saisie ressemble à un numéro plutôt qu'à un nom.
	 *
	 * @param {string} value Valeur saisie.
	 * @return {boolean} Vrai si elle ne contient que des chiffres et des espaces.
	 */
	function isNumberLike( value ) {
		return /^[\d\s  ]+$/.test( value ) && /\d/.test( value );
	}

	/**
	 * Vérifie la longueur et refuse le numéro nul, que Luhn laisserait passer.
	 *
	 * @param {string} number Numéro.
	 * @param {number} length Longueur attendue.
	 * @return {boolean} Vrai si le format est bon.
	 */
	function isDigits( number, length ) {
		return new RegExp( '^\\d{' + length + '}$' ).test( number ) && /[1-9]/.test( number );
	}

	/**
	 * Additionne un chiffre de la clé de Luhn.
	 *
	 * @param {number} sum      Somme en cours.
	 * @param {string} digit    Chiffre.
	 * @param {number} position Position depuis la droite.
	 * @return {number} Nouvelle somme.
	 */
	function addLuhnDigit( sum, digit, position ) {
		const value = Number( digit ) * ( 1 === position % 2 ? 2 : 1 );

		return sum + ( value > 9 ? value - 9 : value );
	}

	/**
	 * Applique l'algorithme de Luhn.
	 *
	 * @param {string} number Numéro.
	 * @return {boolean} Vrai si la clé est bonne.
	 */
	function hasValidLuhnKey( number ) {
		return 0 === number.split( '' ).reverse().reduce( addLuhnDigit, 0 ) % 10;
	}

	/**
	 * Additionne deux chiffres.
	 *
	 * @param {number} sum   Somme en cours.
	 * @param {string} digit Chiffre.
	 * @return {number} Nouvelle somme.
	 */
	function addDigit( sum, digit ) {
		return sum + Number( digit );
	}

	/**
	 * Indique si un SIRET est bien formé.
	 *
	 * @param {string} number SIRET, sans espace.
	 * @return {boolean} Vrai s'il est valide.
	 */
	function isValidSiret( number ) {
		if ( ! isDigits( number, 14 ) ) {
			return false;
		}

		if ( number.startsWith( LA_POSTE_SIREN ) && LA_POSTE_HEAD_OFFICE !== number ) {
			return 0 === number.split( '' ).reduce( addDigit, 0 ) % 5;
		}

		return hasValidLuhnKey( number );
	}

	/**
	 * Indique si un SIREN est bien formé.
	 *
	 * @param {string} number SIREN, sans espace.
	 * @return {boolean} Vrai s'il est valide.
	 */
	function isValidSiren( number ) {
		return isDigits( number, 9 ) && hasValidLuhnKey( number );
	}

	/**
	 * Affiche un SIRET par groupes, comme sur un Kbis : 418 166 096 00069.
	 *
	 * @param {string} number SIRET.
	 * @return {string} SIRET groupé.
	 */
	function format( number ) {
		return number.replace( /^(\d{3})(\d{3})(\d{3})(\d{5})$/, '$1 $2 $3 $4' );
	}

	window.wsfsiretNumber = { normalize, isNumberLike, isValidSiret, isValidSiren, format };
}() );
