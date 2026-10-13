<?php
/**
 * WooCommerce : toute la personnalisation passe par des hooks.
 *
 * Aucun gabarit WooCommerce n'est copié dans le thème : la mise en page, les cartes produits,
 * la fiche produit et le tunnel de commande sont réorganisés en retirant et en ajoutant des actions,
 * puis habillés par assets/css/woocommerce.css. Le thème reste ainsi à jour quand WooCommerce
 * fait évoluer ses gabarits.
 *
 * Sommaire :
 *  1. Supports, images, styles et scripts
 *  2. En-tête : compte, panier, compteur (fragments) et mini-panier
 *  3. Conteneurs et fil d'Ariane
 *  4. Archives : en-tête, catégories, barre d'outils
 *  5. Cartes produits
 *  6. Fiche produit : résumé, fiche de dégustation, recettes, produits associés
 *  7. Panier, commande, compte : étapes du tunnel, libellés
 *  8. Libellés en français
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;

/*
 * 1. Supports, images, styles et scripts
 * --------------------------------------------------------------------------------------------
 */

/**
 * Déclare le support de WooCommerce (tailles d'images et grille du catalogue).
 */
function la_fabrique_wc_setup() {
	add_theme_support(
		'woocommerce',
		array(
			'thumbnail_image_width' => 600,
			'single_image_width'    => 900,
			'product_grid'          => array(
				'default_rows'    => 4,
				'min_rows'        => 1,
				'default_columns' => 3,
				'min_columns'     => 2,
				'max_columns'     => 4,
			),
		)
	);
}
add_action( 'after_setup_theme', 'la_fabrique_wc_setup' );

/**
 * Vignettes du catalogue au format portrait 4:5, celui des illustrations des produits.
 *
 * @return array
 */
function la_fabrique_wc_taille_vignette() {
	return array(
		'width'  => 600,
		'height' => 750,
		'crop'   => 1,
	);
}
add_filter( 'woocommerce_get_image_size_thumbnail', 'la_fabrique_wc_taille_vignette' );

/**
 * Miniatures de la galerie et du panier au même format 4:5.
 *
 * @return array
 */
function la_fabrique_wc_taille_miniature() {
	return array(
		'width'  => 160,
		'height' => 200,
		'crop'   => 1,
	);
}
add_filter( 'woocommerce_get_image_size_gallery_thumbnail', 'la_fabrique_wc_taille_miniature' );

// Les styles de WooCommerce sont remplacés par ceux du thème (assets/css/woocommerce.css).
add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );

/**
 * Styles et scripts de la boutique.
 *
 * Les fragments du panier ne sont plus chargés partout depuis WooCommerce 7.8 : l'en-tête
 * affiche un compteur et un mini-panier, on les charge donc nous-mêmes.
 */
function la_fabrique_wc_assets() {
	wp_enqueue_style( 'la-fabrique-woocommerce', la_fabrique_uri( 'assets/css/woocommerce.css' ), array( 'la-fabrique' ), la_fabrique_version( 'assets/css/woocommerce.css' ) );
	wp_enqueue_script( 'wc-cart-fragments' );
	wp_enqueue_script(
		'la-fabrique-woocommerce',
		la_fabrique_uri( 'assets/js/woocommerce.js' ),
		array( 'jquery' ),
		la_fabrique_version( 'assets/js/woocommerce.js' ),
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);
	wp_add_inline_script(
		'la-fabrique-woocommerce',
		'window.laFabriqueBoutique = ' . wp_json_encode(
			array(
				'ajoute'  => __( 'Produit ajouté au panier.', 'la-fabrique' ),
				'retirer' => __( 'Retirer un', 'la-fabrique' ),
				'ajouter' => __( 'Ajouter un', 'la-fabrique' ),
			)
		) . ';',
		'before'
	);
}
add_action( 'wp_enqueue_scripts', 'la_fabrique_wc_assets', 15 );

/*
 * 2. En-tête : compte, panier, compteur et mini-panier
 * --------------------------------------------------------------------------------------------
 */

/**
 * Pastille du nombre d'articles (fragment remplacé après chaque ajout en AJAX).
 *
 * @return string
 */
