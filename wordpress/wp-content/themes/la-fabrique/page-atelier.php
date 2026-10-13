<?php
/**
 * Page « L'atelier » : l'histoire de la filature, l'illustration du bâtiment, puis le contenu
 * de l'éditeur à côté d'une fiche pratique (torréfaction, retrait, contact).
 *
 * Hiérarchie des templates : page-{slug}.php. WordPress choisit ce fichier pour la page dont le
 * slug est « atelier » (créée par bin/seed.php), avant page.php, sans aucun code ; un modèle
 * choisi dans l'éditeur pour cette page resterait prioritaire.
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;

get_header();

$la_fabrique_lot = la_fabrique_lot();
?>

<main id="contenu" class="lf-main lf-atelier" tabindex="-1">
	<?php
	while ( have_posts() ) :
		the_post();
		$la_fabrique_mention = false !== stripos( wp_strip_all_tags( get_the_content() ), 'fictive' );
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'lf-page' ); ?>>
			<header class="lf-conteneur lf-atelier__tete">
				<?php la_fabrique_fil_ariane(); ?>
				<h1 class="lf-atelier__titre" data-lf-revele="lignes"><?php the_title(); ?></h1>
				<p class="lf-atelier__chapo">
					<?php
					if ( has_excerpt() ) {
						echo esc_html( get_the_excerpt() );
					} else {
						esc_html_e( 'Une filature de briques, à Fives. Des sheds, une cheminée, un torréfacteur de quinze kilos, et le mardi, une odeur qui fait tourner les têtes dans la rue.', 'la-fabrique' );
					}
					?>
				</p>
			</header>

			<figure class="lf-conteneur lf-atelier__illustration" aria-hidden="true">
				<?php echo wp_kses( la_fabrique_get_svg( 'atelier.svg', 'lf-atelier__dessin' ), la_fabrique_svg_autorise() ); ?>
				<?php echo wp_kses( la_fabrique_get_svg( 'tampon.svg', 'lf-badge-tournant lf-atelier__badge' ), la_fabrique_svg_autorise() ); ?>
			</figure>

			<div class="lf-conteneur lf-atelier__corps">
				<div class="lf-prose lf-atelier__contenu">
					<?php the_content(); ?>
				</div>

				<aside class="lf-atelier__fiche" aria-labelledby="lf-atelier-pratique">
					<h2 class="lf-atelier__fiche-titre" id="lf-atelier-pratique"><?php esc_html_e( 'En pratique', 'la-fabrique' ); ?></h2>
					<dl class="lf-fiche">
						<div class="lf-fiche__ligne">
							<dt><?php la_fabrique_icone( 'origine' ); ?><?php esc_html_e( 'Adresse', 'la-fabrique' ); ?></dt>
							<dd><?php esc_html_e( 'Atelier à Fives, Lille', 'la-fabrique' ); ?></dd>
						</div>
						<div class="lf-fiche__ligne">
							<dt><?php la_fabrique_icone( 'flamme' ); ?><?php esc_html_e( 'Torréfaction', 'la-fabrique' ); ?></dt>
							<dd>
								<?php
								/* translators: 1 : numéro du lot, 2 : date du prochain mardi (jj.mm). */
								echo esc_html( sprintf( __( 'Le mardi. Lot n° %1$s en cours, prochain lot le %2$s.', 'la-fabrique' ), $la_fabrique_lot['numero'], $la_fabrique_lot['prochaine']->format( 'd.m' ) ) );
								?>
							</dd>
						</div>
						<div class="lf-fiche__ligne">
							<dt><?php la_fabrique_icone( 'atelier' ); ?><?php esc_html_e( 'Retrait des commandes', 'la-fabrique' ); ?></dt>
							<dd>
								<?php foreach ( la_fabrique_horaires_retrait() as $la_fabrique_horaire ) : ?>
									<span class="lf-fiche__bloc"><?php echo esc_html( $la_fabrique_horaire ); ?></span>
								<?php endforeach; ?>
							</dd>
						</div>
						<div class="lf-fiche__ligne">
							<dt><?php la_fabrique_icone( 'tasse' ); ?><?php esc_html_e( 'Nous écrire', 'la-fabrique' ); ?></dt>
							<dd><a class="lf-lien" href="mailto:bonjour@la-fabrique.test">bonjour@la-fabrique.test</a></dd>
						</div>
					</dl>
					<?php
					if ( ! $la_fabrique_mention ) {
						la_fabrique_marque_fictive();
					}
					?>
				</aside>
			</div>
		</article>
	<?php endwhile; ?>
</main>

<?php
get_footer();
