<?php
/**
 * Pied de page : la silhouette des sheds, la marque, les menus, la mention « marque fictive »,
 * puis les panneaux (<dialog>) du menu mobile, de la recherche et du mini-panier.
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;
?>

<footer class="lf-pied">
	<div class="lf-toits lf-pied__toits" aria-hidden="true"></div>
	<div class="lf-conteneur">
		<div class="lf-pied__haut">
			<p class="lf-pied__marque" aria-hidden="true"><?php bloginfo( 'name' ); ?></p>
			<p class="lf-pied__signature"><?php esc_html_e( 'Torréfacteur à Fives, Lille. Petits lots torréfiés le mardi, recettes partagées le reste de la semaine.', 'la-fabrique' ); ?></p>
		</div>

		<div class="lf-pied__colonnes">
			<nav class="lf-pied__bloc" aria-labelledby="lf-pied-boutique">
				<h2 class="lf-pied__titre" id="lf-pied-boutique"><?php esc_html_e( 'Parcourir', 'la-fabrique' ); ?></h2>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'pied-de-page',
						'container'      => false,
						'menu_class'     => 'lf-pied__liste',
						'depth'          => 1,
						'fallback_cb'    => 'la_fabrique_menu_secours',
					)
				);
				?>
			</nav>

			<div class="lf-pied__bloc">
				<h2 class="lf-pied__titre"><?php esc_html_e( 'L’atelier', 'la-fabrique' ); ?></h2>
				<p><?php esc_html_e( 'Atelier à Fives, Lille', 'la-fabrique' ); ?></p>
				<p class="lf-pied__petit"><?php esc_html_e( 'Retrait des commandes :', 'la-fabrique' ); ?></p>
				<ul class="lf-pied__liste lf-pied__liste--simple">
					<?php foreach ( la_fabrique_horaires_retrait() as $la_fabrique_horaire ) : ?>
						<li><?php echo esc_html( $la_fabrique_horaire ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>

			<div class="lf-pied__bloc">
				<h2 class="lf-pied__titre"><?php esc_html_e( 'Nous écrire', 'la-fabrique' ); ?></h2>
				<p><a class="lf-lien lf-lien--clair" href="mailto:bonjour@la-fabrique.test">bonjour@la-fabrique.test</a></p>
				<p class="lf-pied__petit"><?php esc_html_e( 'On répond entre deux torréfactions, en général dans la journée.', 'la-fabrique' ); ?></p>
			</div>
		</div>

		<div class="lf-pied__bas">
			<p>
				<?php
				/* translators: %s : année. */
				echo esc_html( sprintf( __( '© %s La Fabrique', 'la-fabrique' ), wp_date( 'Y' ) ) );
				?>
			</p>
			<?php la_fabrique_marque_fictive( 'lf-pied__fictive' ); ?>
			<a class="lf-pied__retour" href="#haut">
				<?php esc_html_e( 'Haut de page', 'la-fabrique' ); ?>
				<?php la_fabrique_icone( 'haut' ); ?>
			</a>
		</div>
	</div>
</footer>

<?php get_template_part( 'template-parts/panneaux' ); ?>

<?php wp_footer(); ?>
</body>
</html>
