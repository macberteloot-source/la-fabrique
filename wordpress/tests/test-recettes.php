<?php
/**
 * Tests du plugin fabrique-recettes : fonctions publiques et route REST.
 *
 * Prérequis : contenus de démonstration (bin/seed.php).
 * Lancement : wp eval-file tests/test-recettes.php
 *
 * @package Fabrique\Tests
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/lib.php';

/**
 * Identifiant d'une recette de démonstration.
 *
 * @param string $slug Slug.
 */
function fabrique_test_recette( string $slug ): int {
	$recette = get_page_by_path( $slug, OBJECT, 'recette' );
	if ( ! $recette ) {
		fabrique_echec( "recette {$slug} absente : lancer bin/seed.php" );
	}
	return (int) $recette->ID;
}

/**
 * Exécute une requête REST interne.
 *
 * @param string               $methode Méthode HTTP.
 * @param string               $route   Route.
 * @param array<string, mixed> $params  Paramètres de requête.
 */
function fabrique_test_rest( string $methode, string $route, array $params = array() ): WP_REST_Response {
	$requete = new WP_REST_Request( $methode, $route );
	$requete->set_query_params( $params );
	return rest_ensure_response( rest_do_request( $requete ) );
}

// Le même fichier est lancé deux fois par tests/run.sh : avec ACF, puis avec --skip-plugins=advanced-custom-fields.
fabrique_test_groupe( 'fabrique_recette_infos() — ' . ( function_exists( 'get_field' ) ? 'ACF actif' : 'sans ACF (repli get_post_meta)' ) );

fabrique_test(
	'mêmes clés pour une recette, un contenu quelconque et un identifiant inexistant',
	function () {
		$cles = array( 'temps', 'difficulte', 'difficulte_libelle', 'dose_cafe', 'volume_eau', 'ratio', 'temperature', 'mouture', 'mouture_libelle', 'cafes', 'astuce', 'methodes' );
		fabrique_egal( $cles, array_keys( fabrique_recette_infos( fabrique_test_recette( 'v60-du-dimanche' ) ) ) );
		fabrique_egal( $cles, array_keys( fabrique_recette_infos( (int) get_option( 'page_on_front' ) ) ) );
		fabrique_egal( $cles, array_keys( fabrique_recette_infos( 999999 ) ) );
		fabrique_egal( null, fabrique_recette_infos( 999999 )['temps'] );
		fabrique_egal( array(), fabrique_recette_infos( 999999 )['methodes'] );
	}
);

fabrique_test(
	'V60 du dimanche : valeurs typées, libellés, ratio, café lié, méthode',
	function () {
		$infos = fabrique_recette_infos( fabrique_test_recette( 'v60-du-dimanche' ) );
		fabrique_egal( 4, $infos['temps'] );
		fabrique_egal( 'facile', $infos['difficulte'] );
		fabrique_egal( 'Facile', $infos['difficulte_libelle'] );
		fabrique_egal( 15.0, $infos['dose_cafe'] );
		fabrique_egal( 250, $infos['volume_eau'] );
		fabrique_egal( '1:' . number_format_i18n( 250 / 15, 1 ), $infos['ratio'] );
		fabrique_egal( 94, $infos['temperature'] );
		fabrique_egal( 'moyenne-fine', $infos['mouture'] );
		fabrique_egal( 'Moyenne-fine', $infos['mouture_libelle'] );
		fabrique_egal( array( wc_get_product_id_by_sku( 'beffroi' ) ), $infos['cafes'] );
		fabrique_egal( 'filtre-doux', $infos['methodes'][0]['slug'] );
		fabrique_egal( 'Filtre doux', $infos['methodes'][0]['nom'] );
		fabrique_contient( '/recettes/methode/filtre-doux/', $infos['methodes'][0]['lien'] );
		fabrique_vrai( '' !== $infos['astuce'], 'astuce vide' );
	}
);

fabrique_test(
	'espresso 18 g → 36 g : ratio entier « 1:2 » ; cold brew : température null',
	function () {
		fabrique_egal( '1:2', fabrique_recette_infos( fabrique_test_recette( 'espresso-le-reglage-de-base' ) )['ratio'] );
		$cold = fabrique_recette_infos( fabrique_test_recette( 'cold-brew-12-heures' ) );
		fabrique_egal( null, $cold['temperature'] );
		fabrique_egal( 'Grossière', $cold['mouture_libelle'] );
	}
);

