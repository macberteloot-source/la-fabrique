<?php
/**
 * Page d'accueil (page statique « Accueil »).
 *
 * Chaque section est une partie de gabarit (template-parts/accueil/) : une section dont les données
 * manquent (WooCommerce ou plugin des recettes désactivé) ne s'affiche simplement pas.
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="contenu" class="lf-main lf-main--accueil" tabindex="-1">
	<?php
	get_template_part( 'template-parts/accueil/hero' );
	get_template_part( 'template-parts/accueil/bandeau' );
	get_template_part( 'template-parts/accueil/cafes' );
	get_template_part( 'template-parts/accueil/etapes' );
	get_template_part( 'template-parts/accueil/recettes' );
	get_template_part( 'template-parts/accueil/atelier' );
	?>
</main>

<?php
get_footer();