function la_fabrique_wc_compteur() {
	$nombre = ( WC()->cart ) ? (int) WC()->cart->get_cart_contents_count() : 0;
	return sprintf(
		'<span class="lf-panier-compteur" data-nombre="%1$d"><span aria-hidden="true">%1$d</span><span class="screen-reader-text">%2$s</span></span>',
		$nombre,
		/* translators: %d : nombre d'articles dans le panier. */
		esc_html( sprintf( _n( '%d article', '%d articles', $nombre, 'la-fabrique' ), $nombre ) )
	);
}

/**
 * Liens « Mon compte » et « Panier » de l'en-tête.
 */
function la_fabrique_wc_actions_entete() {
	?>
	<a class="lf-action lf-action--compte" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">
		<?php la_fabrique_icone( 'compte' ); ?>
		<span class="screen-reader-text"><?php is_user_logged_in() ? esc_html_e( 'Mon compte', 'la-fabrique' ) : esc_html_e( 'Se connecter', 'la-fabrique' ); ?></span>
	</a>
	<a class="lf-action lf-action--panier" href="<?php echo esc_url( wc_get_cart_url() ); ?>"<?php echo ( is_cart() || is_checkout() ) ? '' : ' data-lf-dialogue="lf-panier"'; ?>>
		<?php la_fabrique_icone( 'panier' ); ?>
		<span class="screen-reader-text"><?php esc_html_e( 'Panier', 'la-fabrique' ); ?></span>
		<?php echo wp_kses_post( la_fabrique_wc_compteur() ); ?>
	</a>
	<?php
}
add_action( 'la_fabrique_actions_entete', 'la_fabrique_wc_actions_entete' );

/**
 * Fragments AJAX : le compteur de l'en-tête suit le panier sans rechargement.
 * (Le contenu du mini-panier, div.widget_shopping_cart_content, est déjà un fragment natif.)
 *
 * @param array $fragments Sélecteur CSS => HTML de remplacement.
 * @return array
 */
function la_fabrique_wc_fragments( $fragments ) {
	$fragments['span.lf-panier-compteur'] = la_fabrique_wc_compteur();
	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'la_fabrique_wc_fragments' );

/**
 * Mini-panier dans un panneau latéral (<dialog>), sauf sur les pages Panier et Commande.
 */
function la_fabrique_wc_panneau_panier() {
	if ( is_cart() || is_checkout() ) {
		return;
	}
	?>
	<dialog id="lf-panier" class="lf-panneau lf-panneau--panier" aria-labelledby="lf-panier-titre">
		<div class="lf-panneau__tete">
			<h2 class="lf-panneau__titre" id="lf-panier-titre"><?php esc_html_e( 'Votre panier', 'la-fabrique' ); ?></h2>
			<button type="button" class="lf-action" data-lf-ferme>
				<?php la_fabrique_icone( 'fermer' ); ?>
				<span class="screen-reader-text"><?php esc_html_e( 'Fermer le panier', 'la-fabrique' ); ?></span>
			</button>
		</div>
		<div class="widget_shopping_cart_content"><?php woocommerce_mini_cart(); ?></div>
	</dialog>
	<?php
}
add_action( 'la_fabrique_panneaux', 'la_fabrique_wc_panneau_panier' );

/*
 * 3. Conteneurs et fil d'Ariane
 * --------------------------------------------------------------------------------------------
 */

remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );

/**
 * Ouvre la zone principale du thème (lien d'évitement → #contenu).
 */
function la_fabrique_wc_debut() {
	echo '<main id="contenu" class="lf-main lf-boutique" tabindex="-1"><div class="lf-conteneur">';
}
add_action( 'woocommerce_before_main_content', 'la_fabrique_wc_debut', 10 );

/**
 * Ferme la zone principale.
 */
function la_fabrique_wc_fin() {
	echo '</div></main>';
}
add_action( 'woocommerce_after_main_content', 'la_fabrique_wc_fin', 10 );

/**
 * Fil d'Ariane : même balisage que celui du thème (liste ordonnée, séparateurs en CSS).
 *
 * @return array
 */
function la_fabrique_wc_ariane() {
	return array(
		'delimiter'   => '',
		'wrap_before' => '<nav class="lf-ariane" aria-label="' . esc_attr__( 'Fil d’Ariane', 'la-fabrique' ) . '"><ol class="lf-ariane__liste">',
		'wrap_after'  => '</ol></nav>',
		'before'      => '<li>',
		'after'       => '</li>',
		'home'        => _x( 'Accueil', 'fil d’Ariane', 'la-fabrique' ),
	);
}
add_filter( 'woocommerce_breadcrumb_defaults', 'la_fabrique_wc_ariane' );

