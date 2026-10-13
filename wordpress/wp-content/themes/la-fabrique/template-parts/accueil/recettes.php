<?php
/**
 * Accueil : les trois dernières recettes, en une mise en page éditoriale (une grande, deux en ligne).
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;

if ( ! la_fabrique_recettes_actives() ) {
	return;
}

$la_fabrique_requete = new WP_Query(
	array(
		'post_type'           => 'recette',
		'post_status'         => 'publish',
		'posts_per_page'      => 3,
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
	)
);
if ( ! $la_fabrique_requete->have_posts() ) {
	return;
}
// Une seule requête pour les images mises en avant des trois cartes.
update_post_thumbnail_cache( $la_fabrique_requete );
?>
<section class="lf-section lf-recettes-accueil" aria-labelledby="lf-recettes-titre">
	<div class="lf-conteneur">
		<div class="lf-section__tete">
			<h2 class="lf-section__titre" id="lf-recettes-titre" data-lf-revele="lignes"><?php esc_html_e( 'Les dernières recettes', 'la-fabrique' ); ?></h2>
			<p class="lf-section__intro"><?php esc_html_e( 'Testées au comptoir avant d’être publiées : la dose, l’eau, la mouture, le temps, et l’astuce qui change tout.', 'la-fabrique' ); ?></p>
			<a class="lf-lien" href="<?php echo esc_url( (string) get_post_type_archive_link( 'recette' ) ); ?>"><?php esc_html_e( 'Toutes les recettes', 'la-fabrique' ); ?></a>
		</div>
		<div class="lf-recettes-accueil__grille">
			<?php
			foreach ( $la_fabrique_requete->posts as $la_fabrique_i => $la_fabrique_recette ) {
				la_fabrique_carte_recette(
					la_fabrique_donnees_recette( $la_fabrique_recette ),
					array(
						'niveau'   => 3,
						'variante' => 0 === $la_fabrique_i ? 'une' : 'ligne',
					)
				);
			}
			?>
		</div>
	</div>
</section>
