<?php
/**
 * Gabarit de dernier recours de la hiérarchie (et page des articles : « Le journal »).
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="contenu" class="lf-main" tabindex="-1">
	<header class="lf-page-tete lf-conteneur">
		<h1 class="lf-page-tete__titre" data-lf-revele="lignes">
			<?php
			if ( is_home() && ! is_front_page() ) {
				single_post_title();
			} else {
				esc_html_e( 'Le journal de l’atelier', 'la-fabrique' );
			}
			?>
		</h1>
	</header>

	<div class="lf-conteneur lf-page-corps">
		<?php if ( have_posts() ) : ?>
			<div class="lf-grille lf-grille--3">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/contenu/carte' );
				endwhile;
				?>
			</div>
			<?php la_fabrique_pagination(); ?>
		<?php else : ?>
			<?php get_template_part( 'template-parts/contenu/aucun' ); ?>
		<?php endif; ?>
	</div>
</main>

<?php
get_footer();
