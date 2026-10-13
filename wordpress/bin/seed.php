<?php
/**
 * Contenus de démonstration de La Fabrique (idempotent).
 *
 * Usage : wp eval-file bin/seed.php
 *
 * Chaque élément est retrouvé par son sku ou son slug : relancer le script met à jour
 * sans dupliquer. Les illustrations seed/images/{sku}.png et seed/images/recette-{slug}.png
 * sont importées si elles existent (et réimportées si le fichier a changé) ; sans elles,
 * les contenus sont créés sans image.
 *
 * @package Fabrique
 */

defined( 'ABSPATH' ) || exit;

/**
 * Affiche une ligne dans le terminal (WP-CLI).
 *
 * @param string $message Message.
 */
function fabrique_seed_log( string $message ): void {
	if ( class_exists( 'WP_CLI' ) ) {
		WP_CLI::log( $message );
	}
}

/**
 * Arrête le script avec un message d'erreur.
 *
 * @param string $message Message.
 */
function fabrique_seed_erreur( string $message ): void {
	if ( class_exists( 'WP_CLI' ) ) {
		WP_CLI::error( $message );
	}
	exit( 1 );
}

/**
 * Dossier des illustrations générées par le thème.
 */
function fabrique_seed_dossier_images(): string {
	return dirname( __DIR__ ) . '/seed/images';
}

/**
 * Enregistre la valeur d'un champ : via ACF si actif (référence de champ comprise), sinon en méta.
 *
 * @param int    $post_id Contenu.
 * @param string $nom     Nom du champ (= clé de méta).
 * @param mixed  $valeur  Valeur (null = vide).
 */
function fabrique_seed_champ( int $post_id, string $nom, $valeur ): void {
	$valeur = null === $valeur ? '' : $valeur;
	if ( function_exists( 'update_field' ) ) {
		update_field( 'field_' . $nom, $valeur, $post_id );
		return;
	}
	update_post_meta( $post_id, $nom, $valeur );
}

/**
 * Retrouve un contenu par type et slug, quel que soit son statut.
 *
 * @param string $type Type de contenu.
 * @param string $slug Slug.
 */
