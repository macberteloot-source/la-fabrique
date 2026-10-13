<?php
/**
 * Accueil : l'atelier et le retrait des commandes, puis la mention « marque fictive ».
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;

$la_fabrique_lot     = la_fabrique_lot();
$la_fabrique_atelier = get_page_by_path( 'atelier' );
?>
<section class="lf-section lf-atelier-accueil" aria-labelledby="lf-atelier-titre">
	<div class="lf-conteneur lf-atelier-accueil__grille">
		<div class="lf-atelier-accueil__visuel" aria-hidden="true">
			<?php echo wp_kses( la_fabrique_get_svg( 'atelier.svg', 'lf-atelier-accueil__dessin' ), la_fabrique_svg_autorise() ); ?>
			<?php echo wp_kses( la_fabrique_get_svg( 'tampon.svg', 'lf-badge-tournant' ), la_fabrique_svg_autorise() ); ?>
		</div>

		<div class="lf-atelier-accueil__texte">
			<h2 class="lf-section__titre" id="lf-atelier-titre" data-lf-revele="lignes"><?php esc_html_e( 'L’atelier, à Fives', 'la-fabrique' ); ?></h2>
			<p class="lf-atelier-accueil__intro"><?php esc_html_e( 'Une ancienne filature de briques, ses toits en dents de scie, un torréfacteur de quinze kilos et une grande table où l’on goûte tout. Commandez en ligne, passez chercher votre café : il vous attend, emballé, au créneau choisi.', 'la-fabrique' ); ?></p>

			<dl class="lf-fiche">
				<div class="lf-fiche__ligne">
					<dt><?php la_fabrique_icone( 'flamme' ); ?><?php esc_html_e( 'Torréfaction', 'la-fabrique' ); ?></dt>
					<dd>
						<?php
						/* translators: %s : date du prochain mardi (jj.mm). */
						echo esc_html( sprintf( __( 'Le mardi. Prochain lot le %s.', 'la-fabrique' ), $la_fabrique_lot['prochaine']->format( 'd.m' ) ) );
						?>
					</dd>
				</div>
				<div class="lf-fiche__ligne">
					<dt><?php la_fabrique_icone( 'atelier' ); ?><?php esc_html_e( 'Retrait à l’atelier', 'la-fabrique' ); ?></dt>
					<dd><?php echo esc_html( implode( ' · ', la_fabrique_horaires_retrait() ) ); ?></dd>
				</div>
				<div class="lf-fiche__ligne">
					<dt><?php la_fabrique_icone( 'livraison' ); ?><?php esc_html_e( 'Livraison', 'la-fabrique' ); ?></dt>
					<dd>
						<?php
						/* translators: %s : seuil de la livraison offerte, par exemple « 45 € ». */
						echo esc_html( sprintf( __( 'Colissimo en 48 h, offerte dès %s.', 'la-fabrique' ), la_fabrique_seuil_livraison() ) );
						?>
					</dd>
				</div>
			</dl>

			<?php if ( $la_fabrique_atelier ) : ?>
				<a class="lf-bouton lf-bouton--contour" href="<?php echo esc_url( get_permalink( $la_fabrique_atelier ) ); ?>" data-lf-magnetique>
					<span><?php esc_html_e( 'Découvrir l’atelier', 'la-fabrique' ); ?></span>
				</a>
			<?php endif; ?>

			<?php la_fabrique_marque_fictive( 'lf-fictive lf-atelier-accueil__fictive' ); ?>
		</div>
	</div>
</section>
