<?php
/**
 * Affichage des recettes (données du plugin fabrique-recettes).
 *
 * Le thème n'accède aux données que par le contrat public du plugin :
 * fabrique_recette_infos(), fabrique_cafe_fiche() et la route REST fabrique/v1/recettes.
 * Sans le plugin, les fonctions ci-dessous renvoient des valeurs vides et les sections
 * « recettes » ne s'affichent pas.
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;

/**
 * Le plugin des recettes est-il actif ?
 *
 * @return bool
 */
function la_fabrique_recettes_actives() {
	return post_type_exists( 'recette' ) && function_exists( 'fabrique_recette_infos' );
}

/**
 * Infos d'une recette, avec les mêmes clés que le plugin même s'il est absent.
 *
 * @param int $post_id Identifiant de la recette.
 * @return array
 */
function la_fabrique_infos_recette( $post_id ) {
	$defaut = array(
		'temps'              => null,
		'difficulte'         => '',
		'difficulte_libelle' => '',
		'dose_cafe'          => null,
		'volume_eau'         => null,
		'ratio'              => '',
		'temperature'        => null,
		'mouture'            => '',
		'mouture_libelle'    => '',
		'cafes'              => array(),
		'astuce'             => '',
		'methodes'           => array(),
	);
	if ( ! function_exists( 'fabrique_recette_infos' ) ) {
		return $defaut;
	}
	return array_merge( $defaut, (array) fabrique_recette_infos( (int) $post_id ) );
}

/**
 * Fiche d'un café, avec les mêmes clés que le plugin même s'il est absent.
 *
 * @param int $product_id Identifiant du produit.
 * @return array
 */
function la_fabrique_fiche_cafe( $product_id ) {
	$defaut = array(
		'origine'              => '',
		'procede'              => '',
		'altitude'             => null,
		'notes'                => array(),
		'intensite'            => 0,
		'torrefaction'         => '',
		'torrefaction_libelle' => '',
	);
	if ( ! function_exists( 'fabrique_cafe_fiche' ) ) {
		return $defaut;
	}
	return array_merge( $defaut, (array) fabrique_cafe_fiche( (int) $product_id ) );
}

/**
 * Durée lisible d'une recette : 5 → « 5 min », 720 → « 12 h », 90 → « 1 h 30 ».
 *
 * @param int|null $minutes Durée en minutes.
 * @return string
 */
function la_fabrique_temps_lisible( $minutes ) {
	if ( ! $minutes ) {
		return '';
	}
	$minutes = (int) $minutes;
	if ( $minutes < 60 ) {
		/* translators: %d : nombre de minutes. */
		return sprintf( __( '%d min', 'la-fabrique' ), $minutes );
	}
	$heures = intdiv( $minutes, 60 );
	$reste  = $minutes % 60;
	return $reste
		/* translators: 1 : heures, 2 : minutes sur deux chiffres. */
		? sprintf( __( '%1$d h %2$s', 'la-fabrique' ), $heures, sprintf( '%02d', $reste ) )
		/* translators: %d : nombre d'heures. */
		: sprintf( __( '%d h', 'la-fabrique' ), $heures );
}

/**
 * Taille d'image des cartes recettes, aussi utilisée par la route REST (cartes identiques en PHP et en JS).
 *
 * @return string
 */
function la_fabrique_taille_image_recette() {
	return 'la-fabrique-recette';
}
add_filter( 'fabrique_recettes_rest_taille_image', 'la_fabrique_taille_image_recette' );

/**
 * Données d'une carte recette à partir d'un article WordPress (même forme que la route REST).
 *
 * @param WP_Post|int $recette Recette.
 * @return array
 */
function la_fabrique_donnees_recette( $recette ) {
	$recette = get_post( $recette );
	$infos   = la_fabrique_infos_recette( $recette->ID );
	$cafes   = array();
	foreach ( $infos['cafes'] as $cafe_id ) {
		$cafes[] = array(
			'id'   => (int) $cafe_id,
			'nom'  => get_the_title( $cafe_id ),
			'lien' => get_permalink( $cafe_id ),
		);
	}
	return array(
		'id'                 => $recette->ID,
		'titre'              => get_the_title( $recette ),
		'lien'               => get_permalink( $recette ),
		'extrait'            => wp_strip_all_tags( get_the_excerpt( $recette ) ),
		'image_id'           => (int) get_post_thumbnail_id( $recette ),
		'image'              => null,
		'methode'            => $infos['methodes'] ? array(
			'slug' => $infos['methodes'][0]['slug'],
			'nom'  => $infos['methodes'][0]['nom'],
		) : null,
		'temps'              => $infos['temps'],
		'difficulte'         => $infos['difficulte'],
		'difficulte_libelle' => $infos['difficulte_libelle'],
		'ratio'              => $infos['ratio'],
		'cafes'              => $cafes,
	);
}