function fabrique_seed_trouver( string $type, string $slug ): int {
	$ids = get_posts(
		array(
			'post_type'      => $type,
			'name'           => $slug,
			'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);
	return $ids ? (int) $ids[0] : 0;
}

/**
 * Crée ou met à jour un contenu (page, recette) retrouvé par son slug.
 *
 * @param array<string, mixed> $donnees Champs de wp_insert_post() ; post_type et post_name obligatoires.
 * @return int Identifiant.
 */
function fabrique_seed_contenu( array $donnees ): int {
	$id = fabrique_seed_trouver( $donnees['post_type'], $donnees['post_name'] );
	if ( $id ) {
		$donnees['ID'] = $id;
		unset( $donnees['post_date'] );
		$resultat = wp_update_post( wp_slash( $donnees ), true );
	} else {
		$resultat = wp_insert_post( wp_slash( $donnees ), true );
	}
	if ( is_wp_error( $resultat ) ) {
		fabrique_seed_erreur( $donnees['post_name'] . ' : ' . $resultat->get_error_message() );
	}
	return (int) $resultat;
}

/**
 * Cherche une illustration : nom exact, sinon un fichier dont le nom diffère seulement par la ponctuation
 * (« recette-cafe-chicoree-a-l-ancienne.png » pour « recette-cafe-chicoree-a-lancienne.png »).
 *
 * @param string $fichier Nom attendu (ex. wazemmes.png).
 * @return string Chemin complet, ou chaîne vide.
 */
function fabrique_seed_chercher_image( string $fichier ): string {
	$dossier = fabrique_seed_dossier_images();
	if ( is_readable( $dossier . '/' . $fichier ) ) {
		return $dossier . '/' . $fichier;
	}
	$cle = preg_replace( '/[^a-z0-9]/', '', strtolower( pathinfo( $fichier, PATHINFO_FILENAME ) ) );
	// Pas de GLOB_BRACE : indisponible sous Alpine (image WordPress:cli).
	foreach ( (array) glob( $dossier . '/*' ) as $candidat ) {
		$candidat = (string) $candidat;
		if ( ! in_array( strtolower( pathinfo( $candidat, PATHINFO_EXTENSION ) ), array( 'png', 'webp', 'jpg' ), true ) ) {
			continue;
		}
		$nom = preg_replace( '/[^a-z0-9]/', '', strtolower( pathinfo( $candidat, PATHINFO_FILENAME ) ) );
		if ( $nom === $cle ) {
			return $candidat;
		}
	}
	return '';
}

/**
 * Importe une illustration dans la médiathèque (une seule fois par version du fichier).
 *
 * @param string $fichier Nom du fichier dans seed/images.
 * @param string $alt     Texte alternatif.
 * @param int    $contenu Contenu auquel rattacher le média.
 * @return int Identifiant du média, ou 0 si le fichier n'existe pas (encore).
 */
function fabrique_seed_image( string $fichier, string $alt, int $contenu ): int {
	$chemin = fabrique_seed_chercher_image( $fichier );
	if ( '' === $chemin ) {
		return 0;
	}
	$empreinte = (string) md5_file( $chemin );

	$existants = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => '_fabrique_seed_source', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- script ponctuel.
			'meta_value'     => $fichier, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- script ponctuel.
		)
	);
	foreach ( $existants as $media_id ) {
		if ( get_post_meta( $media_id, '_fabrique_seed_empreinte', true ) === $empreinte ) {
			update_post_meta( $media_id, '_wp_attachment_image_alt', $alt );
			return (int) $media_id;
		}
		// Le fichier a changé depuis le dernier import : l'ancienne version est remplacée.
		wp_delete_attachment( $media_id, true );
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';

	$temporaire = wp_tempnam( $fichier );
	copy( $chemin, $temporaire );
	$media_id = media_handle_sideload(
		array(
			'name'     => basename( $chemin ),
			'tmp_name' => $temporaire,
		),
		$contenu,
		$alt
	);
	if ( is_wp_error( $media_id ) ) {
		wp_delete_file( $temporaire );
		fabrique_seed_log( "  ! image {$fichier} : " . $media_id->get_error_message() );
		return 0;
	}

	update_post_meta( $media_id, '_wp_attachment_image_alt', $alt );
	update_post_meta( $media_id, '_fabrique_seed_source', $fichier );
	update_post_meta( $media_id, '_fabrique_seed_empreinte', $empreinte );
	fabrique_seed_log( "  image importée : {$fichier}" );
	return (int) $media_id;
}

/**
 * Balisage de blocs : paragraphes, puis sections (titre + liste à puces ou numérotée).
 *
 * @param string[]                                                           $paragraphes Paragraphes d'introduction.
 * @param array<int, array{titre: string, items: string[], numerote?: bool}> $sections    Sections.
 */
function fabrique_seed_blocs( array $paragraphes, array $sections = array() ): string {
	$html = '';
	foreach ( $paragraphes as $paragraphe ) {
		$html .= "<!-- wp:paragraph -->\n<p>" . esc_html( $paragraphe ) . "</p>\n<!-- /wp:paragraph -->\n\n";
	}
	foreach ( $sections as $section ) {
		$numerote = ! empty( $section['numerote'] );
		$balise   = $numerote ? 'ol' : 'ul';
		$html    .= "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">" . esc_html( $section['titre'] ) . "</h2>\n<!-- /wp:heading -->\n\n";
		$html    .= $numerote ? "<!-- wp:list {\"ordered\":true} -->\n" : "<!-- wp:list -->\n";
		$html    .= "<{$balise} class=\"wp-block-list\">";
		foreach ( $section['items'] as $item ) {
			$html .= "<!-- wp:list-item -->\n<li>" . esc_html( $item ) . "</li>\n<!-- /wp:list-item -->";
		}
		$html .= "</{$balise}>\n<!-- /wp:list -->\n\n";
	}
	return trim( $html );
}

/**
 * Catégories de produits.
 *
 * @return array<string, int> slug => identifiant.
 */
function fabrique_seed_categories(): array {
	$categories = array(
		'cafes'       => array( 'Cafés', 'Cafés de spécialité torréfiés chaque mardi à l’atelier, en petits lots.' ),
		'accessoires' => array( 'Accessoires', 'Le matériel qu’on utilise nous-mêmes au comptoir.' ),
		'coffrets'    => array( 'Coffrets', 'Pour offrir, ou pour trouver son café.' ),
	);

	$ids   = array();
	$ordre = 0;
	foreach ( $categories as $slug => list( $nom, $description ) ) {
		$terme = get_term_by( 'slug', $slug, 'product_cat' );
		if ( $terme ) {
			wp_update_term(
				$terme->term_id,
				'product_cat',
				array(
					'name'        => $nom,
					'description' => $description,
				)
			);
			$ids[ $slug ] = (int) $terme->term_id;
		} else {
			$resultat = wp_insert_term(
				$nom,
				'product_cat',
				array(
					'slug'        => $slug,
					'description' => $description,
				)
			);
			if ( is_wp_error( $resultat ) ) {
				fabrique_seed_erreur( "Catégorie {$slug} : " . $resultat->get_error_message() );
			}
			$ids[ $slug ] = (int) $resultat['term_id'];
		}
		update_term_meta( $ids[ $slug ], 'order', $ordre++ );
	}
	fabrique_seed_log( '  catégories : ' . implode( ', ', array_keys( $ids ) ) );
	return $ids;
}

/**
 * Attribut global (pa_…) et ses termes, dans l'ordre donné.
 *
 * @param string                $slug   Slug sans préfixe (poids, mouture).
 * @param string                $nom    Libellé.
 * @param array<string, string> $termes slug => nom.
 */
function fabrique_seed_attribut( string $slug, string $nom, array $termes ): void {
	$taxonomie = wc_attribute_taxonomy_name( $slug );

	if ( ! wc_attribute_taxonomy_id_by_name( $slug ) ) {
		$resultat = wc_create_attribute(
			array(
				'name'         => $nom,
				'slug'         => $slug,
				'type'         => 'select',
				'order_by'     => 'menu_order',
				'has_archives' => false,
			)
		);
		if ( is_wp_error( $resultat ) ) {
			fabrique_seed_erreur( "Attribut {$slug} : " . $resultat->get_error_message() );
		}
	}

	// La taxonomie d'un attribut créé pendant cette requête n'est déclarée qu'au prochain « init ».
	if ( ! taxonomy_exists( $taxonomie ) ) {
		register_taxonomy(
			$taxonomie,
			array( 'product', 'product_variation' ),
			array(
				'hierarchical' => false,
				'show_ui'      => false,
				'query_var'    => false,
				'rewrite'      => false,
			)
		);
	}

	$ordre = 0;
	foreach ( $termes as $terme_slug => $terme_nom ) {
		$terme = get_term_by( 'slug', $terme_slug, $taxonomie );
		if ( $terme ) {
			$terme_id = (int) $terme->term_id;
			if ( $terme->name !== $terme_nom ) {
				wp_update_term( $terme_id, $taxonomie, array( 'name' => $terme_nom ) );
			}
		} else {
			$resultat = wp_insert_term( $terme_nom, $taxonomie, array( 'slug' => $terme_slug ) );
			if ( is_wp_error( $resultat ) ) {
				fabrique_seed_erreur( "Terme {$terme_slug} : " . $resultat->get_error_message() );
			}
			$terme_id = (int) $resultat['term_id'];
		}
		update_term_meta( $terme_id, 'order', $ordre++ );
	}
	fabrique_seed_log( "  attribut {$taxonomie} : " . implode( ', ', array_keys( $termes ) ) );
}

/**
 * Attribut de produit (taxonomie globale) prêt à rattacher à un produit.
 *
 * @param string   $taxonomie Taxonomie pa_….
 * @param string[] $slugs     Termes proposés.
 * @param int      $position  Ordre d'affichage.
 */
function fabrique_seed_attribut_produit( string $taxonomie, array $slugs, int $position ): WC_Product_Attribute {
	$ids = array();
	foreach ( $slugs as $slug ) {
		$terme = get_term_by( 'slug', $slug, $taxonomie );
		if ( $terme ) {
			$ids[] = (int) $terme->term_id;
		}
	}
	$attribut = new WC_Product_Attribute();
	$attribut->set_id( wc_attribute_taxonomy_id_by_name( $taxonomie ) );
	$attribut->set_name( $taxonomie );
	$attribut->set_options( $ids );
	$attribut->set_position( $position );
	$attribut->set_visible( true );
	$attribut->set_variation( true );
	return $attribut;
}

/**
 * Données du catalogue (cafés variables, accessoires et coffrets simples).
 *
 * @return array<int, array<string, mixed>>
 */
function fabrique_seed_donnees_produits(): array {
	return array(
		array(
			'sku'         => 'wazemmes',
			'nom'         => 'Wazemmes',
			'categorie'   => 'cafes',
			'prix'        => array(
				'250-g' => '9.90',
				'1-kg'  => '34.00',
			),
			'fiche'       => array( 'Brésil & Inde', 'nature', 1100, 'chocolat noir, noisette, mélasse', 4, 'moyenne-foncee' ),
			'courte'      => 'Le café du marché du dimanche : rond, chocolaté, généreux. Celui qu’on boit sans y penser… et qu’on rachète.',
			'description' => array(
				'Un assemblage Brésil et Inde en procédé nature, torréfié un peu plus longtemps que les autres pour aller chercher le chocolat noir et la noisette. Il tient tête au lait et pardonne les petits écarts de réglage.',
				'Pour l’espresso, le latte ou la cafetière italienne de mamie.',
			),
			'une'         => true,
		),
		array(
			'sku'         => 'beffroi',
			'nom'         => 'Beffroi',
			'categorie'   => 'cafes',
			'prix'        => array(
				'250-g' => '12.50',
				'1-kg'  => '44.00',
			),
			'fiche'       => array( 'Éthiopie (Yirgacheffe)', 'lavé', 1950, 'jasmin, bergamote, pêche', 2, 'claire' ),
			'courte'      => 'Floral et lumineux, comme la vue du haut du beffroi un matin sans brume (ça arrive).',
			'description' => array(
				'Un Yirgacheffe lavé, cultivé à près de 2 000 mètres. Torréfaction claire pour garder le jasmin, la bergamote et une finale de pêche blanche.',
				'À préparer en filtre doux : V60, Chemex ou dripper en grès.',
			),
			'une'         => true,
		),
		array(
			'sku'         => 'vieux-lille',
			'nom'         => 'Vieux-Lille',
			'categorie'   => 'cafes',
			'prix'        => array(
				'250-g' => '11.00',
				'1-kg'  => '39.00',
			),
			'fiche'       => array( 'Colombie (Huila)', 'lavé', 1700, 'caramel, pomme rouge, cacao', 3, 'moyenne' ),
			'courte'      => 'Caramel et pomme rouge : l’équilibre des façades de la rue de la Monnaie, en tasse.',
			'description' => array(
				'Une Colombie de la région de Huila, lavée, sucrée et ronde. Le café qui met tout le monde d’accord, de l’espresso au cold brew.',
			),
			'une'         => false,
		),
		array(
			'sku'         => 'fives',
			'nom'         => 'Fives',
			'categorie'   => 'cafes',
			'prix'        => array(
				'250-g' => '9.50',
				'1-kg'  => '32.00',
			),
			'fiche'       => array( 'Pérou & Honduras', 'lavé', 1400, 'biscuit, amande, sucre roux', 3, 'moyenne' ),
			'courte'      => 'Le café de l’atelier, celui de la pause de 10 h : biscuit, amande et sucre roux.',
			'description' => array(
				'Notre assemblage maison, baptisé d’après le quartier de la filature. Pérou et Honduras lavés, une torréfaction moyenne, et des notes de biscuit qui font penser à un certain spéculoos.',
			),
			'une'         => false,
		),
		array(
			'sku'         => 'braderie',
			'nom'         => 'La Braderie (édition limitée)',
			'categorie'   => 'cafes',
			'prix'        => array(
				'250-g' => '14.00',
			),
			'fiche'       => array( 'Kenya (Nyeri)', 'lavé', 1800, 'cassis, pamplemousse rose', 2, 'claire' ),
			'courte'      => 'Édition limitée de la rentrée : cassis et pamplemousse rose. Comme la Braderie, ça ne dure qu’un temps.',
			'description' => array(
				'Un Kenya de Nyeri, lavé, éclatant et juteux. Un seul lot, vendu en 250 g uniquement, jusqu’à épuisement.',
			),
			'une'         => true,
		),
		array(
			'sku'         => 'nuit-blanche',
			'nom'         => 'Nuit Blanche (décaféiné)',
			'categorie'   => 'cafes',
			'prix'        => array(
				'250-g' => '11.50',
				'1-kg'  => '40.00',
			),
			'fiche'       => array( 'Mexique', 'décaféiné à l’eau', 1300, 'cacao, pain d’épices', 3, 'moyenne' ),
			'courte'      => 'Le goût sans l’insomnie : cacao et pain d’épices, décaféiné à l’eau, sans solvant.',
			'description' => array(
				'Un Mexique décaféiné à l’eau (procédé sans solvant chimique). Tout le café, sans compter les moutons après 22 h.',
			),
			'une'         => false,
		),
		array(
			'sku'         => 'dripper-gres',
			'nom'         => 'Dripper en grès',
			'categorie'   => 'accessoires',
			'prix'        => '32.00',
			'courte'      => 'Tourné à la main par un potier de Roubaix (fictif).',
			'description' => array(
				'Un dripper conique en grès émaillé, compatible avec les filtres papier de taille 02. Le grès garde la chaleur : l’extraction est plus régulière.',
			),
			'une'         => false,
		),
		array(
			'sku'         => 'moulin-manuel',
			'nom'         => 'Moulin manuel',
			'categorie'   => 'accessoires',
			'prix'        => '69.00',
			'courte'      => 'Meules coniques en acier, 40 réglages.',
			'description' => array(
				'Des meules coniques en acier et 40 crans de réglage, de l’espresso au cold brew. Moudre juste avant l’infusion change tout : c’est le premier conseil de l’atelier.',
			),
			'une'         => false,
		),
		array(
			'sku'         => 'coffret-decouverte',
			'nom'         => 'Coffret découverte',
			'categorie'   => 'coffrets',
			'prix'        => '24.00',
			'courte'      => 'Trois cafés de 100 g pour trouver le vôtre.',
			'description' => array(
				'Wazemmes, Beffroi et Vieux-Lille en sachets de 100 g, avec une carte de dégustation pour noter vos impressions.',
			),
			'une'         => false,
		),
	);
}

/**
 * Crée ou met à jour les produits ; renvoie les identifiants par sku.
 *
 * @param array<string, int> $categories slug => identifiant.
 * @return array<string, int>
 */
function fabrique_seed_produits( array $categories ): array {
	$ids   = array();
	$ordre = 0;

	foreach ( fabrique_seed_donnees_produits() as $donnees ) {
		$sku       = $donnees['sku'];
		$variable  = is_array( $donnees['prix'] );
		$classe    = $variable ? 'WC_Product_Variable' : 'WC_Product_Simple';
		$existant  = wc_get_product_id_by_sku( $sku );
		$produit   = $existant ? wc_get_product( $existant ) : new $classe();
		$type_veut = $variable ? 'variable' : 'simple';

		if ( $existant && $produit->get_type() !== $type_veut ) {
			// Type changé à la main : on rétablit le type attendu.
			wp_set_object_terms( $existant, $type_veut, 'product_type' );
			$produit = new $classe( $existant );
		}

		$produit->set_name( $donnees['nom'] );
		$produit->set_slug( $sku );
		$produit->set_sku( $sku );
		$produit->set_status( 'publish' );
		$produit->set_catalog_visibility( 'visible' );
		$produit->set_featured( $donnees['une'] );
		$produit->set_menu_order( $ordre++ );
		$produit->set_short_description( $donnees['courte'] );
		$produit->set_description( fabrique_seed_blocs( $donnees['description'] ) );
		$produit->set_category_ids( array( $categories[ $donnees['categorie'] ] ) );
		$produit->set_reviews_allowed( false );

		if ( $variable ) {
			$produit->set_attributes(
				array(
					fabrique_seed_attribut_produit( 'pa_poids', array_keys( $donnees['prix'] ), 0 ),
					fabrique_seed_attribut_produit( 'pa_mouture', array( 'grains', 'espresso', 'filtre', 'piston' ), 1 ),
				)
			);
			$produit->set_default_attributes( array( 'pa_poids' => '250-g' ) );
		} else {
			$produit->set_regular_price( $donnees['prix'] );
			// Stock géré par WooCommerce : décrémenté à chaque commande payée ou « en cours », rendu si elle est annulée.
			$produit->set_manage_stock( true );
			$produit->set_stock_quantity( fabrique_seed_stock_initial( $sku ) );
			$produit->set_low_stock_amount( 3 );
		}

		$id          = (int) $produit->save();
		$ids[ $sku ] = $id;

		if ( $variable ) {
			fabrique_seed_variations( $produit, $donnees['prix'] );
		}

		if ( isset( $donnees['fiche'] ) ) {
			list( $origine, $procede, $altitude, $notes, $intensite, $torrefaction ) = $donnees['fiche'];
			fabrique_seed_champ( $id, 'fabrique_origine', $origine );
			fabrique_seed_champ( $id, 'fabrique_procede', $procede );
			fabrique_seed_champ( $id, 'fabrique_altitude', $altitude );
			fabrique_seed_champ( $id, 'fabrique_notes', $notes );
			fabrique_seed_champ( $id, 'fabrique_intensite', $intensite );
			fabrique_seed_champ( $id, 'fabrique_torrefaction', $torrefaction );
		}

		$alt      = $variable
			? sprintf( 'Sachet en papier kraft du café %s, avec son étiquette tamponnée', $donnees['nom'] )
			: sprintf( 'Illustration : %s', $donnees['nom'] );
		$image_id = fabrique_seed_image( $sku . '.png', $alt, $id );
		if ( $image_id && (int) $produit->get_image_id() !== $image_id ) {
			$produit->set_image_id( $image_id );
			$produit->save();
		}
	}

	fabrique_seed_log( '  produits : ' . implode( ', ', array_keys( $ids ) ) );
	return $ids;
}

/**
 * Une variation par poids (mouture « n'importe laquelle » : le client choisit).
 *
 * @param WC_Product_Variable   $produit Produit parent enregistré.
 * @param array<string, string> $prix    poids => prix.
 */
function fabrique_seed_variations( WC_Product_Variable $produit, array $prix ): void {
	$par_poids = array();
	foreach ( $produit->get_children() as $enfant_id ) {
		$poids = get_post_meta( $enfant_id, 'attribute_pa_poids', true );
		if ( isset( $prix[ $poids ] ) && ! isset( $par_poids[ $poids ] ) ) {
			$par_poids[ $poids ] = $enfant_id;
		} else {
			// Variation en trop (ex. 1 kg retiré de La Braderie) ou doublon.
			wp_delete_post( $enfant_id, true );
		}
	}

	$masses = array(
		'250-g' => '0.25',
		'1-kg'  => '1',
	);
	foreach ( $prix as $poids => $montant ) {
		$variation = new WC_Product_Variation( $par_poids[ $poids ] ?? 0 );
		$variation->set_parent_id( $produit->get_id() );
		$variation->set_attributes(
			array(
				'pa_poids'   => $poids,
				'pa_mouture' => '',
			)
		);
		$variation->set_sku( $produit->get_sku() . '-' . $poids );
		$variation->set_regular_price( $montant );
		$variation->set_weight( $masses[ $poids ] ?? '' );
		$variation->set_status( 'publish' );
		// Un stock par poids : 250 g et 1 kg sont deux références distinctes en entrepôt.
		$variation->set_manage_stock( true );
		$variation->set_stock_quantity( fabrique_seed_stock_initial( $produit->get_sku(), $poids ) );
		$variation->set_low_stock_amount( 5 );
		$variation->save();
	}

	WC_Product_Variable::sync( $produit->get_id() );
}

/**
 * Stock de départ de la démonstration (remis à ces valeurs à chaque lancement du script).
 *
 * @param string $sku   UGS du produit.
 * @param string $poids Slug du poids pour une variation (« 250-g », « 1-kg »), vide pour un produit simple.
 * @return int
 */
function fabrique_seed_stock_initial( string $sku, string $poids = '' ): int {
	if ( '' !== $poids ) {
		$quantites = array(
			'250-g' => 40,
			'1-kg'  => 12,
		);
		// L'édition limitée part vite : peu de sachets.
		return 'braderie' === $sku ? 8 : ( $quantites[ $poids ] ?? 20 );
	}
	$quantites = array(
		'dripper-gres'       => 12,
		'moulin-manuel'      => 6,
		'coffret-decouverte' => 20,
	);
	return $quantites[ $sku ] ?? 10;
}

/**
 * Les six recettes de démonstration.
 *
 * @param array<string, int> $produits sku => identifiant.
 */
function fabrique_seed_recettes( array $produits ): void {
	\Fabrique\Recettes\Plugin::creer_methodes_par_defaut();

	$recettes = array(
		array(
			'slug'     => 'espresso-le-reglage-de-base',
			'titre'    => 'Espresso, le réglage de base',
			'methode'  => 'espresso',
			'extrait'  => '18 g de café, 36 g en tasse, une trentaine de secondes : le point de départ pour régler sa machine sans s’arracher les cheveux.',
			'intro'    => array(
				'Un espresso réussi, c’est d’abord une recette qu’on ne change pas tous les matins. On fixe la dose et le poids en tasse, puis on ne joue que sur la mouture.',
			),
			'materiel' => array( 'Machine à espresso et porte-filtre double', 'Balance au dixième de gramme', 'Moulin réglé en mouture fine', 'Tasse préchauffée' ),
			'etapes'   => array(
				'Purgez le groupe quelques secondes et essuyez le panier : il doit être sec.',
				'Pesez 18 g de café fraîchement moulu, mouture fine.',
				'Répartissez la mouture, puis tassez bien à plat, sans forcer.',
				'Lancez l’extraction : visez 36 g de café en tasse en 25 à 30 secondes.',
				'Goûtez avant de toucher au réglage : trop acide et rapide, moulez plus fin ; amer et long, moulez plus gros.',
			),
			'champs'   => array(
				'temps'       => 5,
				'difficulte'  => 'moyen',
				'dose_cafe'   => 18,
				'volume_eau'  => 36,
				'temperature' => 93,
				'mouture'     => 'fine',
				'astuce'      => 'Un seul réglage à la fois, et toujours goûter entre deux. Le chronomètre aide, la langue décide.',
			),
			'cafes'    => array( 'wazemmes' ),
			'alt'      => 'Tasse d’espresso en grès sous le porte-filtre, avec sa crème noisette',
		),
		array(
			'slug'     => 'v60-du-dimanche',
			'titre'    => 'V60 du dimanche',
			'methode'  => 'filtre-doux',
			'extrait'  => 'Un filtre doux et parfumé, à préparer sans se presser : 15 g de Beffroi, 250 ml d’eau à 94 °C.',
			'intro'    => array(
				'Le dimanche, on a le temps de regarder le café gonfler. Le V60 révèle les cafés floraux comme le Beffroi : jasmin, bergamote, pêche.',
			),
			'materiel' => array( 'Dripper V60 (ou dripper en grès) et filtre papier', 'Bouilloire à col de cygne', 'Balance et minuteur', 'Verseuse ou grande tasse' ),
			'etapes'   => array(
				'Rincez le filtre à l’eau chaude, puis videz la verseuse.',
				'Versez 15 g de café moulu moyen-fin et creusez un petit puits au centre.',
				'Pré-infusion : versez 40 ml d’eau à 94 °C et attendez 30 secondes que le café gonfle.',
				'Versez le reste de l’eau en trois fois, en spirale, jusqu’à 250 ml.',
				'L’écoulement doit se terminer vers 3 min 30. Remuez la verseuse et servez.',
			),
			'champs'   => array(
				'temps'       => 4,
				'difficulte'  => 'facile',
				'dose_cafe'   => 15,
				'volume_eau'  => 250,
				'temperature' => 94,
				'mouture'     => 'moyenne-fine',
				'astuce'      => 'Versez en spirale, jamais sur le papier : l’eau doit traverser le café, pas le contourner.',
			),
			'cafes'    => array( 'beffroi' ),
			'alt'      => 'Dripper en grès posé sur une verseuse en verre, filet d’eau en spirale',
		),
		array(
			'slug'     => 'cold-brew-12-heures',
			'titre'    => 'Cold brew 12 heures',
			'methode'  => 'cold-brew',
			'extrait'  => 'Infusé à froid toute une nuit : un concentré doux, peu acide, à allonger d’eau ou de lait. 80 g pour un litre.',
			'intro'    => array(
				'Le cold brew ne demande ni bouilloire ni technique, seulement de la patience. Le froid extrait lentement les sucres et laisse l’amertume de côté.',
			),
			'materiel' => array( 'Grand bocal d’un litre avec couvercle', 'Filtre en tissu ou filtre papier', 'Eau froide filtrée' ),
			'etapes'   => array(
				'Moulez 80 g de café en mouture grossière, comme du gros sel.',
				'Versez le café dans le bocal, ajoutez 1 000 ml d’eau froide et remuez doucement.',
				'Fermez et laissez infuser 12 heures au réfrigérateur.',
				'Filtrez lentement, sans presser le marc.',
				'Servez sur glace, allongé moitié-moitié avec de l’eau ou du lait.',
			),
			'champs'   => array(
				'temps'       => 720,
				'difficulte'  => 'facile',
				'dose_cafe'   => 80,
				'volume_eau'  => 1000,
				'temperature' => null,
				'mouture'     => 'grossiere',
				'astuce'      => 'Le concentré se garde trois jours au frais, pas plus : au-delà, il s’oxyde et perd ses notes de caramel.',
			),
			'cafes'    => array( 'vieux-lille' ),
			'alt'      => 'Bocal de cold brew avec glaçons et carafe, sur un plan de travail',
		),
		array(
			'slug'     => 'latte-au-speculoos',
			'titre'    => 'Latte au spéculoos',
			'methode'  => 'au-lait',
			'extrait'  => 'Le latte du Nord : un double espresso de Wazemmes, du lait soyeux et une pincée de spéculoos émietté.',
			'intro'    => array(
				'Les notes chocolatées de Wazemmes s’entendent à merveille avec le biscuit à la cannelle. Pas de sirop : le spéculoos se pose sur la mousse, au dernier moment.',
			),
			'materiel' => array( 'Machine à espresso avec buse vapeur', 'Pichet à lait', 'Grande tasse (30 cl)', 'Deux spéculoos' ),
			'etapes'   => array(
				'Préparez un double espresso : 18 g de café pour 36 g en tasse.',
				'Faites mousser 200 ml de lait entier jusqu’à 60 °C environ : la texture doit rester brillante.',
				'Versez le lait sur l’espresso en commençant haut, puis rapprochez le pichet pour dessiner.',
				'Émiettez un spéculoos sur la mousse et servez le second à côté.',
			),
			'champs'   => array(
				'temps'       => 8,
				'difficulte'  => 'moyen',
				'dose_cafe'   => 18,
				'volume_eau'  => 36,
				'temperature' => 93,
				'mouture'     => 'fine',
				'astuce'      => 'Le spéculoos se pose sur la mousse, jamais dans le lait : il reste croquant jusqu’à la dernière gorgée.',
			),
			'cafes'    => array( 'wazemmes' ),
			'alt'      => 'Latte en tasse de grès, mousse dessinée et spéculoos émietté',
		),
		array(
			'slug'     => 'cafe-chicoree-a-lancienne',
			'titre'    => 'Café-chicorée à l’ancienne',
			'methode'  => 'filtre-doux',
			'extrait'  => 'Le café de nos grands-mères du Nord, revisité avec un vrai café de spécialité : rond, doux, un brin torréfié.',
			'intro'    => array(
				'Dans le Nord, la chicorée a longtemps accompagné le café du matin. Mélangée à l’assemblage Fives, elle apporte une rondeur caramélisée sans masquer le café.',
			),
			'materiel' => array( 'Cafetière filtre ou dripper', 'Filtre papier', 'Chicorée torréfiée en grains ou soluble' ),
			'etapes'   => array(
				'Mélangez 15 g de café moulu moyen et une cuillère à café de chicorée.',
				'Placez le mélange dans le filtre rincé.',
				'Versez 300 ml d’eau à 92 °C, d’abord un peu pour mouiller, puis le reste en deux fois.',
				'Servez bien chaud, avec un sucre en morceau pour les nostalgiques.',
			),
			'champs'   => array(
				'temps'       => 10,
				'difficulte'  => 'facile',
				'dose_cafe'   => 15,
				'volume_eau'  => 300,
				'temperature' => 92,
				'mouture'     => 'moyenne',
				'astuce'      => 'Une cuillère de chicorée pour quinze grammes de café : au-delà, c’est la chicorée qui parle.',
			),
			'cafes'    => array( 'fives' ),
			'alt'      => 'Bol de café-chicorée fumant à côté d’une boîte de chicorée',
		),
		array(
			'slug'     => 'affogato-du-vieux-lille',
			'titre'    => 'Affogato du Vieux-Lille',
			'methode'  => 'au-lait',
			'extrait'  => 'Une boule de glace vanille noyée sous un espresso brûlant. Version décaféinée possible pour les fins de soirée.',
			'intro'    => array(
				'L’affogato, c’est le dessert qui tient dans une tasse. Le Vieux-Lille apporte le caramel, la Nuit Blanche permet d’en profiter après le dîner.',
			),
			'materiel' => array( 'Machine à espresso', 'Glace vanille de qualité', 'Verre ou tasse refroidis' ),
			'etapes'   => array(
				'Placez une belle boule de glace vanille dans un verre sorti du congélateur.',
				'Préparez un espresso : 18 g de café pour 36 g en tasse.',
				'Versez l’espresso brûlant directement sur la glace, devant vos invités.',
				'Dégustez tout de suite, à la petite cuillère.',
			),
			'champs'   => array(
				'temps'       => 5,
				'difficulte'  => 'facile',
				'dose_cafe'   => 18,
				'volume_eau'  => 36,
				'temperature' => 93,
				'mouture'     => 'fine',
				'astuce'      => 'Avec Nuit Blanche, l’affogato se déguste aussi après 22 h sans compter les moutons.',
			),
			'cafes'    => array( 'vieux-lille', 'nuit-blanche' ),
			'alt'      => 'Verre d’affogato : boule de vanille sous un filet d’espresso',
		),
	);

	$date = strtotime( '2026-09-01 09:00:00' );
	foreach ( $recettes as $index => $recette ) {
		$id = fabrique_seed_contenu(
			array(
				'post_type'    => \Fabrique\Recettes\Plugin::TYPE,
				'post_name'    => $recette['slug'],
				'post_title'   => $recette['titre'],
				'post_status'  => 'publish',
				'post_excerpt' => $recette['extrait'],
				'post_content' => fabrique_seed_blocs(
					$recette['intro'],
					array(
						array(
							'titre' => 'Matériel',
							'items' => $recette['materiel'],
						),
						array(
							'titre'    => 'Étapes',
							'items'    => $recette['etapes'],
							'numerote' => true,
						),
					)
				),
				// Dates échelonnées : l'ordre de la route REST (du plus récent au plus ancien) est stable.
				'post_date'    => gmdate( 'Y-m-d H:i:s', $date + $index * WEEK_IN_SECONDS ),
			)
		);

		wp_set_object_terms( $id, $recette['methode'], \Fabrique\Recettes\Plugin::TAXONOMIE );

		foreach ( $recette['champs'] as $nom => $valeur ) {
			fabrique_seed_champ( $id, 'fabrique_' . $nom, $valeur );
		}
		$cafes = array();
		foreach ( $recette['cafes'] as $sku ) {
			if ( isset( $produits[ $sku ] ) ) {
				$cafes[] = (string) $produits[ $sku ];
			}
		}
		fabrique_seed_champ( $id, 'fabrique_cafes', $cafes );

		$image_id = fabrique_seed_image( 'recette-' . $recette['slug'] . '.png', $recette['alt'], $id );
		if ( $image_id ) {
			set_post_thumbnail( $id, $image_id );
		}
	}
	fabrique_seed_log( '  recettes : ' . count( $recettes ) );
}

/**
 * Pages : accueil, pages WooCommerce (shortcodes classiques), L'atelier, Mentions & démo.
 *
 * @return array<string, int> clé => identifiant.
 */
function fabrique_seed_pages(): array {
	$atelier = fabrique_seed_blocs(
		array(
			'Chaque mardi, l’odeur du café torréfié remplit une ancienne filature du quartier de Fives. C’est là que La Fabrique torréfie ses cafés de spécialité, en petits lots de quinze kilos, pour les tasses du quartier et celles de toute la France.',
			'Nous achetons nos cafés verts à des importateurs qui connaissent les producteurs par leur prénom. Chaque lot porte un numéro et une date de torréfaction : vous savez toujours ce que vous buvez, et depuis quand.',
			'Le reste du temps, l’atelier sert de salle de dégustation. On y partage des recettes, on y règle des moulins, on y refait le monde entre deux spéculoos.',
			'Projet de démonstration, marque fictive : La Fabrique n’existe pas, et l’atelier de Fives non plus.',
		),
		array(
			array(
				'titre' => 'Ce qui nous tient à cœur',
				'items' => array(
					'Torréfier peu, souvent, et le dire : la date est sur chaque sachet.',
					'Des cafés traçables, payés au-dessus des cours du marché.',
					'Des recettes simples, testées au comptoir avant d’être publiées.',
				),
			),
		)
	);

	$mentions = fabrique_seed_blocs(
		array(
			'Ce site est un projet de démonstration réalisé pour un portfolio. La Fabrique est une marque fictive : aucune commande n’est expédiée, aucun paiement n’est encaissé.',
			'Contact : bonjour@la-fabrique.test (adresse de démonstration).',
			'Typographies Instrument Serif et Instrument Sans, sous licence SIL Open Font License. Illustrations générées par script pour le projet.',
		),
		array(
			array(
				'titre' => 'Données personnelles',
				'items' => array(
					'Les comptes clients et les commandes de démonstration restent sur le serveur de démonstration.',
					'La connexion avec Google transmet votre nom, votre adresse e-mail et un identifiant Google ; seul cet identifiant est conservé pour vous reconnaître.',
					'Cookies : panier et session WooCommerce, connexion WordPress, et un cookie technique de dix minutes pendant la connexion avec Google.',
				),
			),
		)
	);

	$pages = array(
		'accueil'    => array( 'accueil', 'Accueil', fabrique_seed_blocs( array( 'Cafés de spécialité torréfiés chaque mardi à Fives, recettes partagées et tasses de quartier.' ) ) ),
		'boutique'   => array( 'boutique', 'Boutique', '' ),
		'panier'     => array( 'panier', 'Panier', "<!-- wp:shortcode -->\n[woocommerce_cart]\n<!-- /wp:shortcode -->" ),
		'commande'   => array( 'commande', 'Commande', "<!-- wp:shortcode -->\n[woocommerce_checkout]\n<!-- /wp:shortcode -->" ),
		'mon-compte' => array( 'mon-compte', 'Mon compte', "<!-- wp:shortcode -->\n[woocommerce_my_account]\n<!-- /wp:shortcode -->" ),
		'atelier'    => array( 'atelier', 'L’atelier', $atelier ),
		'mentions'   => array( 'mentions-demo', 'Mentions & démo', $mentions ),
	);

	// Pages déjà créées par WooCommerce : on les reprend (et on les renomme) plutôt que d'en créer d'autres.
	$options_wc = array(
		'boutique'   => 'woocommerce_shop_page_id',
		'panier'     => 'woocommerce_cart_page_id',
		'commande'   => 'woocommerce_checkout_page_id',
		'mon-compte' => 'woocommerce_myaccount_page_id',
	);

	$ids = array();
	foreach ( $pages as $cle => list( $slug, $titre, $contenu ) ) {
		$existant = isset( $options_wc[ $cle ] ) ? (int) get_option( $options_wc[ $cle ] ) : 0;
		if ( $existant && 'page' === get_post_type( $existant ) && 'trash' !== get_post_status( $existant ) ) {
			$autre = fabrique_seed_trouver( 'page', $slug );
			if ( $autre && $autre !== $existant ) {
				// Une page porte déjà le slug voulu : on garde celle de WooCommerce et on écarte l'autre.
				wp_trash_post( $autre );
			}
			wp_update_post(
				wp_slash(
					array(
						'ID'           => $existant,
						'post_name'    => $slug,
						'post_title'   => $titre,
						'post_content' => $contenu,
						'post_status'  => 'publish',
					)
				)
			);
			$ids[ $cle ] = $existant;
		} else {
			$ids[ $cle ] = fabrique_seed_contenu(
				array(
					'post_type'    => 'page',
					'post_name'    => $slug,
					'post_title'   => $titre,
					'post_content' => $contenu,
					'post_status'  => 'publish',
				)
			);
		}
		if ( isset( $options_wc[ $cle ] ) ) {
			update_option( $options_wc[ $cle ], $ids[ $cle ] );
		}
	}

	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $ids['accueil'] );

	// Contenus d'exemple de WordPress inutiles pour la démo.
	foreach ( array(
		'post' => 'hello-world',
		'page' => 'sample-page',
	) as $type => $slug ) {
		$exemple = fabrique_seed_trouver( $type, $slug );
		if ( $exemple ) {
			wp_delete_post( $exemple, true );
		}
	}

	fabrique_seed_log( '  pages : ' . implode( ', ', array_keys( $ids ) ) );
	return $ids;
}

