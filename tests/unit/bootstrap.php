<?php
/**
 * Amorce des tests unitaires, sans WordPress.
 *
 * Les quelques fonctions de WordPress utilisées sont simulées par Fake_WordPress :
 * transients en mémoire, réponses HTTP choisies par le test.
 *
 * @package WSForm_Siret\Tests
 */

define( 'ABSPATH', __DIR__ . '/' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals -- WordPress constant the plugin files check.

require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';
require_once dirname( __DIR__, 2 ) . '/includes/class-autoloader.php';
require_once __DIR__ . '/class-fake-wordpress.php';
require_once __DIR__ . '/class-wp-error.php';
require_once __DIR__ . '/wordpress-functions.php';

WSForm_Siret\Autoloader::register();