/*
 * 4. Archives : en-tête, catégories, barre d'outils
 * --------------------------------------------------------------------------------------------
 */

remove_action( 'woocommerce_shop_loop_header', 'woocommerce_product_taxonomy_archive_header' );

/**
 * En-tête des archives : titre, introduction et catégories en puces (vrais liens).
 */
function la_fabrique_wc_entete_archive() {
	$intro = '';
	if ( is_product_taxonomy() ) {
		$intro = term_description();
	} elseif ( is_shop() ) {
		$page  = get_post( wc_get_page_id( 'shop' ) );
		$intro = $page ? wpautop( wp_kses_post( $page->post_content ) ) : '';
		if ( '' === trim( wp_strip_all_tags( $intro ) ) ) {
			$intro = '<p>' . esc_html__( 'Des cafés torréfiés le mardi, de quoi les préparer, et des coffrets pour les offrir.', 'la-fabrique' ) . '</p>';
		}
	}

	$categories = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'parent'     => 0,
			'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
		)
	);
	$courante   = is_product_category() ? get_queried_object_id() : 0;
	?>
	<header class="lf-page-tete lf-boutique__tete">
		<h1 class="lf-page-tete__titre" data-lf-revele="lignes"><?php woocommerce_page_title(); ?></h1>
		<?php if ( $intro ) : ?>
			<div class="lf-page-tete__intro"><?php echo wp_kses_post( $intro ); ?></div>
		<?php endif; ?>
		<?php if ( ! is_wp_error( $categories ) && $categories ) : ?>
			<nav class="lf-filtres" aria-label="<?php esc_attr_e( 'Catégories de la boutique', 'la-fabrique' ); ?>">
				<ul class="lf-puces">
					<li>
						<a class="lf-puce" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"<?php echo is_shop() ? ' aria-current="page"' : ''; ?>><?php esc_html_e( 'Tout', 'la-fabrique' ); ?></a>
					</li>
					<?php foreach ( $categories as $categorie ) : ?>
						<li>
							<a class="lf-puce" href="<?php echo esc_url( get_term_link( $categorie ) ); ?>"<?php echo ( $courante === $categorie->term_id ) ? ' aria-current="page"' : ''; ?>>
								<?php echo esc_html( $categorie->name ); ?>
								<span class="lf-puce__nombre"><?php echo (int) $categorie->count; ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</nav>
		<?php endif; ?>
	</header>
	<?php
}
add_action( 'woocommerce_shop_loop_header', 'la_fabrique_wc_entete_archive' );

/**
 * Barre d'outils (nombre de résultats et tri) : on encadre les deux actions natives.
 */
function la_fabrique_wc_outils_debut() {
	echo '<div class="lf-boutique__outils">';
}
add_action( 'woocommerce_before_shop_loop', 'la_fabrique_wc_outils_debut', 15 );

/**
 * Fin de la barre d'outils.
 */
function la_fabrique_wc_outils_fin() {
	echo '</div>';
}
add_action( 'woocommerce_before_shop_loop', 'la_fabrique_wc_outils_fin', 35 );

/**
 * Douze produits par page sur trois colonnes.
 *
 * @return int
 */
function la_fabrique_wc_par_page() {
	return 12;
}
add_filter( 'loop_shop_per_page', 'la_fabrique_wc_par_page' );

/**
 * Trois colonnes dans le catalogue.
 *
 * @return int
 */
function la_fabrique_wc_colonnes() {
	return 3;
}
add_filter( 'loop_shop_columns', 'la_fabrique_wc_colonnes' );

/*
 * 5. Cartes produits
 * --------------------------------------------------------------------------------------------
 */

// Le grand lien qui entoure image, titre et prix est remplacé par un lien « étiré » sur le titre :
// un seul lien par carte pour les lecteurs d'écran, et le bouton reste un élément distinct.
remove_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );

/**
 * Sépare le nom d'un produit et sa précision entre parenthèses : « Nuit Blanche (décaféiné) ».
 *
 * @param string $nom Nom complet.
 * @return array{nom: string, mention: string}
 */
