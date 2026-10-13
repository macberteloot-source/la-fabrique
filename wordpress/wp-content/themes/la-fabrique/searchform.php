<?php
/**
 * Formulaire de recherche (cafés, accessoires, recettes, pages).
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;

$la_fabrique_id = wp_unique_id( 'lf-recherche-champ-' );
?>
<form role="search" method="get" class="lf-recherche-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $la_fabrique_id ); ?>"><?php esc_html_e( 'Rechercher sur le site', 'la-fabrique' ); ?></label>
	<input type="search" id="<?php echo esc_attr( $la_fabrique_id ); ?>" class="lf-recherche-form__champ" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Un café, une recette, un moulin…', 'la-fabrique' ); ?>" autocomplete="off">
	<button type="submit" class="lf-bouton lf-recherche-form__bouton">
		<?php la_fabrique_icone( 'recherche' ); ?>
		<span><?php esc_html_e( 'Rechercher', 'la-fabrique' ); ?></span>
	</button>
</form>
