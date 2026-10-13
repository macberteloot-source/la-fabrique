<?php
/**
 * Réglages de base du thème : supports, menus, tailles d'images.
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;

/**
 * Déclare les fonctionnalités du thème.
 */
function la_fabrique_setup() {
	load_theme_textdomain( 'la-fabrique', LA_FABRIQUE_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
	);
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 60,
			'width'       => 240,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	// L'éditeur reprend les polices et les couleurs du site (voir aussi theme.json).
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/editeur.css' );

	register_nav_menus(
		array(
			'principal'    => __( 'Menu principal', 'la-fabrique' ),
			'pied-de-page' => __( 'Menu du pied de page', 'la-fabrique' ),
		)
	);

	// Illustrations des recettes en 3:2 (cartes et en-tête de la fiche).
	add_image_size( 'la-fabrique-recette', 900, 600, true );
	add_image_size( 'la-fabrique-recette-grande', 1500, 1000, true );
}
add_action( 'after_setup_theme', 'la_fabrique_setup' );

/**
 * Largeur du contenu (oEmbed, images insérées) : la colonne de lecture.
 */
function la_fabrique_largeur_contenu() {
	$GLOBALS['content_width'] = 720; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- variable prévue par WordPress.
}
add_action( 'after_setup_theme', 'la_fabrique_largeur_contenu', 0 );

/**
 * Libellés des tailles d'images dans l'éditeur.
 *
 * @param array $tailles Tailles proposées.
 * @return array
 */
function la_fabrique_noms_tailles( $tailles ) {
	$tailles['la-fabrique-recette'] = __( 'Recette (3:2)', 'la-fabrique' );
	return $tailles;
}
add_filter( 'image_size_names_choose', 'la_fabrique_noms_tailles' );