function la_fabrique_wc_nom( $nom ) {
	if ( preg_match( '/^(.+?)\s*\(([^)]+)\)\s*$/u', $nom, $morceaux ) ) {
		return array(
			'nom'     => $morceaux[1],
			'mention' => $morceaux[2],
		);
	}
	return array(
		'nom'     => $nom,
		'mention' => '',
	);
}

/**
 * Ouvre la zone visuelle de la carte (image et pastilles).
 */
function la_fabrique_wc_carte_visuel_debut() {
	echo '<div class="lf-produit__visuel">';
}
add_action( 'woocommerce_before_shop_loop_item_title', 'la_fabrique_wc_carte_visuel_debut', 5 );

/**
 * Ferme la zone visuelle et ouvre le corps de la carte.
 */
function la_fabrique_wc_carte_corps_debut() {
	echo '</div><div class="lf-produit__corps">';
}
add_action( 'woocommerce_before_shop_loop_item_title', 'la_fabrique_wc_carte_corps_debut', 20 );

/**
 * Titre de la carte : lien étiré et mention (édition limitée, décaféiné…) en pastille.
 */
function la_fabrique_wc_carte_titre() {
	global $product;
	$nom    = la_fabrique_wc_nom( $product->get_name() );
	$niveau = 'h3';
	if ( is_shop() || is_product_taxonomy() ) {
		// Dans le catalogue, le titre de la page est le h1 : les cartes sont des h2.
		$niveau = 'h2';
	}
	printf(
		'<%1$s class="lf-produit__titre"><a class="lf-carte__lien" href="%2$s">%3$s</a></%1$s>',
		esc_attr( $niveau ),
		esc_url( get_permalink( $product->get_id() ) ),
		esc_html( $nom['nom'] )
	);
	if ( $nom['mention'] ) {
		printf( '<p class="lf-produit__mention">%s</p>', esc_html( $nom['mention'] ) );
	}
}
add_action( 'woocommerce_shop_loop_item_title', 'la_fabrique_wc_carte_titre', 10 );

/**
 * Origine et notes du café (fabrique_cafe_fiche) ; description courte pour les autres produits.
 */
function la_fabrique_wc_carte_fiche() {
	global $product;
	$fiche = la_fabrique_fiche_cafe( $product->get_id() );
	if ( $fiche['origine'] || $fiche['notes'] ) {
		if ( $fiche['origine'] ) {
			printf( '<p class="lf-produit__origine">%s</p>', esc_html( $fiche['origine'] ) );
		}
		if ( $fiche['notes'] ) {
			printf(
				'<p class="lf-produit__notes"><span class="screen-reader-text">%1$s </span>%2$s</p>',
				esc_html__( 'Notes :', 'la-fabrique' ),
				esc_html( implode( ', ', $fiche['notes'] ) )
			);
		}
		return;
	}
	$extrait = wp_strip_all_tags( $product->get_short_description() );
	if ( $extrait ) {
		printf( '<p class="lf-produit__notes">%s</p>', esc_html( wp_trim_words( $extrait, 14, '…' ) ) );
	}
}
add_action( 'woocommerce_after_shop_loop_item_title', 'la_fabrique_wc_carte_fiche', 7 );

/**
 * Pied de carte : prix et bouton côte à côte.
 */
function la_fabrique_wc_carte_pied_debut() {
	echo '<div class="lf-produit__pied">';
	woocommerce_template_loop_price();
}
add_action( 'woocommerce_after_shop_loop_item', 'la_fabrique_wc_carte_pied_debut', 7 );

/**
 * Ferme le pied et le corps de la carte.
 */
function la_fabrique_wc_carte_pied_fin() {
	echo '</div></div>';
}
add_action( 'woocommerce_after_shop_loop_item', 'la_fabrique_wc_carte_pied_fin', 20 );

/**
 * Prix des produits variables : « dès 9,90 € » plutôt qu'une fourchette.
 *
 * @param string     $prix    HTML du prix.
 * @param WC_Product $produit Produit variable.
 * @return string
 */
function la_fabrique_wc_prix_variable( $prix, $produit ) {
	$min = $produit->get_variation_price( 'min', true );
	$max = $produit->get_variation_price( 'max', true );
	if ( $min === $max ) {
		return $prix;
	}
	/* translators: %s : prix le plus bas. */
	return sprintf( __( '<span class="lf-prix__des">dès</span> %s', 'la-fabrique' ), wc_price( $min ) );
}
add_filter( 'woocommerce_variable_price_html', 'la_fabrique_wc_prix_variable', 10, 2 );

