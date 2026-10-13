<?php
/**
 * Archives génériques (catégories, étiquettes, dates, auteurs).
 *
 * Les recettes ont leurs propres gabarits (archive-recette.php, taxonomy-methode.php)
 * et la boutique ceux de WooCommerce, habillés par inc/woocommerce.php.
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="contenu" class="lf-main" tabindex="-1">
	<header class="lf-page-tete lf-conteneur">
		<?php la_fabrique_fil_ariane(); ?>
		<h1 class="lf-page-tete__titre" data-lf-revele="lignes"><?php echo esc_html( wp_strip_all_tags( get_the_archive_title() ) ); ?></h1>
		<?php the_archive_description( '<div class="lf-page-tete__intro">', '</div>' ); ?>
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
