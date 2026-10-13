<?php
/**
 * Fiche recette : les cafés recommandés, avec les cartes de la boutique.
 *
 * Arguments : ids (int[]) — identifiants des produits (déjà filtrés par le plugin : publiés seulement).
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;

$la_fabrique_ids = array_map( 'intval', $args['ids'] ?? array() );
if ( ! $la_fabrique_ids ) {
	return;
}
?>
<section class="lf-section lf-recette__cafes" aria-labelledby="lf-cafes-recommandes">
	<div class="lf-conteneur">
		<div class="lf-section__tete">
			<h2 class="lf-section__titre" id="lf-cafes-recommandes"><?php echo esc_html( _n( 'Le café recommandé', 'Les cafés recommandés', count( $la_fabrique_ids ), 'la-fabrique' ) ); ?></h2>
			<p class="lf-section__intro"><?php esc_html_e( 'Ceux que nous utilisons pour cette recette au comptoir. Choisissez la mouture adaptée au moment de commander.', 'la-fabrique' ); ?></p>
		</div>
		<?php
		if ( function_exists( 'la_fabrique_boucle_produits' ) ) {
			la_fabrique_boucle_produits(
				wc_get_products(
					array(
						'include' => $la_fabrique_ids,
						'status'  => 'publish',
						'limit'   => 3,
						'orderby' => 'include',
					)
				),
				3,
				'cafes-recommandes'
			);
		} else {
			echo '<ul class="lf-liste-simple">';
			foreach ( $la_fabrique_ids as $la_fabrique_id ) {
				printf( '<li><a class="lf-lien" href="%1$s">%2$s</a></li>', esc_url( get_permalink( $la_fabrique_id ) ), esc_html( get_the_title( $la_fabrique_id ) ) );
			}
			echo '</ul>';
		}
		?>
	</div>
</section>
