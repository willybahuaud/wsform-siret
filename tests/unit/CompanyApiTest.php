<?php
/**
 * Tests de l'appel à l'API, réponses simulées.
 *
 * @package WSForm_Siret\Tests
 */

use PHPUnit\Framework\TestCase;
use WSForm_Siret\Company_Api;

require_once __DIR__ . '/class-api-fixtures.php';

/**
 * Couvre le cache, la limite de débit et les pannes.
 */
class CompanyApiTest extends TestCase {

	/**
	 * Repart d'un état vide.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		WSForm_Siret_Fake_WordPress::reset();
	}

	/**
	 * Simule la réponse de l'API.
	 *
	 * @param int          $status_code Code HTTP.
	 * @param array|string $body        Corps.
	 * @param array        $headers     En-têtes.
	 * @return void
	 */
	private function answer( $status_code, $body, $headers = array() ) {
		WSForm_Siret_Fake_WordPress::$http_response = WSForm_Siret_Fake_WordPress::response( $status_code, $body, $headers );
	}

	/**
	 * Un SIRET trouvé est mis en cache une heure : le second appel ne part pas.
	 *
	 * @return void
	 */
	public function test_found_and_cached() {
		$this->answer( 200, array( 'results' => array( WSForm_Siret_Api_Fixtures::active_head_office() ) ) );

		$lookup = Company_Api::lookup( '41816609600069' );
		Company_Api::lookup( '41816609600069' );

		$this->assertSame( Company_Api::FOUND, $lookup['status'] );
		$this->assertFalse( $lookup['establishment']['is_closed'] );
		$this->assertCount( 1, WSForm_Siret_Fake_WordPress::$http_requests );
		$this->assertSame( 3600, WSForm_Siret_Fake_WordPress::$transients['wsform_siret_company_41816609600069']['expiration'] );
	}

	/**
	 * La requête cherche le SIRET, en réponse minimale, avec un User-Agent explicite.
	 *
	 * @return void
	 */
	public function test_request() {
		Company_Api::lookup( '41816609600069' );

		$request = WSForm_Siret_Fake_WordPress::$http_requests[0];
		parse_str( (string) parse_url( $request['url'], PHP_URL_QUERY ), $query ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- No WordPress here.

		$this->assertStringStartsWith( 'https://recherche-entreprises.api.gouv.fr/search?', $request['url'] );
		$this->assertSame( '41816609600069', $query['q'] );
		$this->assertSame( 'true', $query['minimal'] );
		$this->assertStringStartsWith( 'wsform-siret/', $request['args']['headers']['User-Agent'] );
		$this->assertLessThanOrEqual( 5, $request['args']['timeout'] );
	}

	/**
	 * Un SIRET absent de l'API est aussi mis en cache.
	 *
	 * @return void
	 */
	public function test_not_found_is_cached() {
		$this->answer( 200, array( 'results' => array() ) );

		$this->assertSame( Company_Api::NOT_FOUND, Company_Api::lookup( '99999999900009' )['status'] );
		Company_Api::lookup( '99999999900009' );
		$this->assertCount( 1, WSForm_Siret_Fake_WordPress::$http_requests );
	}

	/**
	 * Après un 429, plus aucun appel ne part pendant la durée de Retry-After.
	 *
	 * @return void
	 */
	public function test_rate_limit_pauses_calls() {
		$this->answer( 429, 'Too Many Requests', array( 'retry-after' => '30' ) );

		$this->assertSame( Company_Api::UNAVAILABLE, Company_Api::lookup( '41816609600069' )['status'] );
		$this->assertSame( 30, WSForm_Siret_Fake_WordPress::$transients['wsform_siret_api_paused']['expiration'] );

		$this->assertSame( Company_Api::UNAVAILABLE, Company_Api::lookup( '32872594000014' )['status'] );
		$this->assertCount( 1, WSForm_Siret_Fake_WordPress::$http_requests );
	}

	/**
	 * Un Retry-After absent ou démesuré reste borné.
	 *
	 * @return void
	 */
	public function test_rate_limit_pause_is_bounded() {
		$this->answer( 429, '' );
		Company_Api::lookup( '41816609600069' );
		$this->assertSame( 10, WSForm_Siret_Fake_WordPress::$transients['wsform_siret_api_paused']['expiration'] );

		WSForm_Siret_Fake_WordPress::reset();
		$this->answer( 429, '', array( 'retry-after' => '86400' ) );
		Company_Api::lookup( '41816609600069' );
		$this->assertSame( 300, WSForm_Siret_Fake_WordPress::$transients['wsform_siret_api_paused']['expiration'] );
	}

	/**
	 * Une panne n'est pas mise en cache : l'appel suivant retente.
	 *
	 * @return void
	 */
	public function test_failures_are_not_cached() {
		$failures = array(
			new WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' ),
			WSForm_Siret_Fake_WordPress::response( 500, 'Internal Server Error' ),
			WSForm_Siret_Fake_WordPress::response( 200, '<html>maintenance</html>' ),
			WSForm_Siret_Fake_WordPress::response( 200, array( 'erreur' => 'oops' ) ),
		);

		foreach ( $failures as $failure ) {
			WSForm_Siret_Fake_WordPress::$http_response = $failure;
			$this->assertSame( Company_Api::UNAVAILABLE, Company_Api::lookup( '41816609600069' )['status'] );
		}

		$this->assertCount( count( $failures ), WSForm_Siret_Fake_WordPress::$http_requests );
		$this->assertArrayNotHasKey( 'wsform_siret_company_41816609600069', WSForm_Siret_Fake_WordPress::$transients );
	}
}
