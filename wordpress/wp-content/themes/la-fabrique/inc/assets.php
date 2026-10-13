<?php
/**
 * Feuilles de style, scripts, polices et favicon.
 *
 * Performance : CSS sans framework, scripts en `defer` (stratégie de chargement de WordPress),
 * polices locales préchargées, aucune dépendance JavaScript pour le thème lui-même.
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;

/**
 * Version d'un fichier du thème pour le cache navigateur (date de modification).
 *
 * @param string $fichier Chemin relatif au thème.
 * @return string
 */
function la_fabrique_version( $fichier ) {
	$chemin = LA_FABRIQUE_DIR . '/' . ltrim( $fichier, '/' );
	return file_exists( $chemin ) ? (string) filemtime( $chemin ) : LA_FABRIQUE_VERSION;
}

/**
 * URL d'un fichier du thème.
 *
 * @param string $fichier Chemin relatif au thème.
 * @return string
 */
function la_fabrique_uri( $fichier ) {
	return get_theme_file_uri( ltrim( $fichier, '/' ) );
}

/**
 * Enregistre et charge les styles et scripts du thème.
 */
function la_fabrique_assets() {
	wp_enqueue_style( 'la-fabrique', la_fabrique_uri( 'assets/css/main.css' ), array(), la_fabrique_version( 'assets/css/main.css' ) );

	wp_enqueue_script(
		'la-fabrique',
		la_fabrique_uri( 'assets/js/theme.js' ),
		array(),
		la_fabrique_version( 'assets/js/theme.js' ),
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);

	// Recettes : filtres de l'archive et section « Recettes avec ce café » (route REST du plugin).
	wp_register_script(
		'la-fabrique-recettes',
		la_fabrique_uri( 'assets/js/recettes.js' ),
		array(),
		la_fabrique_version( 'assets/js/recettes.js' ),
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'la_fabrique_assets' );

/**
 * Remplace la classe « no-js » par « js » le plus tôt possible (évite un saut d'affichage).
 */
function la_fabrique_classe_js() {
	wp_print_inline_script_tag( "document.documentElement.classList.replace('no-js','js');" );
}
add_action( 'wp_head', 'la_fabrique_classe_js', 0 );

/**
 * Précharge les deux polices visibles dès le premier écran.
 *
 * Les @font-face sont générés par WordPress à partir de theme.json : les URL sont identiques,
 * le navigateur réutilise donc le fichier préchargé.
 */
function la_fabrique_precharger_polices() {
	$polices = array(
		'assets/fonts/instrument-serif-latin-400-normal.woff2',
		'assets/fonts/instrument-sans-latin-400-normal.woff2',
	);
	foreach ( $polices as $police ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( la_fabrique_uri( $police ) )
		);
	}
}
add_action( 'wp_head', 'la_fabrique_precharger_polices', 1 );

/**
 * Favicon du thème, seulement si aucune icône de site n'est réglée dans l'outil de personnalisation.
 */
function la_fabrique_favicon() {
	if ( has_site_icon() ) {
		return;
	}
	printf( '<link rel="icon" href="%s" type="image/svg+xml">' . "\n", esc_url( la_fabrique_uri( 'assets/img/favicon.svg' ) ) );
	printf( '<link rel="icon" href="%s" sizes="32x32" type="image/png">' . "\n", esc_url( la_fabrique_uri( 'assets/img/favicon-32.png' ) ) );
	printf( '<link rel="apple-touch-icon" href="%s">' . "\n", esc_url( la_fabrique_uri( 'assets/img/apple-touch-icon.png' ) ) );
}
add_action( 'wp_head', 'la_fabrique_favicon', 5 );

/**
 * Couleur de l'interface du navigateur mobile.
 */
function la_fabrique_theme_color() {
	echo '<meta name="theme-color" content="#F4EEE3">' . "\n";
}
add_action( 'wp_head', 'la_fabrique_theme_color', 5 );
