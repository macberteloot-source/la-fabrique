<?php
/**
 * Page introuvable.
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="contenu" class="lf-main lf-404" tabindex="-1">
	<div class="lf-conteneur lf-404__grille">
		<div class="lf-404__texte">
			<p class="lf-tampon lf-404__tampon"><?php esc_html_e( 'Lot introuvable', 'la-fabrique' ); ?></p>
			<h1 class="lf-page-tete__titre" data-lf-revele="lignes"><?php esc_html_e( 'Ce grain a roulé sous le comptoir.', 'la-fabrique' ); ?></h1>
			<p class="lf-page-tete__intro"><?php esc_html_e( 'La page demandée n’existe pas, ou plus. Une recherche, ou l’un de ces chemins, devrait vous remettre sur la bonne voie.', 'la-fabrique' ); ?></p>
			<?php get_search_form(); ?>
			<ul class="lf-404__liens">
				<li><a class="lf-lien" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Retour à l’accueil', 'la-fabrique' ); ?></a></li>
				<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
					<li><a class="lf-lien" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'La boutique', 'la-fabrique' ); ?></a></li>
				<?php endif; ?>
				<?php if ( la_fabrique_recettes_actives() ) : ?>
					<li><a class="lf-lien" href="<?php echo esc_url( (string) get_post_type_archive_link( 'recette' ) ); ?>"><?php esc_html_e( 'Les recettes', 'la-fabrique' ); ?></a></li>
				<?php endif; ?>
			</ul>
		</div>
		<div class="lf-404__visuel" aria-hidden="true">
			<?php echo wp_kses( la_fabrique_get_svg( 'symbole.svg', 'lf-404__grain' ), la_fabrique_svg_autorise() ); ?>
		</div>
	</div>
</main>

<?php
get_footer();
