<?php
/**
 * Petites données de la marque utilisées par les gabarits.
 *
 * - le lot de la semaine : La Fabrique torréfie le mardi, un lot par semaine,
 *   numéroté comme la semaine (lot n° 042 = semaine 42) ;
 * - les horaires de retrait à l'atelier (texte d'information, filtrable) ;
 * - la courbe de torréfaction de l'accueil.
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;

/**
 * Dernier lot torréfié : numéro (semaine ISO du mardi) et date du mardi.
 *
 * @return array{numero: string, date: DateTimeImmutable, prochaine: DateTimeImmutable}
 */
function la_fabrique_lot() {
	$maintenant = current_datetime();
	$mardi      = $maintenant->setTime( 0, 0 )->modify( 'tuesday this week' );
	if ( $mardi > $maintenant ) {
		$mardi = $mardi->modify( '-7 days' );
	}
	return array(
		'numero'    => sprintf( '%03d', (int) $mardi->format( 'W' ) ),
		'date'      => $mardi,
		'prochaine' => $mardi->modify( '+7 days' ),
	);
}

/**
 * Horaires de retrait à l'atelier (accueil, pied de page, page « L'atelier »).
 *
 * Source : fabrique_horaires_retrait() du plugin fabrique-boutique (contrat après arbitrage),
 * c'est-à-dire les plages réglées dans WooCommerce → La Fabrique. Sans le plugin, le thème
 * affiche les horaires par défaut du cahier des charges. Le filtre permet d'ajuster le résultat.
 *
 * @return string[] Lignes d'horaires (texte brut, à échapper à l'affichage).
 */
function la_fabrique_horaires_retrait() {
	if ( function_exists( 'fabrique_horaires_retrait' ) ) {
		$horaires = (array) fabrique_horaires_retrait();
	} else {
		$horaires = array(
			__( 'Mercredi, 17 h – 19 h', 'la-fabrique' ),
			__( 'Samedi, 10 h – 12 h', 'la-fabrique' ),
		);
	}

	/**
	 * Filtre les horaires de retrait affichés par le thème.
	 *
	 * @param string[] $horaires Lignes d'horaires.
	 */
	return (array) apply_filters( 'la_fabrique_horaires_retrait', $horaires );
}

/**
 * Seuil de la livraison offerte, au format de la barre de l'en-tête : « 45 € ».
 *
 * Source : fabrique_seuil_livraison_offerte() du plugin fabrique-boutique (même calcul que la
 * barre : méthode « Livraison offerte » de la zone du visiteur, sinon réglage du plugin), pour
 * que les textes du thème ne contredisent jamais la barre. Repli sans le plugin : « 45 € ».
 *
 * @return string Montant (texte brut, à échapper à l'affichage).
 */
function la_fabrique_seuil_livraison() {
	if ( function_exists( 'fabrique_seuil_livraison_offerte' ) ) {
		return (string) fabrique_seuil_livraison_offerte();
	}
	return "45\u{00A0}€";
}

/**
 * Courbe de torréfaction d'un lot (température du grain en fonction du temps).
 *
 * Valeurs réalistes pour un café lavé en torréfaction claire : chute à l'enfournement,
 * point bas, séchage, réactions de Maillard, premier crac, développement, sortie.
 *
 * @return array{points: array, etapes: array, phases: array}
 */
function la_fabrique_courbe_torrefaction() {
	return array(
		// Minutes depuis l'enfournement => °C.
		'points' => array(
			array( 0, 200 ),
			array( 0.4, 150 ),
			array( 0.9, 108 ),
			array( 1.5, 92 ),
			array( 2.2, 99 ),
			array( 3, 113 ),
			array( 4, 131 ),
			array( 5, 150 ),
			array( 6, 163 ),
			array( 7, 175 ),
			array( 8, 186 ),
			array( 9.1, 196 ),
			array( 10, 203 ),
			array( 11, 209 ),
		),
		'etapes' => array(
			array(
				'minute'      => 1.5,
				'temperature' => 92,
				'libelle'     => __( 'Point bas', 'la-fabrique' ),
			),
			array(
				'minute'      => 5,
				'temperature' => 150,
				'libelle'     => __( 'Fin du séchage', 'la-fabrique' ),
			),
			array(
				'minute'      => 9.1,
				'temperature' => 196,
				'libelle'     => __( 'Premier crac', 'la-fabrique' ),
			),
			array(
				'minute'      => 11,
				'temperature' => 209,
				'libelle'     => __( 'Sortie', 'la-fabrique' ),
			),
		),
		'phases' => array(
			array( 1.5, 5, __( 'Séchage', 'la-fabrique' ) ),
			array( 5, 9.1, __( 'Maillard', 'la-fabrique' ) ),
			array( 9.1, 11, __( 'Développement', 'la-fabrique' ) ),
		),
	);
}

/**
 * Première lettre en capitale (multi-octets) : « lavé » → « Lavé ».
 *
 * @param string $texte Texte.
 * @return string
 */
function la_fabrique_majuscule( $texte ) {
	$texte = (string) $texte;
	return mb_strtoupper( mb_substr( $texte, 0, 1 ) ) . mb_substr( $texte, 1 );
}

/**
 * Durée lisible : 9.1 → « 9 min 06 ».
 *
 * @param float $minutes Durée en minutes.
 * @return string
 */
function la_fabrique_duree( $minutes ) {
	$total    = (int) round( $minutes * 60 );
	$secondes = $total % 60;
	/* translators: 1 : minutes, 2 : secondes sur deux chiffres. */
	return sprintf( __( '%1$d min %2$s', 'la-fabrique' ), intdiv( $total, 60 ), sprintf( '%02d', $secondes ) );
}

/**
 * Tracé SVG lisse (spline de Catmull-Rom convertie en courbes de Bézier) passant par des points.
 *
 * @param array $points Liste de couples [x, y] en coordonnées SVG.
 * @return string Attribut « d ».
 */
function la_fabrique_trace_lisse( $points ) {
	$nombre = count( $points );
	if ( $nombre < 2 ) {
		return '';
	}
	$format = static function ( $valeur ) {
		return rtrim( rtrim( number_format( $valeur, 1, '.', '' ), '0' ), '.' );
	};
	$d      = 'M' . $format( $points[0][0] ) . ',' . $format( $points[0][1] );
	for ( $i = 0; $i < $nombre - 1; $i++ ) {
		$p0  = $points[ max( 0, $i - 1 ) ];
		$p1  = $points[ $i ];
		$p2  = $points[ $i + 1 ];
		$p3  = $points[ min( $nombre - 1, $i + 2 ) ];
		$c1x = $p1[0] + ( $p2[0] - $p0[0] ) / 6;
		$c1y = $p1[1] + ( $p2[1] - $p0[1] ) / 6;
		$c2x = $p2[0] - ( $p3[0] - $p1[0] ) / 6;
		$c2y = $p2[1] - ( $p3[1] - $p1[1] ) / 6;
		$d  .= sprintf(
			'C%s,%s %s,%s %s,%s',
			$format( $c1x ),
			$format( $c1y ),
			$format( $c2x ),
			$format( $c2y ),
			$format( $p2[0] ),
			$format( $p2[1] )
		);
	}
	return $d;
}
