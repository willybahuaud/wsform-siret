<?php
/**
 * Scénarios d'intégration, lancés sur un site local avec WS Form.
 *
 * Lancement : wp eval-file wp-content/plugins/wsform-siret/tests/integration/run.php --user=admin
 *
 * Aucune requête ne part vers GitHub ni WordPress.org : leurs réponses sont simulées.
 *
 * @package WSForm_Siret\Tests
 */

use WSForm_Siret\Field_Type;

require_once __DIR__ . '/class-lab-test.php';
require_once __DIR__ . '/updater-scenarios.php';

/**
 * Le champ est déclaré dans le groupe des services publics, avec ses réglages.
 *
 * @return void
 */
function wsform_siret_test_field_registered() {
	// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Filtres de WS Form.
	$field_types = apply_filters( 'wsf_config_field_types', array() );
	$meta_keys   = apply_filters( 'wsf_config_meta_keys', array(), 0 );
	// phpcs:enable

	WSForm_Siret_Lab_Test::check( isset( $field_types[ Field_Type::FIELD_GROUP ]['types'][ Field_Type::FIELD_TYPE ] ), 'field: type registered in the shared group' );
	foreach ( array( 'wsfsiret_field_mapping', 'wsfsiret_establishment', 'wsfsiret_reject_closed', 'wsfsiret_reject_unknown', 'wsfsiret_attribution', 'wsfsiret_text_invalid' ) as $meta_key ) {
		WSForm_Siret_Lab_Test::check( isset( $meta_keys[ $meta_key ] ), 'field: setting ' . $meta_key . ' declared' );
	}
}

add_filter( 'pre_http_request', array( 'WSForm_Siret_Fake_Github', 'answer' ), 10, 3 );

$wsform_siret_scenarios = array(
	'wsform_siret_test_field_registered',
	'wsform_siret_test_updater_newer_release',
	'wsform_siret_test_updater_up_to_date',
	'wsform_siret_test_updater_tested_branch',
	'wsform_siret_test_updater_missing_manifest',
	'wsform_siret_test_updater_foreign_host',
	'wsform_siret_test_updater_invalid_releases',
	'wsform_siret_test_updater_failures',
	'wsform_siret_test_updater_no_token',
);

foreach ( $wsform_siret_scenarios as $wsform_siret_scenario ) {
	$wsform_siret_scenario();
}

foreach ( WSForm_Siret_Lab_Test::$failures as $wsform_siret_failure ) {
	WP_CLI::warning( $wsform_siret_failure );
}

if ( WSForm_Siret_Lab_Test::$failures ) {
	WP_CLI::error( sprintf( '%d of %d checks failed.', count( WSForm_Siret_Lab_Test::$failures ), WSForm_Siret_Lab_Test::$assertions ) );
}

WP_CLI::success( sprintf( '%d checks passed.', WSForm_Siret_Lab_Test::$assertions ) );