/**
 * Affiche une liste de produits avec les cartes de la boutique (accueil, recettes).
 *
 * @param WC_Product[] $produits Produits à afficher.
 * @param int          $colonnes Nombre de colonnes.
 * @param string       $nom      Nom de la boucle (pour les filtres de WooCommerce).
 */
function la_fabrique_boucle_produits( $produits, $colonnes = 3, $nom = 'la-fabrique' ) {
	if ( ! $produits ) {
		return;
	}
	wc_set_loop_prop( 'name', $nom );
	wc_set_loop_prop( 'columns', $colonnes );
	woocommerce_product_loop_start();
	foreach ( $produits as $produit ) {
		$objet = get_post( $produit->get_id() );
		setup_postdata( $GLOBALS['post'] = $objet ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited, Squiz.PHP.DisallowMultipleAssignments.Found -- même technique que les gabarits de WooCommerce.
		wc_get_template_part( 'content', 'product' );
	}
	woocommerce_product_loop_end();
	wp_reset_postdata();
	wc_reset_loop();
}

/*
 * 6. Fiche produit
 * --------------------------------------------------------------------------------------------
 */

remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );

/**
 * Catégorie au-dessus du titre (remplace le bloc « UGS / Catégories » sous le bouton).
 */
function la_fabrique_wc_fiche_categorie() {
	global $product;
	$categories = wc_get_product_terms( $product->get_id(), 'product_cat' );
	if ( ! $categories ) {
		return;
	}
	$lien = get_term_link( $categories[0] );
	if ( is_wp_error( $lien ) ) {
		return;
	}
	printf( '<p class="lf-fiche-produit__categorie"><a href="%1$s">%2$s</a></p>', esc_url( $lien ), esc_html( $categories[0]->name ) );
}
add_action( 'woocommerce_single_product_summary', 'la_fabrique_wc_fiche_categorie', 4 );

/**
 * Titre de la fiche : la mention entre parenthèses passe sous le nom.
 */
function la_fabrique_wc_fiche_titre() {
	global $product;
	$nom = la_fabrique_wc_nom( $product->get_name() );
	printf(
		'<h1 class="product_title entry-title lf-fiche-produit__titre">%1$s%2$s</h1>',
		esc_html( $nom['nom'] ),
		$nom['mention'] ? ' <span class="lf-fiche-produit__mention">' . esc_html( $nom['mention'] ) . '</span>' : ''
	);
}
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
add_action( 'woocommerce_single_product_summary', 'la_fabrique_wc_fiche_titre', 5 );

/**
 * Notes de dégustation en puces, juste sous le prix.
 */
function la_fabrique_wc_fiche_notes() {
	global $product;
	$fiche = la_fabrique_fiche_cafe( $product->get_id() );
	if ( ! $fiche['notes'] ) {
		return;
	}
	echo '<ul class="lf-puces lf-fiche-produit__notes" aria-label="' . esc_attr__( 'Notes de dégustation', 'la-fabrique' ) . '">';
	foreach ( $fiche['notes'] as $note ) {
		printf( '<li class="lf-puce lf-puce--note">%s</li>', esc_html( $note ) );
	}
	echo '</ul>';
}
add_action( 'woocommerce_single_product_summary', 'la_fabrique_wc_fiche_notes', 15 );

/**
 * Garanties sous le bouton d'ajout au panier.
 */
function la_fabrique_wc_fiche_garanties() {
	$lot = la_fabrique_lot();
	?>
	<ul class="lf-garanties">
		<li>
			<?php la_fabrique_icone( 'flamme' ); ?>
			<?php
			/* translators: %s : date du prochain mardi (jj.mm). */
			echo esc_html( sprintf( __( 'Torréfié le mardi, expédié sous 48 h. Prochain lot le %s.', 'la-fabrique' ), $lot['prochaine']->format( 'd.m' ) ) );
			?>
		</li>
		<li>
			<?php la_fabrique_icone( 'livraison' ); ?>
			<?php
			// Le tarif de Colissimo est un réglage de WooCommerce : le panier l'affiche, le thème ne le recopie pas.
			/* translators: %s : seuil de la livraison offerte, par exemple « 45 € ». */
			echo esc_html( sprintf( __( 'Livraison Colissimo offerte dès %s.', 'la-fabrique' ), la_fabrique_seuil_livraison() ) );
			?>
		</li>
		<li><?php la_fabrique_icone( 'atelier' ); ?><?php esc_html_e( 'Retrait gratuit à l’atelier, à Fives.', 'la-fabrique' ); ?></li>
	</ul>
	<?php
}
add_action( 'woocommerce_single_product_summary', 'la_fabrique_wc_fiche_garanties', 32 );

