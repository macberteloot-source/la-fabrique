<?php
/**
 * Accueil : courbe de torréfaction du lot de la semaine (SVG calculé en PHP).
 *
 * Abscisses : minutes depuis l'enfournement (0 à 12) ; ordonnées : température du grain (60 à 220 °C).
 * Deux formats sont produits à partir des mêmes données : un format large (bureau), qui court
 * sur toute la largeur comme une ligne d'horizon, et un format compact (mobile) aux textes plus
 * grands ; le CSS n'en affiche qu'un. La ligne est révélée par un rectangle de découpe animé en
 * transform (voir main.css, section 12).
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;

$la_fabrique_lot    = $args['lot'] ?? la_fabrique_lot();
$la_fabrique_courbe = la_fabrique_courbe_torrefaction();
$la_fabrique_sortie = end( $la_fabrique_courbe['etapes'] );

/*
 * Formats : dimensions du viewBox, zone de tracé (gauche, droite, haut, bas), position des étiquettes
 * de chaque repère (décalage x, décalage y, alignement) et affichage des valeurs détaillées.
 */
$la_fabrique_formats = array(
	'large'   => array(
		'largeur'    => 1280,
		'hauteur'    => 300,
		'zone'       => array( 56, 1190, 34, 262 ),
		'etiquettes' => array( array( 12, 22, 'start' ), array( -14, -30, 'end' ), array( -14, -30, 'end' ), array( 14, 4, 'start' ) ),
		'valeurs'    => true,
		'pas'        => 1,
	),
	'compact' => array(
		'largeur'    => 640,
		'hauteur'    => 420,
		'zone'       => array( 54, 610, 50, 360 ),
		'etiquettes' => array( array( 14, 36, 'start' ), array( -16, -18, 'end' ), array( -16, -18, 'end' ), array( -16, -18, 'end' ) ),
		'valeurs'    => false,
		'pas'        => 2,
	),
);
?>
<figure class="lf-courbe lf-hero__courbe" data-lf-courbe>
	<?php
	foreach ( $la_fabrique_formats as $la_fabrique_nom => $la_fabrique_f ) :
		list( $la_fabrique_g, $la_fabrique_d, $la_fabrique_h, $la_fabrique_b ) = $la_fabrique_f['zone'];

		$la_fabrique_x = static function ( $minute ) use ( $la_fabrique_g, $la_fabrique_d ) {
			return round( $la_fabrique_g + $minute / 12 * ( $la_fabrique_d - $la_fabrique_g ), 1 );
		};
		$la_fabrique_y = static function ( $temperature ) use ( $la_fabrique_h, $la_fabrique_b ) {
			return round( $la_fabrique_b - ( $temperature - 60 ) / 160 * ( $la_fabrique_b - $la_fabrique_h ), 1 );
		};

		$la_fabrique_points = array();
		foreach ( $la_fabrique_courbe['points'] as $la_fabrique_point ) {
			$la_fabrique_points[] = array( $la_fabrique_x( $la_fabrique_point[0] ), $la_fabrique_y( $la_fabrique_point[1] ) );
		}
		$la_fabrique_id = wp_unique_id( 'lf-courbe-' );
		?>
		<svg class="lf-courbe__svg lf-courbe__svg--<?php echo esc_attr( $la_fabrique_nom ); ?>" viewBox="0 0 <?php echo (int) $la_fabrique_f['largeur']; ?> <?php echo (int) $la_fabrique_f['hauteur']; ?>" role="img" aria-labelledby="<?php echo esc_attr( $la_fabrique_id ); ?>-titre <?php echo esc_attr( $la_fabrique_id ); ?>-desc" focusable="false">
			<title id="<?php echo esc_attr( $la_fabrique_id ); ?>-titre">
				<?php
				/* translators: %s : numéro du lot. */
				echo esc_html( sprintf( __( 'Courbe de torréfaction du lot n° %s', 'la-fabrique' ), $la_fabrique_lot['numero'] ) );
				?>
			</title>
			<desc id="<?php echo esc_attr( $la_fabrique_id ); ?>-desc">
				<?php
				$la_fabrique_morceaux = array();
				foreach ( $la_fabrique_courbe['etapes'] as $la_fabrique_etape ) {
					/* translators: 1 : nom de l'étape, 2 : durée, 3 : température. */
					$la_fabrique_morceaux[] = sprintf( __( '%1$s à %2$s, %3$d °C', 'la-fabrique' ), $la_fabrique_etape['libelle'], la_fabrique_duree( $la_fabrique_etape['minute'] ), $la_fabrique_etape['temperature'] );
				}
				echo esc_html( implode( ' ; ', $la_fabrique_morceaux ) . '.' );
				?>
			</desc>
			<defs>
				<clipPath id="<?php echo esc_attr( $la_fabrique_id ); ?>-revele">
					<rect class="lf-courbe__rideau" x="0" y="0" width="<?php echo (int) $la_fabrique_f['largeur']; ?>" height="<?php echo (int) $la_fabrique_f['hauteur']; ?>"/>
				</clipPath>
			</defs>

			<g class="lf-courbe__phases">
				<?php foreach ( $la_fabrique_courbe['phases'] as $la_fabrique_i => $la_fabrique_phase ) : ?>
					<rect class="lf-courbe__phase lf-courbe__phase--<?php echo (int) $la_fabrique_i; ?>" x="<?php echo esc_attr( $la_fabrique_x( $la_fabrique_phase[0] ) ); ?>" y="<?php echo (int) $la_fabrique_h; ?>" width="<?php echo esc_attr( round( $la_fabrique_x( $la_fabrique_phase[1] ) - $la_fabrique_x( $la_fabrique_phase[0] ), 1 ) ); ?>" height="<?php echo (int) ( $la_fabrique_b - $la_fabrique_h ); ?>"/>
					<text class="lf-courbe__nom-phase" x="<?php echo esc_attr( $la_fabrique_x( $la_fabrique_phase[1] ) - 10 ); ?>" y="<?php echo (int) ( $la_fabrique_b - 12 ); ?>" text-anchor="end"><?php echo esc_html( $la_fabrique_phase[2] ); ?></text>
				<?php endforeach; ?>
			</g>

			<g class="lf-courbe__grille">
				<?php foreach ( array( 100, 150, 200 ) as $la_fabrique_t ) : ?>
					<line x1="<?php echo (int) $la_fabrique_g; ?>" x2="<?php echo (int) $la_fabrique_d; ?>" y1="<?php echo esc_attr( $la_fabrique_y( $la_fabrique_t ) ); ?>" y2="<?php echo esc_attr( $la_fabrique_y( $la_fabrique_t ) ); ?>"/>
					<text class="lf-courbe__axe" x="<?php echo (int) ( $la_fabrique_g - 10 ); ?>" y="<?php echo esc_attr( $la_fabrique_y( $la_fabrique_t ) + 4 ); ?>" text-anchor="end"><?php echo esc_html( $la_fabrique_t . '°' ); ?></text>
				<?php endforeach; ?>
				<line class="lf-courbe__base" x1="<?php echo (int) $la_fabrique_g; ?>" x2="<?php echo (int) $la_fabrique_d; ?>" y1="<?php echo (int) $la_fabrique_b; ?>" y2="<?php echo (int) $la_fabrique_b; ?>"/>
				<?php for ( $la_fabrique_m = 0; $la_fabrique_m <= 12; $la_fabrique_m += $la_fabrique_f['pas'] ) : ?>
					<text class="lf-courbe__axe" x="<?php echo esc_attr( $la_fabrique_x( $la_fabrique_m ) ); ?>" y="<?php echo (int) ( $la_fabrique_b + 22 ); ?>" text-anchor="middle"><?php echo esc_html( 12 === $la_fabrique_m ? __( '12 min', 'la-fabrique' ) : (string) $la_fabrique_m ); ?></text>
				<?php endfor; ?>
			</g>

			<g clip-path="url(#<?php echo esc_attr( $la_fabrique_id ); ?>-revele)">
				<path class="lf-courbe__ligne" d="<?php echo esc_attr( la_fabrique_trace_lisse( $la_fabrique_points ) ); ?>"/>
			</g>

			<?php foreach ( $la_fabrique_courbe['etapes'] as $la_fabrique_i => $la_fabrique_etape ) : ?>
				<?php
				$la_fabrique_px = $la_fabrique_x( $la_fabrique_etape['minute'] );
				$la_fabrique_py = $la_fabrique_y( $la_fabrique_etape['temperature'] );
				list( $la_fabrique_dx, $la_fabrique_dy, $la_fabrique_ancre ) = $la_fabrique_f['etiquettes'][ $la_fabrique_i ];
				?>
				<g class="lf-courbe__repere" style="--lf-delai: <?php echo esc_attr( round( $la_fabrique_etape['minute'] / 12, 2 ) ); ?>">
					<circle cx="<?php echo esc_attr( $la_fabrique_px ); ?>" cy="<?php echo esc_attr( $la_fabrique_py ); ?>" r="<?php echo 'large' === $la_fabrique_nom ? 5 : 7; ?>"/>
					<text x="<?php echo esc_attr( $la_fabrique_px + $la_fabrique_dx ); ?>" y="<?php echo esc_attr( $la_fabrique_py + $la_fabrique_dy ); ?>" text-anchor="<?php echo esc_attr( $la_fabrique_ancre ); ?>">
						<tspan class="lf-courbe__etape"><?php echo esc_html( $la_fabrique_etape['libelle'] ); ?></tspan>
						<?php if ( $la_fabrique_f['valeurs'] ) : ?>
							<tspan class="lf-courbe__valeur" x="<?php echo esc_attr( $la_fabrique_px + $la_fabrique_dx ); ?>" dy="16"><?php echo esc_html( la_fabrique_duree( $la_fabrique_etape['minute'] ) . ' · ' . $la_fabrique_etape['temperature'] . ' °C' ); ?></tspan>
						<?php endif; ?>
					</text>
				</g>
			<?php endforeach; ?>
		</svg>
	<?php endforeach; ?>
	<figcaption class="lf-courbe__legende">
		<?php
		printf(
			/* translators: 1 : numéro du lot, 2 : durée totale, 3 : température de sortie. */
			esc_html__( 'Lot n° %1$s : la température du grain pendant la torréfaction. Sortie à %2$s, %3$d °C.', 'la-fabrique' ),
			esc_html( $la_fabrique_lot['numero'] ),
			esc_html( la_fabrique_duree( $la_fabrique_sortie['minute'] ) ),
			(int) $la_fabrique_sortie['temperature']
		);
		?>
	</figcaption>
</figure>
