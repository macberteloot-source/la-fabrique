<?php
/**
 * Fonctions d'affichage appelées par les gabarits.
 *
 * Convention : les fonctions la_fabrique_get_*() renvoient du HTML, les autres l'affichent.
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;

/**
 * Balises et attributs SVG autorisés pour wp_kses (icônes et logo du thème).
 *
 * @return array
 */
function la_fabrique_svg_autorise() {
	$commun = array(
		'class'           => true,
		'fill'            => true,
		'stroke'          => true,
		'stroke-width'    => true,
		'stroke-linecap'  => true,
		'stroke-linejoin' => true,
		'opacity'         => true,
		'transform'       => true,
		'clip-path'       => true,
	);
	return array(
		'svg'      => $commun + array(
			'xmlns'               => true,
			'viewbox'             => true,
			'width'               => true,
			'height'              => true,
			'role'                => true,
			'aria-hidden'         => true,
			'aria-label'          => true,
			'focusable'           => true,
			'preserveaspectratio' => true,
		),
		'g'        => $commun,
		'path'     => $commun + array( 'd' => true ),
		'circle'   => $commun + array(
			'cx' => true,
			'cy' => true,
			'r'  => true,
		),
		'ellipse'  => $commun + array(
			'cx' => true,
			'cy' => true,
			'rx' => true,
			'ry' => true,
		),
		'rect'     => $commun + array(
			'x'      => true,
			'y'      => true,
			'width'  => true,
			'height' => true,
			'rx'     => true,
		),
		'line'     => $commun + array(
			'x1' => true,
			'y1' => true,
			'x2' => true,
			'y2' => true,
		),
		'polyline' => $commun + array( 'points' => true ),
		'defs'     => array(),
		'clippath' => array( 'id' => true ),
		'title'    => array( 'id' => true ),
	);
}

/**
 * Règles wp_kses du contenu (post) enrichies des balises SVG : pour le HTML des hooks.
 *
 * @return array
 */
function la_fabrique_kses_avec_svg() {
	static $regles = null;
	if ( null === $regles ) {
		$regles = array_merge( wp_kses_allowed_html( 'post' ), la_fabrique_svg_autorise() );
	}
	return $regles;
}

/**
 * Icônes de l'interface (trait de 1,6 sur une grille de 24).
 *
 * @param string $nom    Nom de l'icône.
 * @param string $classe Classe CSS supplémentaire.
 * @return string SVG décoratif (aria-hidden), ou chaîne vide si l'icône n'existe pas.
 */
