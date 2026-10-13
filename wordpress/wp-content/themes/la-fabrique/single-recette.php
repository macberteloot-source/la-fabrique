<?php
/**
 * Fiche recette (type de contenu « recette » du plugin fabrique-recettes).
 *
 * En-tête (méthode, titre, chapô, illustration), fiche technique (temps, difficulté, ratio,
 * température, mouture), étapes (contenu de l'éditeur), astuce du barista, cafés recommandés.
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
		$la_fabrique_infos = la_fabrique_infos_recette( get_the_ID() );
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'lf-recette' ); ?>>
			<header class="lf-conteneur lf-recette__tete">
				<div class="lf-recette__intro">
					<?php la_fabrique_fil_ariane(); ?>
					<?php if ( $la_fabrique_infos['methodes'] ) : ?>
						<ul class="lf-puces lf-recette__methodes">
							<?php foreach ( $la_fabrique_infos['methodes'] as $la_fabrique_methode ) : ?>
								<li><a class="lf-puce" href="<?php echo esc_url( $la_fabrique_methode['lien'] ); ?>"><?php echo esc_html( $la_fabrique_methode['nom'] ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<h1 class="lf-recette__titre" data-lf-revele="lignes"><?php the_title(); ?></h1>
					<?php if ( has_excerpt() ) : ?>
						<p class="lf-recette__chapo"><?php echo esc_html( get_the_excerpt() ); ?></p>
					<?php endif; ?>
				</div>
				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="lf-recette__image">
						<?php
						the_post_thumbnail(
							'la-fabrique-recette-grande',
							array(
								'loading'       => 'eager',
								'fetchpriority' => 'high',
								'sizes'         => '(min-width: 960px) 52vw, 100vw',
							)
						);
						?>
					</figure>
				<?php endif; ?>
			</header>

			<?php get_template_part( 'template-parts/recette/fiche', null, array( 'infos' => $la_fabrique_infos ) ); ?>

			<div class="lf-conteneur lf-recette__corps">
				<div class="lf-prose lf-recette__contenu">
					<?php the_content(); ?>
				</div>

				<?php if ( $la_fabrique_infos['astuce'] ) : ?>
					<aside class="lf-astuce" aria-labelledby="lf-astuce-titre" data-lf-revele>
						<h2 class="lf-astuce__titre" id="lf-astuce-titre"><?php esc_html_e( 'L’astuce du barista', 'la-fabrique' ); ?></h2>
						<p class="lf-astuce__texte"><?php echo esc_html( $la_fabrique_infos['astuce'] ); ?></p>
						<?php echo wp_kses( la_fabrique_get_svg( 'tampon.svg', 'lf-astuce__tampon' ), la_fabrique_svg_autorise() ); ?>
					</aside>
				<?php endif; ?>
			</div>

			<?php get_template_part( 'template-parts/recette/cafes', null, array( 'ids' => $la_fabrique_infos['cafes'] ) ); ?>

			<div class="lf-conteneur">
				<?php
				the_post_navigation(
					array(
						'class'     => 'lf-voisins',
						'prev_text' => '<span class="lf-voisins__sens">' . esc_html__( 'Recette précédente', 'la-fabrique' ) . '</span><span class="lf-voisins__titre">%title</span>',
						'next_text' => '<span class="lf-voisins__sens">' . esc_html__( 'Recette suivante', 'la-fabrique' ) . '</span><span class="lf-voisins__titre">%title</span>',
					)
				);
				?>
			</div>
		</article>
	<?php endwhile; ?>
</main>

<?php
get_footer();
