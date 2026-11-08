<?php
/**
 * Tests du contrôle des numéros.
 *
 * @package WSForm_Siret\Tests
 */

use PHPUnit\Framework\TestCase;
use WSForm_Siret\Siret_Number;

/**
 * Couvre la clé de Luhn, l'exception de La Poste et les saisies avec espaces.
 */
class SiretNumberTest extends TestCase {

	/**
	 * SIRET et validité attendue.
	 *
	 * Les numéros de La Poste viennent de l'API, relevés le 2026-11-08.
	 *
	 * @return array<string,array{0:string,1:bool}>
	 */
	public function sirets() {
		return array(
			'valid key'                          => array( '41816609600069', true ),
			'wrong key'                          => array( '41816609600068', false ),
			'La Poste establishment'             => array( '35600000004769', true ),
			'La Poste establishment, other'      => array( '35600000049837', true ),
			'La Poste, digit sum not a multiple' => array( '35600000049838', false ),
			'La Poste head office follows Luhn'  => array( '35600000000048', true ),
			'La Poste, Luhn only is not enough'  => array( '35600000000014', false ),
			'all zeros'                          => array( '00000000000000', false ),
			'too short'                          => array( '4181660960006', false ),
			'too long'                           => array( '418166096000690', false ),
			'letters'                            => array( '4181660960006A', false ),
			'spaces are not normalized here'     => array( '418 166 096 00069', false ),
			'empty'                              => array( '', false ),
		);
	}

	/**
	 * Les SIRET sont contrôlés.
	 *
	 * @dataProvider sirets
	 *
	 * @param string $number   Numéro.
	 * @param bool   $expected Validité attendue.
	 * @return void
	 */
	public function test_is_valid_siret( $number, $expected ) {
		$this->assertSame( $expected, Siret_Number::is_valid_siret( $number ) );
	}

	/**
	 * Les SIREN sont contrôlés par Luhn, celui de La Poste compris.
	 *
	 * @return void
	 */
	public function test_is_valid_siren() {
		$this->assertTrue( Siret_Number::is_valid_siren( '418166096' ) );
		$this->assertTrue( Siret_Number::is_valid_siren( '356000000' ) );
		$this->assertFalse( Siret_Number::is_valid_siren( '418166097' ) );
		$this->assertFalse( Siret_Number::is_valid_siren( '000000000' ) );
		$this->assertFalse( Siret_Number::is_valid_siren( '41816609600069' ) );
	}

	/**
	 * Les espaces tapés ou collés disparaissent, y compris insécables.
	 *
	 * @return void
	 */
	public function test_normalize() {
		$this->assertSame( '41816609600069', Siret_Number::normalize( ' 418 166 096 00069 ' ) );
		$this->assertSame( '41816609600069', Siret_Number::normalize( "418\u{00A0}166\u{202F}096\t00069" ) );
		$this->assertSame( 'OCTO', Siret_Number::normalize( 'OCTO' ) );
		$this->assertSame( '', Siret_Number::normalize( array( '41816609600069' ) ) );
	}
}
