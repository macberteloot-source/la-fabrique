<?php
/**
 * Corps commun de l'archive des recettes et des pages de méthode.
 *
 * Les filtres sont de vrais liens vers les pages de taxonomie. recettes.js les intercepte :
 * il interroge la route REST (fabrique/v1/recettes?methode=slug), remplace la grille, met à jour
 * le titre, l'URL (history.pushState) et annonce le nombre de résultats (role="status").
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;

global $wp_query;

$la_fabrique_terme = is_tax( 'methode' ) ? get_queried_object() : null;
$la_fabrique_titre = $la_fabrique_terme ? $la_fabrique_terme->name : __( 'Recettes', 'la-fabrique' );
$la_fabrique_intro = $la_fabrique_terme ? term_description( $la_fabrique_terme ) : '';
if ( '' === trim( wp_strip_all_tags( (string) $la_fabrique_intro ) ) ) {
	$la_fabrique_intro = '<p>' . esc_html__( 'Nos recettes de comptoir, testées et chronométrées : la dose, l’eau, la mouture et le geste. Choisissez une méthode.', 'la-fabrique' ) . '</p>';
}
$la_fabrique_total = (int) $wp_query->found_posts;
?>
<main id="contenu" class="lf-main lf-recettes" tabindex="-1" data-lf-recettes data-lf-methode="<?php echo esc_attr( $la_fabrique_terme ? $la_fabrique_terme->slug : '' ); ?>">
	<header class="lf-page-tete lf-conteneur">
		<?php la_fabrique_fil_ariane(); ?>
		<h1 class="lf-page-tete__titre" data-lf-recettes-titre><?php echo esc_html( $la_fabrique_titre ); ?></h1>
		<div class="lf-page-tete__intro"><?php echo wp_kses_post( $la_fabrique_intro ); ?></div>
		<?php get_template_part( 'template-parts/recette/filtres', null, array( 'courante' => $la_fabrique_terme ? $la_fabrique_terme->slug : '' ) ); ?>
	</header>

	<div class="lf-conteneur lf-page-corps">
		<p class="lf-recettes__statut" role="status" data-lf-recettes-statut>
			<?php
			/* translators: %d : nombre de recettes. */
			echo esc_html( sprintf( _n( '%d recette', '%d recettes', $la_fabrique_total, 'la-fabrique' ), $la_fabrique_total ) );
			?>
		</p>

		<?php if ( have_posts() ) : ?>
			<div class="lf-grille lf-grille--recettes" data-lf-recettes-grille>
				<?php
				update_post_thumbnail_cache( $wp_query );
				while ( have_posts() ) :
					the_post();
					la_fabrique_carte_recette( la_fabrique_donnees_recette( get_post() ), array( 'niveau' => 2 ) );
				endwhile;
				?>
			</div>
			<div class="lf-recettes__pagination" data-lf-recettes-pagination>
				<?php la_fabrique_pagination(); ?>
			</div>
		<?php else : ?>
			<div class="lf-grille lf-grille--recettes" data-lf-recettes-grille></div>
			<?php get_template_part( 'template-parts/contenu/aucun' ); ?>
		<?php endif; ?>
	</div>
</main>
<?php
get_template_part( 'template-parts/recette/modele-carte' );
