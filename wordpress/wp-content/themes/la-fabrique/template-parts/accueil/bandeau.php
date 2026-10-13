<?php
/**
 * Accueil : bandeau défilant des cafés, sous la ligne des toits.
 *
 * Purement décoratif (aria-hidden) : les mêmes cafés sont présentés juste après, en cartes.
 * La liste est doublée pour que le défilement (une translation CSS) boucle sans à-coup ;
 * il s'arrête au survol et disparaît si l'utilisateur limite les animations.
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;

$la_fabrique_noms = array();
if ( function_exists( 'wc_get_products' ) ) {
	$la_fabrique_noms = wc_get_products(
		array(
			'status'   => 'publish',
			'limit'    => 8,
			'category' => array( 'cafes' ),
			'orderby'  => 'menu_order',
			'order'    => 'ASC',
			'return'   => 'objects',
		)
	);
	$la_fabrique_noms = array_map(
		static function ( $produit ) {
			return $produit->get_name();
		},
		$la_fabrique_noms
	);
}
if ( ! $la_fabrique_noms ) {
	$la_fabrique_noms = array( 'Wazemmes', 'Beffroi', 'Vieux-Lille', 'Fives', 'La Braderie', 'Nuit Blanche' );
}
$la_fabrique_grain = la_fabrique_get_svg( 'symbole.svg', 'lf-bandeau__grain' );
?>
<div class="lf-bandeau lf-sombre" aria-hidden="true">
	<div class="lf-bandeau__piste">
		<?php for ( $la_fabrique_copie = 0; $la_fabrique_copie < 2; $la_fabrique_copie++ ) : ?>
			<ul class="lf-bandeau__liste">
				<?php foreach ( $la_fabrique_noms as $la_fabrique_nom ) : ?>
					<li><?php echo esc_html( $la_fabrique_nom ); ?></li>
					<li class="lf-bandeau__separateur"><?php echo wp_kses( $la_fabrique_grain, la_fabrique_svg_autorise() ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endfor; ?>
	</div>
</div>
