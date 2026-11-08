<?php
/**
 * Tests de la validation à la soumission, API simulée.
 *
 * @package WSForm_Siret\Tests
 */

use PHPUnit\Framework\TestCase;
use WSForm_Siret\Submission_Validation;

require_once __DIR__ . '/class-api-fixtures.php';

/**
 * Couvre la clé de contrôle et les deux réglages qui interrogent l'API.
 */
class SubmissionValidationTest extends TestCase {

	const ALL_CHECKS = array(
		'reject_closed'  => true,
		'reject_unknown' => true,
	);

	/**
	 * Repart d'un état vide.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		WSForm_Siret_Fake_WordPress::reset();
	}

	/**
	 * Simule les entreprises renvoyées par l'API.
	 *
	 * @param array $companies Entreprises.
	 * @return void
	 */
	private function answer_with( array $companies ) {
		WSForm_Siret_Fake_WordPress::$http_response = WSForm_Siret_Fake_WordPress::response( 200, array( 'results' => $companies ) );
	}

	/**
	 * Une valeur mal formée est refusée sans appel à l'API.
	 *
	 * @return void
	 */
	public function test_invalid_values_are_refused_without_calling_the_api() {
		foreach ( array( '41816609600068', 'OCTO TECHNOLOGY', '418166096', '<script>', '00000000000000' ) as $value ) {
			$this->assertSame( Submission_Validation::ERROR_INVALID, Submission_Validation::get_error( $value, self::ALL_CHECKS ), $value );
		}

		$this->assertCount( 0, WSForm_Siret_Fake_WordPress::$http_requests );
	}

	/**
	 * Sans réglage actif, un SIRET valide passe sans appel à l'API, espaces compris.
	 *
	 * @return void
	 */
	public function test_valid_siret_without_checks() {
		$this->assertSame( '', Submission_Validation::get_error( '418 166 096 00069', array() ) );
		$this->assertCount( 0, WSForm_Siret_Fake_WordPress::$http_requests );
	}

	/**
	 * Un établissement fermé est refusé quand le réglage est actif, et seulement alors.
	 *
	 * @return void
	 */
	public function test_closed_establishment() {
		$this->answer_with( array( WSForm_Siret_Api_Fixtures::closed_establishment() ) );

		$this->assertSame( Submission_Validation::ERROR_CLOSED, Submission_Validation::get_error( '32872594000014', array( 'reject_closed' => true ) ) );
		$this->assertSame( '', Submission_Validation::get_error( '32872594000014', array( 'reject_unknown' => true ) ) );
	}

	/**
	 * Une entreprise cessée est refusée quand le réglage est actif.
	 *
	 * @return void
	 */
	public function test_ceased_company() {
		$this->answer_with( array( WSForm_Siret_Api_Fixtures::ceased_company() ) );

		$this->assertSame( Submission_Validation::ERROR_CLOSED, Submission_Validation::get_error( '79076360100016', self::ALL_CHECKS ) );
	}

	/**
	 * Une entreprise active passe tous les contrôles.
	 *
	 * @return void
	 */
	public function test_active_company() {
		$this->answer_with( array( WSForm_Siret_Api_Fixtures::active_head_office() ) );

		$this->assertSame( '', Submission_Validation::get_error( '41816609600069', self::ALL_CHECKS ) );
	}

	/**
	 * Un SIRET absent de l'API ne bloque que si le réglage le demande.
	 *
	 * @return void
	 */
	public function test_unknown_siret() {
		$this->answer_with( array() );

		$this->assertSame( '', Submission_Validation::get_error( '99999999900009', array( 'reject_closed' => true ) ) );
		$this->assertSame( Submission_Validation::ERROR_UNKNOWN, Submission_Validation::get_error( '99999999900009', self::ALL_CHECKS ) );
	}

	/**
	 * API coupée ou en 429 : le SIRET passe, et l'action le signale.
	 *
	 * @return void
	 */
	public function test_unavailable_api_lets_the_form_through() {
		$answers = array(
			new WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' ),
			WSForm_Siret_Fake_WordPress::response( 429, '', array( 'retry-after' => '5' ) ),
		);

		foreach ( $answers as $answer ) {
			WSForm_Siret_Fake_WordPress::reset();
			WSForm_Siret_Fake_WordPress::$http_response = $answer;

			$this->assertSame( '', Submission_Validation::get_error( '41816609600069', self::ALL_CHECKS ) );
			$this->assertSame( array( array( 'wsform_siret_lookup_unavailable', '41816609600069' ) ), WSForm_Siret_Fake_WordPress::$actions );
		}
	}
}