fabrique_test(
	'ratio arrondi au dixième, sans « ,0 » : 20 g / 339 ml → « 1:17 »',
	function () {
		$id = wp_insert_post(
			array(
				'post_type'   => 'recette',
				'post_title'  => 'Recette de test (ratio)',
				'post_status' => 'draft',
			)
		);
		update_post_meta( $id, 'fabrique_dose_cafe', '20' );
		update_post_meta( $id, 'fabrique_volume_eau', '339' );
		$ratio = fabrique_recette_infos( $id )['ratio'];
		wp_delete_post( $id, true );

		fabrique_egal( '1:17', $ratio, '16,95 arrondi au dixième = 17,0 :' );
		fabrique_egal( '1:17', \Fabrique\Recettes\Champs::ratio( 20.0, 339 ) );
		fabrique_egal( '1:' . number_format_i18n( 16.7, 1 ), \Fabrique\Recettes\Champs::ratio( 15.0, 250 ) );
		fabrique_egal( '1:' . number_format_i18n( 12.5, 1 ), \Fabrique\Recettes\Champs::ratio( 80.0, 1000 ) );
		fabrique_egal( '1:2', \Fabrique\Recettes\Champs::ratio( 18.0, 36 ) );
		fabrique_egal( '', \Fabrique\Recettes\Champs::ratio( 0.0, 250 ) );
	}
);

fabrique_test(
	'sans ACF (lecture par get_post_meta) : mêmes valeurs ; métas nettoyées à l’enregistrement',
	function () {
		$id = wp_insert_post(
			array(
				'post_type'   => 'recette',
				'post_title'  => 'Recette de test (sans ACF)',
				'post_status' => 'draft',
			)
		);
		// Écriture directe en méta, comme sans ACF ; valeurs volontairement sales.
		update_post_meta( $id, 'fabrique_temps', ' 12 ' );
		update_post_meta( $id, 'fabrique_difficulte', 'impossible' );
		update_post_meta( $id, 'fabrique_dose_cafe', '17.5' );
		update_post_meta( $id, 'fabrique_volume_eau', '280' );
		update_post_meta( $id, 'fabrique_mouture', 'moyenne' );
		update_post_meta( $id, 'fabrique_cafes', array( wc_get_product_id_by_sku( 'fives' ), 'abc', 0 ) );
		update_post_meta( $id, 'fabrique_astuce', "<script>alert(1)</script>Remuez\ndoucement" );

		$infos = fabrique_recette_infos( $id );
		fabrique_egal( 12, $infos['temps'] );
		fabrique_egal( '', $infos['difficulte'], 'choix inconnu refusé :' );
		fabrique_egal( 17.5, $infos['dose_cafe'] );
		fabrique_egal( '1:16', $infos['ratio'] );
		fabrique_egal( 'Moyenne', $infos['mouture_libelle'] );
		fabrique_egal( array( wc_get_product_id_by_sku( 'fives' ) ), $infos['cafes'] );
		fabrique_egal( array( (string) wc_get_product_id_by_sku( 'fives' ) ), get_post_meta( $id, 'fabrique_cafes', true ), 'stockage en chaînes (format ACF) :' );
		fabrique_vrai( ! str_contains( $infos['astuce'], '<script>' ), 'balise non retirée' );
		wp_delete_post( $id, true );
	}
);

fabrique_test(
	'un café dépublié disparaît de la liste des cafés de la recette',
	function () {
		$cafe_id = wc_get_product_id_by_sku( 'nuit-blanche' );
		$recette = fabrique_test_recette( 'affogato-du-vieux-lille' );
		fabrique_vrai( in_array( $cafe_id, fabrique_recette_infos( $recette )['cafes'], true ), 'café absent avant le test' );
		wp_update_post(
			array(
				'ID'          => $cafe_id,
				'post_status' => 'draft',
			)
		);
		clean_post_cache( $cafe_id );
		$absent = ! in_array( $cafe_id, fabrique_recette_infos( $recette )['cafes'], true );
		wp_update_post(
			array(
				'ID'          => $cafe_id,
				'post_status' => 'publish',
			)
		);
		fabrique_vrai( $absent, 'café brouillon encore listé' );
	}
);

fabrique_test_groupe( 'fabrique_cafe_fiche()' );

