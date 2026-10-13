<?php
/**
 * Filtres sur le balisage et la hiérarchie des templates.
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;

/**
 * Classes du body : repère de mise en page pour le CSS.
 *
 * @param string[] $classes Classes existantes.
 * @return string[]
 */
function la_fabrique_classes_body( $classes ) {
	if ( is_front_page() ) {
		$classes[] = 'lf-accueil';
	}
	if ( is_singular() && has_post_thumbnail() ) {
		$classes[] = 'lf-avec-image';
	}
	return $classes;
}
add_filter( 'body_class', 'la_fabrique_classes_body' );

/**
 * Titres d'archives sans préfixe (« Catégorie : », « Archives : »…).
 *
 * @return string
 */
function la_fabrique_prefixe_archive() {
	return '';
}
add_filter( 'get_the_archive_title_prefix', 'la_fabrique_prefixe_archive' );

/**
 * Extraits courts pour les cartes.
 *
 * @return int
 */
function la_fabrique_longueur_extrait() {
	return 22;
}
add_filter( 'excerpt_length', 'la_fabrique_longueur_extrait' );

/**
 * Points de suspension sobres à la fin des extraits.
 *
 * @return string
 */
function la_fabrique_suite_extrait() {
	return '…';
}
add_filter( 'excerpt_more', 'la_fabrique_suite_extrait' );

/**
 * Recettes : neuf par page (la route REST du plugin accepte jusqu'à 12 ; le JavaScript demande la même valeur).
 *
 * @param WP_Query $requete Requête en cours.
 */
function la_fabrique_requete_recettes( $requete ) {
	if ( is_admin() || ! $requete->is_main_query() ) {
		return;
	}
	if ( $requete->is_post_type_archive( 'recette' ) || $requete->is_tax( 'methode' ) ) {
		$requete->set( 'posts_per_page', la_fabrique_recettes_par_page() );
	}
}
add_action( 'pre_get_posts', 'la_fabrique_requete_recettes' );

/**
 * Nombre de recettes par page (archive et filtres JavaScript).
 *
 * @return int
 */
function la_fabrique_recettes_par_page() {
	/**
	 * Filtre le nombre de recettes par page (entre 1 et 12, limite de la route REST).
	 *
	 * @param int $nombre Nombre de recettes.
	 */
	return min( 12, max( 1, (int) apply_filters( 'la_fabrique_recettes_par_page', 9 ) ) );
}

/**
 * Ajoute une classe aux liens du menu principal qui ont un sous-menu (pour le style).
 *
 * @param array    $atts  Attributs du lien.
 * @param WP_Post  $item  Élément de menu.
 * @param stdClass $args  Arguments de wp_nav_menu().
 * @return array
 */
function la_fabrique_attributs_liens_menu( $atts, $item, $args ) {
	if ( isset( $args->theme_location ) && 'principal' === $args->theme_location ) {
		$atts['class'] = trim( ( $atts['class'] ?? '' ) . ' lf-nav__lien' );
	}
	return $atts;
}
add_filter( 'nav_menu_link_attributes', 'la_fabrique_attributs_liens_menu', 10, 3 );
