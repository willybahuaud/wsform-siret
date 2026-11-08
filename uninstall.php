<?php
/**
 * Supprime les caches du plugin à sa désinstallation.
 *
 * Les SIRET enregistrés dans les soumissions restent : ils appartiennent au site.
 *
 * @package WSForm_Siret
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Même clé que WSForm_Siret\Updater\Github_Release::get_cache_key().
delete_site_transient( 'wsform_siret_release_' . md5( 'willybahuaud/wsform-siret' ) );

// Réponses de l'API mises en cache par WSForm_Siret\Company_Api, une par SIRET.
delete_transient( 'wsform_siret_api_paused' );
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( '_transient_wsform_siret_company_' ) . '%', $wpdb->esc_like( '_transient_timeout_wsform_siret_company_' ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
