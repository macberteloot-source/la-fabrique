<?php
/**
 * Article seul (journal de l'atelier).
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="contenu" class="lf-main" tabindex="-1">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'lf-article' ); ?>>
			<header class="lf-page-tete lf-conteneur">
				<?php la_fabrique_fil_ariane(); ?>
				<h1 class="lf-page-tete__titre" data-lf-revele="lignes"><?php the_title(); ?></h1>
				<?php la_fabrique_meta_article(); ?>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="lf-conteneur lf-article__image">
					<?php the_post_thumbnail( 'large', array( 'loading' => 'eager' ) ); ?>
				</figure>
			<?php endif; ?>

			<div class="lf-conteneur lf-page-corps">
				<div class="lf-prose">
					<?php
					the_content();
					wp_link_pages();
					?>
				</div>
				<?php
				$la_fabrique_etiquettes = get_the_tag_list( '<ul class="lf-puces"><li class="lf-puce">', '</li><li class="lf-puce">', '</li></ul>' );
				if ( $la_fabrique_etiquettes && ! is_wp_error( $la_fabrique_etiquettes ) ) {
					echo wp_kses_post( $la_fabrique_etiquettes );
				}
				?>
			</div>

			<div class="lf-conteneur">
				<?php
				the_post_navigation(
					array(
						'class'     => 'lf-voisins',
						'prev_text' => '<span class="lf-voisins__sens">' . esc_html__( 'Article précédent', 'la-fabrique' ) . '</span><span class="lf-voisins__titre">%title</span>',
						'next_text' => '<span class="lf-voisins__sens">' . esc_html__( 'Article suivant', 'la-fabrique' ) . '</span><span class="lf-voisins__titre">%title</span>',
					)
				);
				?>
			</div>
		</article>
		<?php
		if ( comments_open() || get_comments_number() ) {
			comments_template();
		}
	endwhile;
	?>
</main>

<?php
get_footer();