fabrique_test(
	'Wazemmes : fiche complète, notes en tableau, intensité bornée, variation → parent',
	function () {
		$id    = wc_get_product_id_by_sku( 'wazemmes' );
		$fiche = fabrique_cafe_fiche( $id );
		fabrique_egal( array( 'origine', 'procede', 'altitude', 'notes', 'intensite', 'torrefaction', 'torrefaction_libelle' ), array_keys( $fiche ) );
		fabrique_egal( 'Brésil & Inde', $fiche['origine'] );
		fabrique_egal( 1100, $fiche['altitude'] );
		fabrique_egal( array( 'chocolat noir', 'noisette', 'mélasse' ), $fiche['notes'] );
		fabrique_egal( 4, $fiche['intensite'] );
		fabrique_egal( 'moyenne-foncee', $fiche['torrefaction'] );
		fabrique_egal( 'Moyenne-foncée', $fiche['torrefaction_libelle'] );

		$variations = wc_get_product( $id )->get_children();
		fabrique_egal( $fiche, fabrique_cafe_fiche( (int) $variations[0] ) );
	}
);

fabrique_test(
	'produit sans fiche (accessoire) et identifiant invalide : valeurs vides',
	function () {
		$fiche = fabrique_cafe_fiche( wc_get_product_id_by_sku( 'dripper-gres' ) );
		fabrique_egal( '', $fiche['origine'] );
		fabrique_egal( null, $fiche['altitude'] );
		fabrique_egal( array(), $fiche['notes'] );
		fabrique_egal( 0, $fiche['intensite'] );
		fabrique_egal( '', fabrique_cafe_fiche( 0 )['torrefaction_libelle'] );
	}
);

fabrique_test_groupe( 'GET /fabrique/v1/recettes' );

fabrique_test(
	'liste par défaut : 6 recettes, structure du contrat, en-têtes de pagination et de cache',
	function () {
		$reponse = fabrique_test_rest( 'GET', '/fabrique/v1/recettes' );
		fabrique_egal( 200, $reponse->get_status() );
		$donnees = $reponse->get_data();
		fabrique_egal( array( 'recettes', 'total', 'pages' ), array_keys( $donnees ) );
		fabrique_egal( 6, $donnees['total'] );
		fabrique_egal( 1, $donnees['pages'] );
		fabrique_egal( 6, count( $donnees['recettes'] ) );
		$recette = $donnees['recettes'][0];
		fabrique_egal( array( 'id', 'titre', 'lien', 'extrait', 'image', 'methode', 'temps', 'difficulte', 'difficulte_libelle', 'ratio', 'cafes' ), array_keys( $recette ) );
		fabrique_vrai( null === $recette['image'] || array( 'url', 'alt', 'largeur', 'hauteur' ) === array_keys( $recette['image'] ), 'image mal formée' );
		fabrique_egal( array( 'slug', 'nom' ), array_keys( $recette['methode'] ) );
		fabrique_egal( array( 'id', 'nom', 'lien' ), array_keys( $recette['cafes'][0] ) );
		$entetes = $reponse->get_headers();
		fabrique_egal( '6', $entetes['X-WP-Total'] );
		fabrique_egal( '1', $entetes['X-WP-TotalPages'] );
		fabrique_egal( 'public, max-age=300', $entetes['Cache-Control'] );
	}
);

fabrique_test(
	'filtres methode et cafe, pagination',
	function () {
		$au_lait = fabrique_test_rest( 'GET', '/fabrique/v1/recettes', array( 'methode' => 'au-lait' ) )->get_data();
		fabrique_egal( 2, $au_lait['total'] );
		foreach ( $au_lait['recettes'] as $recette ) {
			fabrique_egal( 'au-lait', $recette['methode']['slug'] );
		}

		$vieux_lille = fabrique_test_rest( 'GET', '/fabrique/v1/recettes', array( 'cafe' => (string) wc_get_product_id_by_sku( 'vieux-lille' ) ) )->get_data();
		fabrique_egal( 2, $vieux_lille['total'], 'Vieux-Lille (cold brew + affogato) :' );

		$page = fabrique_test_rest(
			'GET',
			'/fabrique/v1/recettes',
			array(
				'par_page' => '4',
				'page'     => '2',
			)
		);
		fabrique_egal( 2, count( $page->get_data()['recettes'] ) );
		fabrique_egal( 2, $page->get_data()['pages'] );
		fabrique_egal( '2', $page->get_headers()['X-WP-TotalPages'] );
	}
);

