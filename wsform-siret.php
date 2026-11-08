<?php
/**
 * Plugin Name:       Recherche SIRET pour WS Form
 * Plugin URI:        https://github.com/willybahuaud/wsform-siret
 * Description:       A WS Form field that finds a French company by name or SIRET, checks it and fills the form. Public government data, no API key.
 * Version:           0.2.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            Willy Bahuaud
 * Author URI:        https://wabeo.fr
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wsform-siret
 * Domain Path:       /languages
 * Update URI:        https://github.com/willybahuaud/wsform-siret
 *
 * @package WSForm_Siret
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WSFORM_SIRET_FILE', __FILE__ );
define( 'WSFORM_SIRET_PATH', plugin_dir_path( __FILE__ ) );

require_once WSFORM_SIRET_PATH . 'includes/class-autoloader.php';
WSForm_Siret\Autoloader::register();

add_action( 'init', 'wsform_siret_load_textdomain', 0 );
add_action( 'init', 'wsform_siret_boot_updater' );

/**
 * Charge les traductions embarquées.
 *
 * Le plugin n'est pas sur WordPress.org : aucun paquet de langue ne viendrait les fournir.
 *
 * @return void
 */
function wsform_siret_load_textdomain() {
	load_plugin_textdomain( 'wsform-siret', false, dirname( plugin_basename( WSFORM_SIRET_FILE ) ) . '/languages' );
}

/**
 * Branche les releases GitHub sur les mises à jour des extensions.
 *
 * @return void
 */
function wsform_siret_boot_updater() {
	$updater = new WSForm_Siret\Updater\Updater(
		'willybahuaud',
		'wsform-siret',
		WSFORM_SIRET_FILE,
		WSForm_Siret\Plugin::VERSION,
		__( 'Recherche SIRET pour WS Form', 'wsform-siret' )
	);
	$updater->boot();
}

// Les crochets ne passent que par des filtres de WS Form : sans lui, rien ne s'exécute.
WSForm_Siret\Plugin::boot();
