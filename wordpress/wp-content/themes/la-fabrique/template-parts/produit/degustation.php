<?php
/**
 * Fiche de dégustation d'un café (données de fabrique_cafe_fiche()).
 *
 * Présentée comme l'étiquette tamponnée des sacs : intensité sur 5, échelle de torréfaction,
 * origine, procédé, altitude.
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;

$la_fabrique_fiche = $args['fiche'] ?? array();
$la_fabrique_lot   = la_fabrique_lot();

// Les quatre degrés de torréfaction du contrat (champ de sélection « fabrique_torrefaction »).
$la_fabrique_degres = array(
	'claire'         => __( 'Claire', 'la-fabrique' ),
	'moyenne'        => __( 'Moyenne', 'la-fabrique' ),
	'moyenne-foncee' => __( 'Moyenne-foncée', 'la-fabrique' ),
	'foncee'         => __( 'Foncée', 'la-fabrique' ),
);
$la_fabrique_rang   = array_search( $la_fabrique_fiche['torrefaction'], array_keys( $la_fabrique_degres ), true );
$la_fabrique_id     = wp_unique_id( 'lf-degustation-' );
?>
<section class="lf-degustation" aria-labelledby="<?php echo esc_attr( $la_fabrique_id ); ?>">
	<div class="lf-degustation__tete">
		<h2 class="lf-degustation__titre" id="<?php echo esc_attr( $la_fabrique_id ); ?>"><?php esc_html_e( 'Fiche de dégustation', 'la-fabrique' ); ?></h2>
		<p class="lf-degustation__lot">
			<?php
			/* translators: %s : numéro du lot. */
			echo esc_html( sprintf( __( 'Lot n° %s', 'la-fabrique' ), $la_fabrique_lot['numero'] ) );
			?>
		</p>
	</div>

	<dl class="lf-degustation__liste">
		<?php if ( $la_fabrique_fiche['intensite'] ) : ?>
			<div class="lf-degustation__ligne">
				<dt><?php esc_html_e( 'Intensité', 'la-fabrique' ); ?></dt>
				<dd>
					<span class="lf-jauge" style="--lf-niveau: <?php echo (int) $la_fabrique_fiche['intensite']; ?>" aria-hidden="true">
						<?php for ( $la_fabrique_i = 1; $la_fabrique_i <= 5; $la_fabrique_i++ ) : ?>
							<span class="lf-jauge__grain<?php echo $la_fabrique_i <= $la_fabrique_fiche['intensite'] ? ' is-plein' : ''; ?>"></span>
						<?php endfor; ?>
					</span>
					<span class="lf-degustation__valeur">
						<?php
						/* translators: %d : intensité de 1 à 5. */
						echo esc_html( sprintf( __( '%d sur 5', 'la-fabrique' ), $la_fabrique_fiche['intensite'] ) );
						?>
					</span>
				</dd>
			</div>
		<?php endif; ?>

		<?php if ( false !== $la_fabrique_rang ) : ?>
			<div class="lf-degustation__ligne">
				<dt><?php esc_html_e( 'Torréfaction', 'la-fabrique' ); ?></dt>
				<dd>
					<span class="lf-echelle" style="--lf-rang: <?php echo (int) $la_fabrique_rang; ?>" aria-hidden="true">
						<?php foreach ( array_keys( $la_fabrique_degres ) as $la_fabrique_j => $la_fabrique_degre ) : ?>
							<span class="lf-echelle__case<?php echo $la_fabrique_j === $la_fabrique_rang ? ' is-active' : ''; ?>"></span>
						<?php endforeach; ?>
					</span>
					<span class="lf-degustation__valeur"><?php echo esc_html( $la_fabrique_fiche['torrefaction_libelle'] ? $la_fabrique_fiche['torrefaction_libelle'] : $la_fabrique_degres[ $la_fabrique_fiche['torrefaction'] ] ); ?></span>
				</dd>
			</div>
		<?php endif; ?>

		<?php if ( $la_fabrique_fiche['origine'] ) : ?>
			<div class="lf-degustation__ligne">
				<dt><?php esc_html_e( 'Origine', 'la-fabrique' ); ?></dt>
				<dd><?php echo esc_html( la_fabrique_majuscule( $la_fabrique_fiche['origine'] ) ); ?></dd>
			</div>
		<?php endif; ?>

		<?php if ( $la_fabrique_fiche['procede'] ) : ?>
			<div class="lf-degustation__ligne">
				<dt><?php esc_html_e( 'Procédé', 'la-fabrique' ); ?></dt>
				<dd><?php echo esc_html( la_fabrique_majuscule( $la_fabrique_fiche['procede'] ) ); ?></dd>
			</div>
		<?php endif; ?>

		<?php if ( $la_fabrique_fiche['altitude'] ) : ?>
			<div class="lf-degustation__ligne">
				<dt><?php esc_html_e( 'Altitude', 'la-fabrique' ); ?></dt>
				<dd>
					<?php
					/* translators: %s : altitude en mètres. */
					echo esc_html( sprintf( __( '%s m', 'la-fabrique' ), number_format_i18n( $la_fabrique_fiche['altitude'] ) ) );
					?>
				</dd>
			</div>
		<?php endif; ?>
	</dl>
</section>
