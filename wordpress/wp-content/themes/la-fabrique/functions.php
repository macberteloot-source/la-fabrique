<?php
/**
 * Fonctions du thème La Fabrique.
 *
 * Ce fichier ne fait que charger les modules du dossier inc/ :
 * - setup.php              supports du thème, menus, tailles d'images ;
 * - assets.php             feuilles de style, scripts, polices, favicon ;
 * - template-tags.php      fonctions d'affichage appelées par les gabarits ;
 * - template-functions.php filtres sur le balisage (classes, titres, hiérarchie) ;
 * - fabrique.php           petites données de la marque (lot de la semaine, horaires) ;
 * - recettes.php           affichage des recettes (plugin fabrique-recettes) ;
 * - woocommerce.php        personnalisation de la boutique par des hooks.
 *
 * Les données (recettes, fiches café, livraison) vivent dans les plugins : le thème
 * ne fait que les présenter et fonctionne aussi quand ces plugins sont désactivés.
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;

define( 'LA_FABRIQUE_VERSION', '1.0.0' );
define( 'LA_FABRIQUE_DIR', get_template_directory() );

require LA_FABRIQUE_DIR . '/inc/setup.php';
require LA_FABRIQUE_DIR . '/inc/assets.php';
require LA_FABRIQUE_DIR . '/inc/template-tags.php';
require LA_FABRIQUE_DIR . '/inc/template-functions.php';
require LA_FABRIQUE_DIR . '/inc/fabrique.php';
require LA_FABRIQUE_DIR . '/inc/recettes.php';

if ( class_exists( 'WooCommerce' ) ) {
	require LA_FABRIQUE_DIR . '/inc/woocommerce.php';
}