/**
 * Fiche de dégustation (intensité, torréfaction, origine, procédé, altitude).
 */
function la_fabrique_wc_fiche_degustation() {
	global $product;
	$fiche = la_fabrique_fiche_cafe( $product->get_id() );
	if ( ! $fiche['origine'] && ! $fiche['intensite'] && ! $fiche['torrefaction'] ) {
		return;
	}
	get_template_part( 'template-parts/produit/degustation', null, array( 'fiche' => $fiche ) );
}
add_action( 'woocommerce_single_product_summary', 'la_fabrique_wc_fiche_degustation', 40 );

/**
 * « Recettes avec ce café » : repli rendu côté serveur, enrichi en JavaScript (route REST).
 */
function la_fabrique_wc_fiche_recettes() {
	global $product;
	get_template_part( 'template-parts/produit/recettes-cafe', null, array( 'produit' => $product ) );
}
add_action( 'woocommerce_after_single_product_summary', 'la_fabrique_wc_fiche_recettes', 12 );

/**
 * Onglets : titres en français, et pas d'onglet « Informations complémentaires » redondant.
 *
 * @param array $onglets Onglets de la fiche.
 * @return array
 */
function la_fabrique_wc_onglets( $onglets ) {
	if ( isset( $onglets['description'] ) ) {
		$onglets['description']['title'] = __( 'L’histoire de ce café', 'la-fabrique' );
		global $product;
		if ( $product && ! has_term( 'cafes', 'product_cat', $product->get_id() ) ) {
			$onglets['description']['title'] = __( 'Description', 'la-fabrique' );
		}
	}
	if ( isset( $onglets['additional_information'] ) ) {
		$onglets['additional_information']['title'] = __( 'Formats', 'la-fabrique' );
	}
	if ( isset( $onglets['reviews'] ) ) {
		global $product;
		$nombre                      = $product ? (int) $product->get_review_count() : 0;
		$onglets['reviews']['title'] = sprintf(
			/* translators: %d : nombre d'avis. */
			__( 'Avis (%d)', 'la-fabrique' ),
			$nombre
		);
	}
	return $onglets;
}
add_filter( 'woocommerce_product_tabs', 'la_fabrique_wc_onglets', 20 );

// Les panneaux d'onglets n'ont pas besoin de répéter le titre de l'onglet.
add_filter( 'woocommerce_product_description_heading', '__return_empty_string' );
add_filter( 'woocommerce_product_additional_information_heading', '__return_empty_string' );

/**
 * Produits associés : quatre, sur quatre colonnes.
 *
 * @param array $args Arguments.
 * @return array
 */
function la_fabrique_wc_associes( $args ) {
	$args['posts_per_page'] = 4;
	$args['columns']        = 4;
	return $args;
}
add_filter( 'woocommerce_output_related_products_args', 'la_fabrique_wc_associes' );

/**
 * Titre des produits associés.
 *
 * @return string
 */
function la_fabrique_wc_titre_associes() {
	return __( 'Vous aimerez aussi', 'la-fabrique' );
}
add_filter( 'woocommerce_product_related_products_heading', 'la_fabrique_wc_titre_associes' );

/**
 * Titre des ventes incitatives.
 *
 * @return string
 */
function la_fabrique_wc_titre_incitatives() {
	return __( 'Pour aller plus loin', 'la-fabrique' );
}
add_filter( 'woocommerce_product_upsells_products_heading', 'la_fabrique_wc_titre_incitatives' );

/**
 * Titre des ventes croisées du panier.
 *
 * @return string
 */
function la_fabrique_wc_titre_croisees() {
	return __( 'Pour aller avec', 'la-fabrique' );
}
add_filter( 'woocommerce_product_cross_sells_products_heading', 'la_fabrique_wc_titre_croisees' );

/**
 * Boutons − et + autour du champ quantité (les boutons n'existent qu'avec JavaScript).
 */