/**
 * Crée ou complète un menu, puis l'affecte à un emplacement du thème la-fabrique.
 *
 * @param string                           $nom         Nom du menu.
 * @param string                           $emplacement Emplacement déclaré par le thème.
 * @param array<int, array<string, mixed>> $elements    Éléments : type, objet, objet_id, titre.
 */
function fabrique_seed_menu( string $nom, string $emplacement, array $elements ): void {
	$menu    = wp_get_nav_menu_object( $nom );
	$menu_id = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu( $nom );
	if ( ! $menu_id ) {
		fabrique_seed_erreur( "Menu {$nom} impossible à créer." );
	}

	$existants = wp_get_nav_menu_items( $menu_id, array( 'post_status' => 'any' ) );
	$existants = is_array( $existants ) ? $existants : array();

	foreach ( $elements as $position => $element ) {
		$element_id = 0;
		foreach ( $existants as $item ) {
			if ( $item->type === $element['type'] && $item->object === $element['objet']
				&& ( 'post_type_archive' === $element['type'] || (int) $item->object_id === (int) $element['objet_id'] ) ) {
				$element_id = (int) $item->db_id;
				break;
			}
		}
		wp_update_nav_menu_item(
			$menu_id,
			$element_id,
			array(
				'menu-item-type'      => $element['type'],
				'menu-item-object'    => $element['objet'],
				'menu-item-object-id' => (int) $element['objet_id'],
				'menu-item-title'     => $element['titre'],
				'menu-item-position'  => $position + 1,
				'menu-item-status'    => 'publish',
			)
		);
	}

	// Emplacement du thème « la-fabrique », même s'il n'est pas encore actif.
	$mods = get_option( 'theme_mods_la-fabrique', array() );
	$mods = is_array( $mods ) ? $mods : array();

	$mods['nav_menu_locations']                 = isset( $mods['nav_menu_locations'] ) && is_array( $mods['nav_menu_locations'] ) ? $mods['nav_menu_locations'] : array();
	$mods['nav_menu_locations'][ $emplacement ] = $menu_id;
	update_option( 'theme_mods_la-fabrique', $mods );
}

