<?php
/**
 * Aucun contenu à afficher : on propose une piste plutôt qu'une impasse.
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="lf-vide">
	<h2 class="lf-vide__titre">
		<?php
		if ( is_search() ) {
			esc_html_e( 'Rien ne correspond à cette recherche.', 'la-fabrique' );
		} else {
			esc_html_e( 'Rien à afficher ici pour l’instant.', 'la-fabrique' );
		}
		?>
	</h2>
	<p><?php esc_html_e( 'Essayez un nom de café (Beffroi, Wazemmes…), une méthode (espresso, cold brew…) ou un accessoire.', 'la-fabrique' ); ?></p>
	<?php
	if ( ! is_search() ) {
		get_search_form();
	}
	?>
</div>