fabrique_test(
	'paramètres invalides → 400 au format WP_Error (rest_invalid_param)',
	function () {
		$cas = array(
			array( 'par_page' => '13' ),
			array( 'par_page' => '0' ),
			array( 'page' => '0' ),
			array( 'page' => 'deux' ),
			array( 'methode' => 'inconnue' ),
			array( 'methode' => '<script>' ),
			array( 'cafe' => '-3' ),
			array( 'cafe' => (string) fabrique_test_recette( 'v60-du-dimanche' ) ),
			array( 'page' => '9' ),
		);
		foreach ( $cas as $params ) {
			$reponse = fabrique_test_rest( 'GET', '/fabrique/v1/recettes', $params );
			fabrique_egal( 400, $reponse->get_status(), fabrique_test_exporter( $params ) );
			fabrique_egal( 'rest_invalid_param', $reponse->get_data()['code'], fabrique_test_exporter( $params ) );
		}
	}
);

fabrique_test(
	'seules les recettes publiées sans mot de passe sont listées',
	function () {
		$brouillon = wp_insert_post(
			array(
				'post_type'   => 'recette',
				'post_title'  => 'Brouillon secret',
				'post_status' => 'draft',
			)
		);
		$protegee  = wp_insert_post(
			array(
				'post_type'     => 'recette',
				'post_title'    => 'Recette protégée',
				'post_status'   => 'publish',
				'post_password' => 'motdepasse',
			)
		);
		$donnees   = fabrique_test_rest( 'GET', '/fabrique/v1/recettes', array( 'par_page' => '12' ) )->get_data();
		$ids       = wp_list_pluck( $donnees['recettes'], 'id' );
		$detail_b  = fabrique_test_rest( 'GET', '/fabrique/v1/recettes/' . $brouillon )->get_status();
		$detail_p  = fabrique_test_rest( 'GET', '/fabrique/v1/recettes/' . $protegee )->get_status();
		wp_delete_post( $brouillon, true );
		wp_delete_post( $protegee, true );

		fabrique_egal( 6, $donnees['total'] );
		fabrique_vrai( ! in_array( $brouillon, $ids, true ) && ! in_array( $protegee, $ids, true ), 'contenu non public listé' );
		fabrique_egal( 404, $detail_b, 'détail d’un brouillon :' );
		fabrique_egal( 404, $detail_p, 'détail d’une recette protégée :' );
	}
);

fabrique_test_groupe( 'GET /fabrique/v1/recettes/<id>' );

fabrique_test(
	'détail : objet de liste + contenu HTML (liste numérotée) + infos',
	function () {
		$id      = fabrique_test_recette( 'v60-du-dimanche' );
		$reponse = fabrique_test_rest( 'GET', '/fabrique/v1/recettes/' . $id );
		fabrique_egal( 200, $reponse->get_status() );
		$donnees = $reponse->get_data();
		fabrique_egal( $id, $donnees['id'] );
		fabrique_egal( 'V60 du dimanche', $donnees['titre'] );
		fabrique_contient( '<ol', $donnees['contenu'] );
		fabrique_egal( fabrique_recette_infos( $id ), $donnees['infos'] );
		fabrique_egal( 'public, max-age=300', $reponse->get_headers()['Cache-Control'] );
	}
);

fabrique_test(
	'détail : titre en texte brut (apostrophe typographique, pas d’entité HTML)',
	function () {
		$donnees = fabrique_test_rest( 'GET', '/fabrique/v1/recettes/' . fabrique_test_recette( 'cafe-chicoree-a-lancienne' ) )->get_data();
		fabrique_egal( 'Café-chicorée à l’ancienne', $donnees['titre'] );
	}
);

fabrique_test(
	'détail : identifiant inexistant ou d’un autre type → 404',
	function () {
		fabrique_egal( 404, fabrique_test_rest( 'GET', '/fabrique/v1/recettes/999999' )->get_status() );
		fabrique_egal( 404, fabrique_test_rest( 'GET', '/fabrique/v1/recettes/' . wc_get_product_id_by_sku( 'wazemmes' ) )->get_status() );
		fabrique_egal( 'fabrique_recette_introuvable', fabrique_test_rest( 'GET', '/fabrique/v1/recettes/999999' )->get_data()['code'] );
	}
);

fabrique_test(
	'route en lecture seule : POST refusé',
	function () {
		fabrique_egal( 404, fabrique_test_rest( 'POST', '/fabrique/v1/recettes' )->get_status() );
	}
);

fabrique_test_fin();
