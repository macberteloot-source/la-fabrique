<?php
/**
 * Accueil : « Nos cafés du moment », avec les mêmes cartes produits que la boutique.
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'la_fabrique_boucle_produits' ) ) {
	return;
}

$la_fabrique_cafes = wc_get_products(
	array(
		'status'     => 'publish',
		'visibility' => 'catalog',
		'limit'      => 4,
		'category'   => array( 'cafes' ),
		'orderby'    => 'menu_order',
		'order'      => 'ASC',
	)
);
if ( ! $la_fabrique_cafes ) {
	return;
}

$la_fabrique_terme = get_term_by( 'slug', 'cafes', 'product_cat' );
$la_fabrique_lien  = $la_fabrique_terme ? get_term_link( $la_fabrique_terme ) : wc_get_page_permalink( 'shop' );
if ( is_wp_error( $la_fabrique_lien ) ) {
	$la_fabrique_lien = wc_get_page_permalink( 'shop' );
}
?>
<section class="lf-section lf-cafes" aria-labelledby="lf-cafes-titre">
	<div class="lf-conteneur">
		<div class="lf-section__tete">
			<h2 class="lf-section__titre" id="lf-cafes-titre" data-lf-revele="lignes"><?php esc_html_e( 'Nos cafés du moment', 'la-fabrique' ); ?></h2>
			<p class="lf-section__intro"><?php esc_html_e( 'Des lots de petits producteurs, torréfiés par quinze kilos. En grains ou moulus pour votre méthode, en 250 g ou en 1 kg.', 'la-fabrique' ); ?></p>
			<a class="lf-lien" href="<?php echo esc_url( $la_fabrique_lien ); ?>"><?php esc_html_e( 'Tous les cafés', 'la-fabrique' ); ?></a>
		</div>
		<?php la_fabrique_boucle_produits( $la_fabrique_cafes, 4, 'accueil' ); ?>
	</div>
</section>
