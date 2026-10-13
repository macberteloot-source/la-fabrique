<?php
/**
 * En-tête du site : <head>, lien d'évitement, barre « livraison offerte », navigation.
 *
 * Les panneaux (menu mobile, recherche, mini-panier) sont des <dialog> placés dans footer.php.
 * Sans JavaScript, le menu reste affiché en clair et le panier est un simple lien.
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?> class="no-js">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="lf-evitement" href="#contenu"><?php esc_html_e( 'Aller au contenu', 'la-fabrique' ); ?></a>

<?php if ( has_action( 'fabrique_livraison_barre' ) ) : ?>
	<div class="lf-annonce">
		<div class="lf-conteneur lf-annonce__interieur">
			<?php
			// Barre « livraison offerte » rendue par le plugin fabrique-boutique (contrat du cahier des charges).
			do_action( 'fabrique_livraison_barre', 'entete' );
			?>
		</div>
	</div>
<?php endif; ?>

<header class="lf-entete" id="haut" data-lf-entete>
	<div class="lf-conteneur lf-entete__barre">
		<?php la_fabrique_logo( 'lf-logo lf-entete__logo' ); ?>

		<nav class="lf-nav" aria-label="<?php esc_attr_e( 'Menu principal', 'la-fabrique' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'principal',
					'container'      => false,
					'menu_class'     => 'lf-nav__liste',
					'depth'          => 1,
					'fallback_cb'    => 'la_fabrique_menu_secours',
				)
			);
			?>
		</nav>

		<div class="lf-entete__actions">
			<button type="button" class="lf-action lf-action--recherche" data-lf-dialogue="lf-recherche" aria-haspopup="dialog" aria-expanded="false" aria-controls="lf-recherche">
				<?php la_fabrique_icone( 'recherche' ); ?>
				<span class="screen-reader-text"><?php esc_html_e( 'Rechercher', 'la-fabrique' ); ?></span>
			</button>

			<?php
			/**
			 * Actions de la boutique (compte, panier) : ajoutées par inc/woocommerce.php.
			 */
			do_action( 'la_fabrique_actions_entete' );
			?>

			<button type="button" class="lf-action lf-burger" data-lf-dialogue="lf-menu-mobile" aria-haspopup="dialog" aria-expanded="false" aria-controls="lf-menu-mobile">
				<span class="lf-burger__traits" aria-hidden="true"></span>
				<span class="lf-burger__texte"><?php esc_html_e( 'Menu', 'la-fabrique' ); ?></span>
			</button>
		</div>
	</div>
</header>