function la_fabrique_get_icone( $nom, $classe = '' ) {
	$icones = array(
		'recherche'   => '<circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/>',
		'compte'      => '<circle cx="12" cy="8.5" r="3.8"/><path d="M4.5 20c1.3-3.6 4.2-5.4 7.5-5.4s6.2 1.8 7.5 5.4"/>',
		'panier'      => '<path d="M5.5 8.5h13l-1 11.5h-11l-1-11.5Z"/><path d="M9 8.5V7a3 3 0 0 1 6 0v1.5"/>',
		'menu'        => '<path d="M4 9h16M4 15h16"/>',
		'fermer'      => '<path d="m6 6 12 12M18 6 6 18"/>',
		'fleche'      => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'fleche-g'    => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
		'haut'        => '<path d="M12 19V5M6 11l6-6 6 6"/>',
		'temps'       => '<circle cx="12" cy="12" r="8"/><path d="M12 7.5V12l3 2"/>',
		'temperature' => '<path d="M10 14.5V5a2 2 0 1 1 4 0v9.5a4 4 0 1 1-4 0Z"/><path d="M12 9v6.5"/>',
		'ratio'       => '<path d="M12 4v15M5 19h14M6 8h12"/><path d="m6 8-3 6h6l-3-6ZM18 8l-3 6h6l-3-6Z"/>',
		'mouture'     => '<ellipse cx="12" cy="12" rx="7" ry="5" transform="rotate(-30 12 12)"/><path d="M7.5 14.5c2-1 2.8-3.4 4.5-4.4 1.6-1 3-.6 4.4-1.7"/>',
		'difficulte'  => '<path d="M6 19v-4M12 19v-8M18 19V7"/>',
		'origine'     => '<path d="M12 21s-6-5.6-6-10.5a6 6 0 1 1 12 0C18 15.4 12 21 12 21Z"/><circle cx="12" cy="10.5" r="2.2"/>',
		'altitude'    => '<path d="m3 19 6.5-10 4 5.5 2.5-3 5 7.5H3Z"/>',
		'procede'     => '<path d="M12 3.5s6 6.4 6 10.5a6 6 0 0 1-12 0c0-4.1 6-10.5 6-10.5Z"/>',
		'flamme'      => '<path d="M12 21c3.5 0 6-2.4 6-5.6 0-4.4-4.2-6.2-4.2-10.4C10 7 6 10.6 6 15.4 6 18.6 8.5 21 12 21Z"/>',
		'tasse'       => '<path d="M5 10h11v4a5 5 0 0 1-5 5h-1a5 5 0 0 1-5-5v-4Z"/><path d="M16 11h1.5a2.5 2.5 0 0 1 0 5H16M9 3.5c-.8 1 .8 2-.1 3M12.5 3.5c-.8 1 .8 2-.1 3"/>',
		'livraison'   => '<path d="M3 6.5h11v9.5H3zM14 10h4l3 3.2V16h-7"/><circle cx="7" cy="17.5" r="1.7"/><circle cx="17" cy="17.5" r="1.7"/>',
		'atelier'     => '<path d="M3 20v-9l5 3v-3l5 3v-3l5 3V7h2.5v13H3Z"/><path d="M7 20v-3h3v3"/>',
		'plus'        => '<path d="M12 5v14M5 12h14"/>',
		'moins'       => '<path d="M5 12h14"/>',
		'coche'       => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
		'grain'       => '<ellipse cx="12" cy="12" rx="8" ry="5.6"/><path d="M5.5 13 9 9.8v3.2l3.5-3.2v3.2l3.5-3.2v3.2h2.5"/>',
	);
	if ( ! isset( $icones[ $nom ] ) ) {
		return '';
	}
	return sprintf(
		'<svg class="lf-icone lf-icone--%1$s %2$s" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%3$s</svg>',
		esc_attr( $nom ),
		esc_attr( $classe ),
		$icones[ $nom ]
	);
}

/**
 * Affiche une icône.
 *
 * @param string $nom    Nom de l'icône.
 * @param string $classe Classe CSS supplémentaire.
 */
function la_fabrique_icone( $nom, $classe = '' ) {
	echo wp_kses( la_fabrique_get_icone( $nom, $classe ), la_fabrique_svg_autorise() );
}

/**
 * Contenu d'un fichier SVG du thème (assets/img), nettoyé, prêt à être inséré dans la page.
 *
 * Le titre est retiré : l'élément qui l'entoure porte l'intitulé accessible.
 *
 * @param string $fichier Nom du fichier dans assets/img (sans chemin).
 * @param string $classe  Classe ajoutée sur la balise svg.
 * @return string
 */
