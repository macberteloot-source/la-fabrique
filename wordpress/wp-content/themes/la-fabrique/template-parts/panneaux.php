<?php
/**
 * Panneaux modaux (<dialog>) : menu mobile et recherche ; le mini-panier est ajouté par inc/woocommerce.php.
 *
 * <dialog> + showModal() donne gratuitement le piège à focus, la touche Échap, l'arrière-plan
 * inerte et le retour du focus sur le bouton d'ouverture ; theme.js gère aria-expanded et les animations.
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;
?>
<dialog id="lf-menu-mobile" class="lf-panneau lf-panneau--menu" aria-label="<?php esc_attr_e( 'Menu', 'la-fabrique' ); ?>">
	<div class="lf-panneau__tete">
		<?php la_fabrique_logo( 'lf-logo lf-panneau__logo' ); ?>
		<button type="button" class="lf-action" data-lf-ferme>
			<?php la_fabrique_icone( 'fermer' ); ?>
			<span class="screen-reader-text"><?php esc_html_e( 'Fermer le menu', 'la-fabrique' ); ?></span>
		</button>
	</div>
	<nav class="lf-menu-mobile" aria-label="<?php esc_attr_e( 'Menu principal (mobile)', 'la-fabrique' ); ?>">
		<?php
		wp_nav_menu(
			array(
				'theme_location' => 'principal',
				'container'      => false,
				'menu_class'     => 'lf-menu-mobile__liste',
				'depth'          => 1,
				'fallback_cb'    => 'la_fabrique_menu_secours',
			)
		);
		?>
	</nav>
	<div class="lf-menu-mobile__bas">
		<?php get_search_form(); ?>
		<p class="lf-menu-mobile__note"><?php esc_html_e( 'Atelier à Fives, Lille. Torréfaction le mardi.', 'la-fabrique' ); ?></p>
	</div>
</dialog>

<dialog id="lf-recherche" class="lf-panneau lf-panneau--recherche" aria-labelledby="lf-recherche-titre" data-lf-focus="input[type=search]">
	<div class="lf-conteneur">
		<div class="lf-panneau__tete">
			<h2 class="lf-panneau__titre" id="lf-recherche-titre"><?php esc_html_e( 'Rechercher', 'la-fabrique' ); ?></h2>
			<button type="button" class="lf-action" data-lf-ferme>
				<?php la_fabrique_icone( 'fermer' ); ?>
				<span class="screen-reader-text"><?php esc_html_e( 'Fermer la recherche', 'la-fabrique' ); ?></span>
			</button>
		</div>
		<?php get_search_form(); ?>
		<?php
		$la_fabrique_methodes = la_fabrique_methodes();
		if ( $la_fabrique_methodes ) :
			?>
			<div class="lf-recherche__pistes">
				<p class="lf-recherche__label" id="lf-recherche-pistes"><?php esc_html_e( 'Ou parcourir les recettes par méthode', 'la-fabrique' ); ?></p>
				<ul class="lf-puces" aria-labelledby="lf-recherche-pistes">
					<?php foreach ( $la_fabrique_methodes as $la_fabrique_methode ) : ?>
						<li><a class="lf-puce" href="<?php echo esc_url( $la_fabrique_methode['lien'] ); ?>"><?php echo esc_html( $la_fabrique_methode['nom'] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
	</div>
</dialog>

<p id="lf-annonce" class="screen-reader-text" aria-live="polite"></p>

<?php
/**
 * Autres panneaux (mini-panier de WooCommerce).
 */
do_action( 'la_fabrique_panneaux' );