/**
 * Menus « principal » et « pied-de-page ».
 *
 * @param array<string, int> $categories slug => identifiant.
 * @param array<string, int> $pages      clé => identifiant.
 */
function fabrique_seed_menus( array $categories, array $pages ): void {
	$recettes = array(
		'type'     => 'post_type_archive',
		'objet'    => \Fabrique\Recettes\Plugin::TYPE,
		'objet_id' => 0,
		'titre'    => 'Recettes',
	);
	$atelier  = array(
		'type'     => 'post_type',
		'objet'    => 'page',
		'objet_id' => $pages['atelier'],
		'titre'    => 'L’atelier',
	);

	fabrique_seed_menu(
		'Menu principal',
		'principal',
		array(
			array(
				'type'     => 'taxonomy',
				'objet'    => 'product_cat',
				'objet_id' => $categories['cafes'],
				'titre'    => 'Cafés',
			),
			array(
				'type'     => 'taxonomy',
				'objet'    => 'product_cat',
				'objet_id' => $categories['accessoires'],
				'titre'    => 'Accessoires',
			),
			$recettes,
			$atelier,
		)
	);

	fabrique_seed_menu(
		'Pied de page',
		'pied-de-page',
		array(
			array(
				'type'     => 'post_type',
				'objet'    => 'page',
				'objet_id' => $pages['boutique'],
				'titre'    => 'Boutique',
			),
			$recettes,
			$atelier,
			array(
				'type'     => 'post_type',
				'objet'    => 'page',
				'objet_id' => $pages['mentions'],
				'titre'    => 'Mentions & démo',
			),
			array(
				'type'     => 'post_type',
				'objet'    => 'page',
				'objet_id' => $pages['mon-compte'],
				'titre'    => 'Mon compte',
			),
		)
	);
	fabrique_seed_log( '  menus : principal, pied-de-page' );
}