function la_fabrique_get_svg( $fichier, $classe = '' ) {
	static $cache = array();
	$fichier      = sanitize_file_name( $fichier );
	if ( ! isset( $cache[ $fichier ] ) ) {
		$chemin            = LA_FABRIQUE_DIR . '/assets/img/' . $fichier;
		$svg               = file_exists( $chemin ) ? (string) file_get_contents( $chemin ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- fichier local du thème.
		$svg               = preg_replace( '#<title[^>]*>.*?</title>#s', '', $svg );
		$svg               = preg_replace( '#\s(role|aria-labelledby)="[^"]*"#', '', $svg );
		$cache[ $fichier ] = wp_kses( $svg, la_fabrique_svg_autorise() );
	}
	$svg = $cache[ $fichier ];
	if ( '' === $svg ) {
		return '';
	}
	$attributs = ' aria-hidden="true" focusable="false"' . ( $classe ? ' class="' . esc_attr( $classe ) . '"' : '' );
	return preg_replace( '#^<svg#', '<svg' . $attributs, $svg, 1 );
}

/**
 * Logo : celui de l'outil de personnalisation s'il existe, sinon le logo vectoriel du thème.
 *
 * @param string $classe Classe du lien.
 */
function la_fabrique_logo( $classe = 'lf-logo' ) {
	if ( has_custom_logo() ) {
		the_custom_logo();
		return;
	}
	printf(
		'<a class="%1$s" href="%2$s" rel="home" aria-label="%3$s">%4$s</a>',
		esc_attr( $classe ),
		esc_url( home_url( '/' ) ),
		/* translators: %s : nom du site. */
		esc_attr( sprintf( __( '%s, accueil', 'la-fabrique' ), get_bloginfo( 'name' ) ) ),
		wp_kses( la_fabrique_get_svg( 'logo.svg', 'lf-logo__svg' ), la_fabrique_svg_autorise() )
	);
}

/**
 * Menu de secours quand aucun menu n'est attribué à un emplacement : liens essentiels.
 *
 * @param array $args Arguments de wp_nav_menu().
 * @return string|void
 */
function la_fabrique_menu_secours( $args ) {
	$liens = array();
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$liens[ wc_get_page_permalink( 'shop' ) ] = __( 'Boutique', 'la-fabrique' );
	}
	if ( post_type_exists( 'recette' ) ) {
		$liens[ (string) get_post_type_archive_link( 'recette' ) ] = __( 'Recettes', 'la-fabrique' );
	}
	$atelier = get_page_by_path( 'atelier' );
	if ( $atelier ) {
		$liens[ get_permalink( $atelier ) ] = get_the_title( $atelier );
	}

	$html = '';
	foreach ( $liens as $url => $libelle ) {
		$html .= sprintf( '<li class="menu-item"><a href="%1$s">%2$s</a></li>', esc_url( $url ), esc_html( $libelle ) );
	}
	$html = sprintf( '<ul class="%1$s">%2$s</ul>', esc_attr( $args['menu_class'] ?? 'menu' ), $html );

	if ( ! empty( $args['echo'] ) ) {
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- chaque morceau est échappé ci-dessus.
		return;
	}
	return $html;
}

/**
 * Fil d'Ariane des pages hors boutique (la boutique utilise celui de WooCommerce, même balisage).
 */
function la_fabrique_fil_ariane() {
	$etapes = array( home_url( '/' ) => __( 'Accueil', 'la-fabrique' ) );

	if ( is_singular( 'recette' ) || is_post_type_archive( 'recette' ) || is_tax( 'methode' ) ) {
		$etapes[ (string) get_post_type_archive_link( 'recette' ) ] = __( 'Recettes', 'la-fabrique' );
		if ( is_singular( 'recette' ) ) {
			$methodes = get_the_terms( get_the_ID(), 'methode' );
			if ( is_array( $methodes ) && $methodes ) {
				$lien = get_term_link( $methodes[0] );
				if ( ! is_wp_error( $lien ) ) {
					$etapes[ $lien ] = $methodes[0]->name;
				}
			}
		}
	} elseif ( is_singular( 'post' ) || is_category() || is_tag() ) {
		$page_articles = (int) get_option( 'page_for_posts' );
		if ( $page_articles ) {
			$etapes[ get_permalink( $page_articles ) ] = get_the_title( $page_articles );
		}
	}

	if ( is_singular() ) {
		$courant = get_the_title();
	} elseif ( is_search() ) {
		$courant = __( 'Recherche', 'la-fabrique' );
	} elseif ( is_404() ) {
		$courant = __( 'Page introuvable', 'la-fabrique' );
	} elseif ( is_post_type_archive( 'recette' ) ) {
		// L'archive est elle-même l'étape « Recettes » : pas de lien vers la page courante.
		array_pop( $etapes );
		$courant = __( 'Recettes', 'la-fabrique' );
	} else {
		$courant = wp_strip_all_tags( get_the_archive_title() );
	}
	?>
	<nav class="lf-ariane" aria-label="<?php esc_attr_e( 'Fil d’Ariane', 'la-fabrique' ); ?>">
		<ol class="lf-ariane__liste">
			<?php foreach ( $etapes as $url => $libelle ) : ?>
				<li><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $libelle ); ?></a></li>
			<?php endforeach; ?>
			<li><span aria-current="page"><?php echo esc_html( $courant ); ?></span></li>
		</ol>
	</nav>
	<?php
}