function la_fabrique_wc_quantite_moins() {
	echo '<button type="button" class="lf-quantite__bouton" data-lf-quantite="-1" hidden>' . wp_kses( la_fabrique_get_icone( 'moins' ), la_fabrique_svg_autorise() ) . '<span class="screen-reader-text">' . esc_html__( 'Retirer un', 'la-fabrique' ) . '</span></button>';
}
add_action( 'woocommerce_before_quantity_input_field', 'la_fabrique_wc_quantite_moins' );

/**
 * Bouton + du champ quantité.
 */
function la_fabrique_wc_quantite_plus() {
	echo '<button type="button" class="lf-quantite__bouton" data-lf-quantite="1" hidden>' . wp_kses( la_fabrique_get_icone( 'plus' ), la_fabrique_svg_autorise() ) . '<span class="screen-reader-text">' . esc_html__( 'Ajouter un', 'la-fabrique' ) . '</span></button>';
}
add_action( 'woocommerce_after_quantity_input_field', 'la_fabrique_wc_quantite_plus' );

/*
 * 7. Panier, commande, compte
 * --------------------------------------------------------------------------------------------
 */

/**
 * Les pages du tunnel et du compte utilisent toute la largeur (page.php).
 *
 * @param bool $large Valeur courante.
 * @return bool
 */
function la_fabrique_wc_page_large( $large ) {
	return $large || is_cart() || is_checkout() || is_account_page();
}
add_filter( 'la_fabrique_page_large', 'la_fabrique_wc_page_large' );

/**
 * Étapes du tunnel de commande au-dessus du titre : panier, livraison et paiement, confirmation.
 */
function la_fabrique_wc_etapes_tunnel() {
	if ( is_order_received_page() ) {
		$courante = 3;
	} elseif ( is_checkout() ) {
		$courante = 2;
	} elseif ( is_cart() ) {
		$courante = 1;
	} else {
		return;
	}
	$etapes = array(
		1 => array( __( 'Panier', 'la-fabrique' ), wc_get_cart_url() ),
		2 => array( __( 'Livraison et paiement', 'la-fabrique' ), wc_get_checkout_url() ),
		3 => array( __( 'Confirmation', 'la-fabrique' ), '' ),
	);
	?>
	<nav class="lf-tunnel" aria-label="<?php esc_attr_e( 'Étapes de la commande', 'la-fabrique' ); ?>">
		<ol class="lf-tunnel__liste">
			<?php foreach ( $etapes as $numero => $etape ) : ?>
				<?php
				$classe = $numero < $courante ? 'is-faite' : ( $numero === $courante ? 'is-courante' : '' );
				?>
				<li class="lf-tunnel__etape <?php echo esc_attr( $classe ); ?>"<?php echo $numero === $courante ? ' aria-current="step"' : ''; ?>>
					<span class="lf-tunnel__numero" aria-hidden="true"><?php echo (int) $numero; ?></span>
					<?php if ( $numero < $courante && $etape[1] && 3 !== $courante ) : ?>
						<a href="<?php echo esc_url( $etape[1] ); ?>"><?php echo esc_html( $etape[0] ); ?></a>
					<?php else : ?>
						<span><?php echo esc_html( $etape[0] ); ?></span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</nav>
	<?php
}
add_action( 'la_fabrique_page_tete', 'la_fabrique_wc_etapes_tunnel' );

/**
 * Menu de « Mon compte » : libellés de la marque, sans « Téléchargements » (aucun produit numérique).
 *
 * @param array $elements Éléments du menu.
 * @return array
 */
function la_fabrique_wc_menu_compte( $elements ) {
	unset( $elements['downloads'] );
	$libelles = array(
		'dashboard'       => __( 'Tableau de bord', 'la-fabrique' ),
		'orders'          => __( 'Commandes', 'la-fabrique' ),
		'edit-address'    => __( 'Adresses', 'la-fabrique' ),
		'payment-methods' => __( 'Moyens de paiement', 'la-fabrique' ),
		'edit-account'    => __( 'Mes informations', 'la-fabrique' ),
		'customer-logout' => __( 'Se déconnecter', 'la-fabrique' ),
	);
	foreach ( $libelles as $cle => $libelle ) {
		if ( isset( $elements[ $cle ] ) ) {
			$elements[ $cle ] = $libelle;
		}
	}
	return $elements;
}
add_filter( 'woocommerce_account_menu_items', 'la_fabrique_wc_menu_compte', 20 );

