<?php
/**
 * Démarrage du plugin.
 *
 * @package WSForm_Siret
 */

namespace WSForm_Siret;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Branche les composants du plugin.
 */
class Plugin {

	const VERSION = '0.2.0';

	/**
	 * Enregistre les crochets de chaque composant.
	 *
	 * Valable avec WS Form LITE comme PRO : tous les crochets utilisés existent dans les deux.
	 *
	 * @return void
	 */
	public static function boot() {
		Field_Type::register_hooks();
		Field_Settings::register_hooks();
		Ign_Link::register_hooks();
		Assets::register_hooks();
		Submission_Validation::register_hooks();
	}

	/**
	 * Retourne l'URL d'un fichier du plugin.
	 *
	 * @param string $relative_path Chemin depuis le dossier du plugin.
	 * @return string
	 */
	public static function get_url( $relative_path ) {
		return plugins_url( $relative_path, WSFORM_SIRET_FILE );
	}
}
