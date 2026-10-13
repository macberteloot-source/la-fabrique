<?php
/**
 * Accueil : la fabrique en quatre étapes (vert → torréfaction → repos → tasse).
 *
 * Une vraie séquence, d'où la numérotation. La ligne relie la température du grain à chaque étape :
 * c'est l'information, pas une décoration (20 °C, 210 °C, 20 °C, 93 °C).
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;

$la_fabrique_etapes = array(
	array(
		'titre' => __( 'Le grain vert', 'la-fabrique' ),
		'temp'  => 20,
		'texte' => __( 'Nous choisissons des lots de petits producteurs, goûtés à l’échantillon. Le café arrive cru, vert, et sent le foin.', 'la-fabrique' ),
	),
	array(
		'titre' => __( 'La torréfaction', 'la-fabrique' ),
		'temp'  => 210,
		'texte' => __( 'Le mardi, chaque lot passe dix à douze minutes dans le tambour. On suit la courbe au degré près, l’oreille tendue vers le premier crac.', 'la-fabrique' ),
	),
	array(
		'titre' => __( 'Le repos', 'la-fabrique' ),
		'temp'  => 20,
		'texte' => __( 'Les grains dégazent cinq à sept jours dans leur sac à valve. C’est pendant ce temps que les arômes se posent.', 'la-fabrique' ),
	),
	array(
		'titre' => __( 'La tasse', 'la-fabrique' ),
		'temp'  => 93,
		'texte' => __( 'Chez vous, avec la bonne mouture et la bonne recette. Ou à l’atelier le samedi matin, avec un spéculoos.', 'la-fabrique' ),
	),
);

// Ligne des températures : viewBox 1000 × 200, étiré en largeur (le trait garde son épaisseur).
$la_fabrique_y      = static function ( $temp ) {
	return round( 186 - $temp / 230 * 168, 1 );
};
$la_fabrique_points = array( array( 0, $la_fabrique_y( 20 ) ) );
foreach ( $la_fabrique_etapes as $la_fabrique_i => $la_fabrique_etape ) {
	$la_fabrique_points[] = array( 125 + 250 * $la_fabrique_i, $la_fabrique_y( $la_fabrique_etape['temp'] ) );
}
$la_fabrique_points[] = array( 1000, $la_fabrique_y( 60 ) );
?>
<section class="lf-section lf-etapes lf-sombre" aria-labelledby="lf-etapes-titre">
	<div class="lf-conteneur">
		<div class="lf-section__tete">
			<h2 class="lf-section__titre" id="lf-etapes-titre" data-lf-revele="lignes"><?php esc_html_e( 'Du grain vert à votre tasse', 'la-fabrique' ); ?></h2>
			<p class="lf-section__intro"><?php esc_html_e( 'Le même grain monte à 210 °C, redescend, se repose, puis rencontre votre eau à 93 °C. Quatre étapes, quatre températures.', 'la-fabrique' ); ?></p>
		</div>

		<div class="lf-etapes__courbe" aria-hidden="true" data-lf-courbe>
			<svg viewBox="0 0 1000 200" preserveAspectRatio="none" focusable="false">
				<defs>
					<clipPath id="lf-etapes-revele"><rect class="lf-courbe__rideau" x="0" y="0" width="1000" height="200"/></clipPath>
				</defs>
				<path class="lf-etapes__base" d="M0,186H1000"/>
				<g clip-path="url(#lf-etapes-revele)">
					<path class="lf-etapes__ligne" d="<?php echo esc_attr( la_fabrique_trace_lisse( $la_fabrique_points ) ); ?>"/>
				</g>
			</svg>
			<?php foreach ( $la_fabrique_etapes as $la_fabrique_i => $la_fabrique_etape ) : ?>
				<span class="lf-etapes__point" style="--lf-x: <?php echo esc_attr( 12.5 + 25 * $la_fabrique_i ); ?>%; --lf-y: <?php echo esc_attr( $la_fabrique_y( $la_fabrique_etape['temp'] ) ); ?>px; --lf-delai: <?php echo esc_attr( 0.15 + 0.25 * $la_fabrique_i ); ?>">
					<span class="lf-etapes__temp"><?php echo esc_html( $la_fabrique_etape['temp'] . ' °C' ); ?></span>
				</span>
			<?php endforeach; ?>
		</div>

		<ol class="lf-etapes__liste">
			<?php foreach ( $la_fabrique_etapes as $la_fabrique_i => $la_fabrique_etape ) : ?>
				<li class="lf-etape" data-lf-revele style="--lf-ordre: <?php echo (int) $la_fabrique_i; ?>">
					<p class="lf-etape__temp" aria-hidden="true"><?php echo esc_html( $la_fabrique_etape['temp'] . ' °C' ); ?></p>
					<h3 class="lf-etape__titre">
						<span class="lf-etape__numero"><?php echo (int) ( $la_fabrique_i + 1 ); ?></span>
						<?php echo esc_html( $la_fabrique_etape['titre'] ); ?>
					</h3>
					<p class="lf-etape__texte">
						<span class="screen-reader-text">
							<?php
							/* translators: %d : température en degrés Celsius. */
							echo esc_html( sprintf( __( '%d °C.', 'la-fabrique' ), $la_fabrique_etape['temp'] ) );
							?>
						</span>
						<?php echo esc_html( $la_fabrique_etape['texte'] ); ?>
					</p>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>