/*
 * 8. Libellés en français
 * --------------------------------------------------------------------------------------------
 */

/**
 * Bouton des cartes produits.
 *
 * @param string     $texte   Libellé.
 * @param WC_Product $produit Produit.
 * @return string
 */
function la_fabrique_wc_texte_bouton( $texte, $produit ) {
	if ( ! $produit->is_purchasable() || ! $produit->is_in_stock() ) {
		return __( 'Voir le produit', 'la-fabrique' );
	}
	if ( $produit->is_type( 'variable' ) ) {
		return __( 'Choisir', 'la-fabrique' );
	}
	return $produit->is_type( 'simple' ) ? __( 'Ajouter', 'la-fabrique' ) : $texte;
}
add_filter( 'woocommerce_product_add_to_cart_text', 'la_fabrique_wc_texte_bouton', 10, 2 );

/**
 * Description accessible du bouton des cartes (aria-label).
 *
 * @param string     $texte   Description.
 * @param WC_Product $produit Produit.
 * @return string
 */
function la_fabrique_wc_description_bouton( $texte, $produit ) {
	$nom = $produit->get_name();
	if ( $produit->is_type( 'variable' ) && $produit->is_purchasable() ) {
		/* translators: %s : nom du produit. */
		return sprintf( __( 'Choisir le poids et la mouture de « %s »', 'la-fabrique' ), $nom );
	}
	if ( $produit->is_type( 'simple' ) && $produit->is_purchasable() && $produit->is_in_stock() ) {
		/* translators: %s : nom du produit. */
		return sprintf( __( 'Ajouter « %s » au panier', 'la-fabrique' ), $nom );
	}
	/* translators: %s : nom du produit. */
	return sprintf( __( 'Voir « %s »', 'la-fabrique' ), $nom );
}
add_filter( 'woocommerce_product_add_to_cart_description', 'la_fabrique_wc_description_bouton', 10, 2 );

/**
 * Bouton de la fiche produit.
 *
 * @return string
 */
function la_fabrique_wc_texte_bouton_fiche() {
	return __( 'Ajouter au panier', 'la-fabrique' );
}
add_filter( 'woocommerce_product_single_add_to_cart_text', 'la_fabrique_wc_texte_bouton_fiche' );

/**
 * Bouton de validation de la commande.
 *
 * @return string
 */
function la_fabrique_wc_texte_commander() {
	return __( 'Confirmer la commande', 'la-fabrique' );
}
add_filter( 'woocommerce_order_button_text', 'la_fabrique_wc_texte_commander' );

/**
 * Pastille « promo ».
 *
 * @return string
 */
function la_fabrique_wc_pastille_promo() {
	return '<span class="onsale lf-pastille">' . esc_html__( 'Promo', 'la-fabrique' ) . '</span>';
}
add_filter( 'woocommerce_sale_flash', 'la_fabrique_wc_pastille_promo' );

/**
 * Options de tri du catalogue.
 *
 * @param array $options Options proposées.
 * @return array
 */
function la_fabrique_wc_tri( $options ) {
	$libelles = array(
		'menu_order' => __( 'Notre sélection', 'la-fabrique' ),
		'popularity' => __( 'Les plus vendus', 'la-fabrique' ),
		'rating'     => __( 'Les mieux notés', 'la-fabrique' ),
		'date'       => __( 'Les nouveautés', 'la-fabrique' ),
		'price'      => __( 'Prix croissant', 'la-fabrique' ),
		'price-desc' => __( 'Prix décroissant', 'la-fabrique' ),
	);
	foreach ( $libelles as $cle => $libelle ) {
		if ( isset( $options[ $cle ] ) ) {
			$options[ $cle ] = $libelle;
		}
	}
	return $options;
}
add_filter( 'woocommerce_catalog_orderby', 'la_fabrique_wc_tri' );
/**
 * Texte du bouton d'ajout au panier.
 */
function la_fabrique_texte_bouton() {
	return 'Je le veux';
}
add_filter( 'woocommerce_product_single_add_to_cart_text', 'la_fabrique_texte_bouton' );
add_filter( 'woocommerce_product_add_to_cart_text', 'la_fabrique_texte_bouton' );