/**
 * Pagination des listes (articles, recettes, recherche).
 */
function la_fabrique_pagination() {
	the_posts_pagination(
		array(
			'mid_size'           => 1,
			'prev_text'          => la_fabrique_get_icone( 'fleche-g' ) . '<span class="screen-reader-text">' . esc_html__( 'Page précédente', 'la-fabrique' ) . '</span>',
			'next_text'          => '<span class="screen-reader-text">' . esc_html__( 'Page suivante', 'la-fabrique' ) . '</span>' . la_fabrique_get_icone( 'fleche' ),
			'before_page_number' => '<span class="screen-reader-text">' . esc_html__( 'Page', 'la-fabrique' ) . ' </span>',
			'class'              => 'lf-pagination',
			'aria_label'         => __( 'Pagination', 'la-fabrique' ),
		)
	);
}

/**
 * Date et temps de lecture d'un article.
 */
function la_fabrique_meta_article() {
	$mots    = str_word_count( wp_strip_all_tags( (string) get_the_content() ) );
	$minutes = max( 1, (int) round( $mots / 220 ) );
	printf(
		'<p class="lf-meta"><time datetime="%1$s">%2$s</time><span>%3$s</span></p>',
		esc_attr( get_the_date( DATE_W3C ) ),
		esc_html( get_the_date() ),
		/* translators: %d : nombre de minutes. */
		esc_html( sprintf( _n( '%d minute de lecture', '%d minutes de lecture', $minutes, 'la-fabrique' ), $minutes ) )
	);
}

/**
 * Mention obligatoire : marque fictive (pied de page, accueil, page « L'atelier »).
 *
 * @param string $classe Classe CSS du paragraphe.
 */
function la_fabrique_marque_fictive( $classe = 'lf-fictive' ) {
	printf(
		'<p class="%1$s">%2$s</p>',
		esc_attr( $classe ),
		esc_html__( 'Projet de démonstration, marque fictive : La Fabrique n’existe pas, aucune commande n’est expédiée.', 'la-fabrique' )
	);
}

/**
 * Titre de section avec lien « tout voir » (accueil, fiches).
 *
 * @param string $titre   Titre de la section.
 * @param string $id      Identifiant du titre (pour aria-labelledby).
 * @param string $lien    URL du lien « tout voir » (facultatif).
 * @param string $libelle Libellé du lien.
 */
function la_fabrique_titre_section( $titre, $id, $lien = '', $libelle = '' ) {
	?>
	<div class="lf-section__tete">
		<h2 class="lf-section__titre" id="<?php echo esc_attr( $id ); ?>" data-lf-revele="lignes"><?php echo esc_html( $titre ); ?></h2>
		<?php if ( $lien ) : ?>
			<a class="lf-lien" href="<?php echo esc_url( $lien ); ?>"><?php echo esc_html( $libelle ); ?></a>
		<?php endif; ?>
	</div>
	<?php
}