/**
 * Point d'entrée.
 */
function fabrique_seed(): void {
	if ( ! class_exists( 'WooCommerce' ) ) {
		fabrique_seed_erreur( 'WooCommerce doit être actif.' );
	}
	if ( ! function_exists( 'fabrique_recette_infos' ) ) {
		fabrique_seed_erreur( 'Le plugin fabrique-recettes doit être actif.' );
	}

	// Les contenus sont créés au nom d'un administrateur (auteur, filtres HTML de confiance).
	if ( ! get_current_user_id() ) {
		$admins = get_users(
			array(
				'role'   => 'administrator',
				'number' => 1,
				'fields' => 'ID',
			)
		);
		if ( $admins ) {
			wp_set_current_user( (int) $admins[0] );
		}
	}

	if ( ! is_dir( fabrique_seed_dossier_images() ) || ! glob( fabrique_seed_dossier_images() . '/*.png' ) ) {
		fabrique_seed_log( '(aucune illustration dans seed/images pour l’instant : contenus créés sans image)' );
	}

	fabrique_seed_log( 'Contenus de démonstration…' );
	$categories = fabrique_seed_categories();
	fabrique_seed_attribut(
		'poids',
		'Poids',
		array(
			'250-g' => '250 g',
			'1-kg'  => '1 kg',
		)
	);
	fabrique_seed_attribut(
		'mouture',
		'Mouture',
		array(
			'grains'   => 'En grains',
			'espresso' => 'Espresso',
			'filtre'   => 'Filtre',
			'piston'   => 'Piston',
		)
	);
	$produits = fabrique_seed_produits( $categories );
	fabrique_seed_recettes( $produits );
	$pages = fabrique_seed_pages();
	fabrique_seed_menus( $categories, $pages );

	flush_rewrite_rules( false );
	wc_delete_product_transients();
	fabrique_seed_log( 'Terminé.' );
}

fabrique_seed();
