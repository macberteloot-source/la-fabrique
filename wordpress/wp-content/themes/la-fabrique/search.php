<?php
/**
 * Résultats de recherche : cafés, accessoires, recettes et pages, dans une même liste typée.
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;

get_header();

global $wp_query;
?>

<main id="contenu" class="lf-main" tabindex="-1">
	<header class="lf-page-tete lf-conteneur">
		<?php la_fabrique_fil_ariane(); ?>
		<h1 class="lf-page-tete__titre">
			<?php
			/* translators: %s : termes recherchés. */
			printf( esc_html__( 'Recherche : « %s »', 'la-fabrique' ), esc_html( get_search_query() ) );
			?>
		</h1>
		<p class="lf-page-tete__intro" role="status">
			<?php
			$la_fabrique_total = (int) $wp_query->found_posts;
			echo esc_html(
				$la_fabrique_total
					/* translators: %d : nombre de résultats. */
					? sprintf( _n( '%d résultat', '%d résultats', $la_fabrique_total, 'la-fabrique' ), $la_fabrique_total )
					: __( 'Aucun résultat.', 'la-fabrique' )
			);
			?>
		</p>
		<div class="lf-page-tete__recherche"><?php get_search_form(); ?></div>
	</header>

	<div class="lf-conteneur lf-page-corps">
		<?php if ( have_posts() ) : ?>
			<ol class="lf-resultats">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/contenu/resultat' );
				endwhile;
				?>
			</ol>
			<?php la_fabrique_pagination(); ?>
		<?php else : ?>
			<?php get_template_part( 'template-parts/contenu/aucun' ); ?>
		<?php endif; ?>
	</div>
</main>

<?php
get_footer();
