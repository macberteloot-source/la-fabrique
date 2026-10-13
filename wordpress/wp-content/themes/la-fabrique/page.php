<?php
/**
 * Pages (dont Panier, Commande et Mon compte, qui contiennent les shortcodes de WooCommerce).
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;

get_header();

/**
 * Filtre : la page utilise-t-elle toute la largeur (tunnel de commande, compte client) ?
 *
 * @param bool $large Vrai pour une mise en page large.
 */
$la_fabrique_large = (bool) apply_filters( 'la_fabrique_page_large', false );
?>

<main id="contenu" class="lf-main" tabindex="-1">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'lf-page' ); ?>>
			<header class="lf-page-tete lf-conteneur<?php echo $la_fabrique_large ? ' lf-page-tete--compacte' : ''; ?>">
				<?php
				if ( ! $la_fabrique_large ) {
					la_fabrique_fil_ariane();
				}

				/**
				 * Avant le titre de la page (étapes du tunnel de commande, par exemple).
				 */
				do_action( 'la_fabrique_page_tete' );
				?>
				<h1 class="lf-page-tete__titre" data-lf-revele="lignes"><?php the_title(); ?></h1>
				<?php if ( has_excerpt() ) : ?>
					<div class="lf-page-tete__intro"><?php the_excerpt(); ?></div>
				<?php endif; ?>
			</header>

			<div class="lf-conteneur lf-page-corps">
				<div class="<?php echo $la_fabrique_large ? 'lf-large' : 'lf-prose'; ?>">
					<?php
					the_content();
					wp_link_pages(
						array(
							'before' => '<nav class="lf-pages-article" aria-label="' . esc_attr__( 'Pages de l’article', 'la-fabrique' ) . '">',
							'after'  => '</nav>',
						)
					);
					?>
				</div>
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