/**
 * Interroge la route REST du plugin depuis PHP (sans requête HTTP) : repli rendu côté serveur.
 *
 * @param array $parametres Paramètres de la route (cafe, methode, par_page, page).
 * @return array|null Données de la réponse, ou null si la route n'existe pas ou renvoie une erreur.
 */
function la_fabrique_rest_recettes( $parametres ) {
	if ( ! la_fabrique_recettes_actives() ) {
		return null;
	}
	$requete = new WP_REST_Request( 'GET', '/fabrique/v1/recettes' );
	$requete->set_query_params( $parametres );
	$reponse = rest_do_request( $requete );
	if ( $reponse->is_error() ) {
		return null;
	}
	$donnees = $reponse->get_data();
	return is_array( $donnees ) && isset( $donnees['recettes'] ) ? $donnees : null;
}

/**
 * Affiche une carte recette.
 *
 * @param array $recette Données (la_fabrique_donnees_recette() ou réponse REST).
 * @param array $options niveau (2 ou 3), variante (''|'une'|'ligne').
 */
function la_fabrique_carte_recette( $recette, $options = array() ) {
	get_template_part(
		'template-parts/recette/carte',
		null,
		array_merge(
			array(
				'recette'  => $recette,
				'niveau'   => 3,
				'variante' => '',
			),
			$options
		)
	);
}

/**
 * Charge le script des recettes là où il sert, avec ses textes traduits.
 */
function la_fabrique_script_recettes() {
	if ( ! la_fabrique_recettes_actives() ) {
		return;
	}
	if ( ! ( is_post_type_archive( 'recette' ) || is_tax( 'methode' ) || is_singular( 'product' ) ) ) {
		return;
	}
	wp_enqueue_script( 'la-fabrique-recettes' );
	wp_add_inline_script(
		'la-fabrique-recettes',
		'window.laFabriqueRecettes = ' . wp_json_encode(
			array(
				'route'    => esc_url_raw( rest_url( 'fabrique/v1/recettes' ) ),
				'parPage'  => la_fabrique_recettes_par_page(),
				'textes'   => array(
					/* translators: %d : nombre de recettes. */
					'une'        => __( '%d recette', 'la-fabrique' ),
					/* translators: %d : nombre de recettes. */
					'plusieurs'  => __( '%d recettes', 'la-fabrique' ),
					'aucune'     => __( 'Pas encore de recette pour cette méthode. Les autres vous attendent.', 'la-fabrique' ),
					'erreur'     => __( 'Les recettes n’ont pas pu être chargées. La page complète reste accessible avec ce lien.', 'la-fabrique' ),
					'chargement' => __( 'Chargement des recettes…', 'la-fabrique' ),
					/* translators: %s : nom de la méthode. */
					'titre'      => __( 'Recettes : %s', 'la-fabrique' ),
					'toutes'     => __( 'Recettes', 'la-fabrique' ),
					'plus'       => __( 'Afficher plus de recettes', 'la-fabrique' ),
					/* translators: %d : nombre de minutes. */
					'minutes'    => __( '%d min', 'la-fabrique' ),
					/* translators: %d : nombre d'heures. */
					'heures'     => __( '%d h', 'la-fabrique' ),
					/* translators: 1 : heures, 2 : minutes sur deux chiffres. */
					'heuresMin'  => __( '%1$d h %2$s', 'la-fabrique' ),
				),
				'methodes' => la_fabrique_methodes(),
			)
		) . ';',
		'before'
	);
}
add_action( 'wp_enqueue_scripts', 'la_fabrique_script_recettes', 20 );

/**
 * Méthodes de préparation (termes de la taxonomie « methode ») avec leur lien.
 *
 * @return array<int, array{slug: string, nom: string, lien: string, nombre: int}>
 */
function la_fabrique_methodes() {
	if ( ! taxonomy_exists( 'methode' ) ) {
		return array();
	}
	$termes = get_terms(
		array(
			'taxonomy'   => 'methode',
			'hide_empty' => false,
			'orderby'    => 'term_order',
		)
	);
	if ( is_wp_error( $termes ) ) {
		return array();
	}
	$methodes = array();
	foreach ( $termes as $terme ) {
		$lien       = get_term_link( $terme );
		$methodes[] = array(
			'slug'   => $terme->slug,
			'nom'    => $terme->name,
			'lien'   => is_wp_error( $lien ) ? '' : $lien,
			'nombre' => (int) $terme->count,
		);
	}
	return $methodes;
}
