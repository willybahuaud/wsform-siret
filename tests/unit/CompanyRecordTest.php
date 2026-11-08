<?php
/**
 * Tests de la lecture d'un établissement.
 *
 * @package WSForm_Siret\Tests
 */

use PHPUnit\Framework\TestCase;
use WSForm_Siret\Company_Record;

require_once __DIR__ . '/class-api-fixtures.php';

/**
 * Couvre le siège, les établissements correspondants et les deux façons d'être fermé.
 */
class CompanyRecordTest extends TestCase {

	/**
	 * Le siège est trouvé et actif.
	 *
	 * @return void
	 */
	public function test_finds_active_head_office() {
		$record = Company_Record::find_establishment( array( WSForm_Siret_Api_Fixtures::active_head_office() ), '41816609600069' );

		$this->assertSame(
			array(
				'siret'     => '41816609600069',
				'siren'     => '418166096',
				'is_closed' => false,
			),
			$record
		);
	}

	/**
	 * Un établissement secondaire fermé est trouvé dans matching_etablissements.
	 *
	 * @return void
	 */
	public function test_finds_closed_establishment() {
		$record = Company_Record::find_establishment( array( WSForm_Siret_Api_Fixtures::closed_establishment() ), '32872594000014' );

		$this->assertTrue( $record['is_closed'] );
	}

	/**
	 * Le siège d'une entreprise active reste ouvert, même si un autre établissement est fermé.
	 *
	 * @return void
	 */
	public function test_head_office_of_company_with_closed_establishment() {
		$record = Company_Record::find_establishment( array( WSForm_Siret_Api_Fixtures::closed_establishment() ), '32872594000030' );

		$this->assertFalse( $record['is_closed'] );
	}

	/**
	 * Une entreprise cessée ferme tous ses établissements.
	 *
	 * @return void
	 */
	public function test_ceased_company_is_closed() {
		$record = Company_Record::find_establishment( array( WSForm_Siret_Api_Fixtures::ceased_company() ), '79076360100016' );

		$this->assertTrue( $record['is_closed'] );
	}

	/**
	 * Un SIRET absent, ou une réponse mal formée, ne donne rien.
	 *
	 * @return void
	 */
	public function test_missing_or_malformed() {
		$this->assertNull( Company_Record::find_establishment( array( WSForm_Siret_Api_Fixtures::active_head_office() ), '99999999900009' ) );
		$this->assertNull( Company_Record::find_establishment( array(), '41816609600069' ) );
		$this->assertNull( Company_Record::find_establishment( 'results', '41816609600069' ) );
		$this->assertNull( Company_Record::find_establishment( array( 'nope', array( 'siege' => 'nope' ) ), '41816609600069' ) );
	}
}
