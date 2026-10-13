<?php
/**
 * Accueil : le hero. Un grand titre, le tampon du lot de la semaine, la courbe de torréfaction
 * du lot et, en bas, la silhouette des sheds de Fives avec sa cheminée.
 *
 * L'introduction reprend le contenu de la page « Accueil » s'il existe (modifiable dans l'éditeur).
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;

$la_fabrique_lot   = la_fabrique_lot();
$la_fabrique_intro = '';
$la_fabrique_page  = (int) get_option( 'page_on_front' );
if ( $la_fabrique_page ) {
	$la_fabrique_intro = trim( wp_strip_all_tags( (string) get_post_field( 'post_content', $la_fabrique_page ) ) );
}
if ( '' === $la_fabrique_intro ) {
	$la_fabrique_intro = __( 'Cafés de spécialité torréfiés chaque mardi à Fives, recettes partagées et tasses de quartier.', 'la-fabrique' );
}

$la_fabrique_lien_cafes = '';
if ( taxonomy_exists( 'product_cat' ) ) {
	$la_fabrique_terme      = get_term_by( 'slug', 'cafes', 'product_cat' );
	$la_fabrique_lien_cafes = $la_fabrique_terme ? get_term_link( $la_fabrique_terme ) : '';
	if ( is_wp_error( $la_fabrique_lien_cafes ) || ! $la_fabrique_lien_cafes ) {
		$la_fabrique_lien_cafes = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : '';
	}
}
?>
<section class="lf-hero" aria-labelledby="lf-hero-titre">
	<div class="lf-conteneur lf-hero__grille">
		<h1 class="lf-hero__titre" id="lf-hero-titre">
			<span class="lf-hero__ligne"><span><?php esc_html_e( 'Le café,', 'la-fabrique' ); ?></span></span>
			<span class="lf-hero__ligne"><span><?php esc_html_e( 'ça se fabrique.', 'la-fabrique' ); ?></span></span>
		</h1>

		<div class="lf-hero__texte">
			<p class="lf-hero__intro"><?php echo esc_html( $la_fabrique_intro ); ?></p>
			<div class="lf-hero__actions">
				<?php if ( $la_fabrique_lien_cafes ) : ?>
					<a class="lf-bouton lf-bouton--grand" href="<?php echo esc_url( $la_fabrique_lien_cafes ); ?>" data-lf-magnetique>
						<span><?php esc_html_e( 'Choisir un café', 'la-fabrique' ); ?></span>
					</a>
				<?php endif; ?>
				<?php if ( la_fabrique_recettes_actives() ) : ?>
					<a class="lf-lien" href="<?php echo esc_url( (string) get_post_type_archive_link( 'recette' ) ); ?>"><?php esc_html_e( 'Lire les recettes', 'la-fabrique' ); ?></a>
				<?php endif; ?>
			</div>
		</div>

		<p class="lf-tampon lf-hero__tampon">
			<?php
			/* translators: %s : numéro du lot. */
			printf( '<span class="lf-tampon__grand">%s</span>', esc_html( sprintf( __( 'Lot n° %s', 'la-fabrique' ), $la_fabrique_lot['numero'] ) ) );
			/* translators: %s : date du mardi de torréfaction (jj.mm). */
			printf( '<span>%s</span>', esc_html( sprintf( __( 'Torréfié le mardi %s', 'la-fabrique' ), $la_fabrique_lot['date']->format( 'd.m' ) ) ) );
			?>
		</p>

		<?php get_template_part( 'template-parts/accueil/courbe', null, array( 'lot' => $la_fabrique_lot ) ); ?>
	</div>

	<div class="lf-hero__ville" aria-hidden="true">
		<div class="lf-toits lf-hero__toits"></div>
		<svg class="lf-hero__cheminee" viewBox="0 0 120 230" width="120" height="230" focusable="false">
			<circle class="lf-fumee lf-fumee--1" cx="66" cy="62" r="13"/>
			<circle class="lf-fumee lf-fumee--2" cx="74" cy="42" r="17"/>
			<circle class="lf-fumee lf-fumee--3" cx="86" cy="18" r="21"/>
			<path class="lf-hero__brique" d="M48 230 54 84h28l6 146Z"/>
			<path class="lf-hero__brique" d="M50 74h36v14H50Z"/>
		</svg>
	</div>
</section>
