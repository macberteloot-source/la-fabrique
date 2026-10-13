<?php
/**
 * Filtres des recettes par méthode : de vrais liens (pages de taxonomie), améliorés en JavaScript.
 *
 * Arguments : courante (slug de la méthode affichée, '' pour toutes).
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;

$la_fabrique_methodes = la_fabrique_methodes();
if ( ! $la_fabrique_methodes ) {
	return;
}
$la_fabrique_courante = $args['courante'] ?? '';
$la_fabrique_comptes  = wp_count_posts( 'recette' );
?>
<nav class="lf-filtres" aria-label="<?php esc_attr_e( 'Filtrer les recettes par méthode', 'la-fabrique' ); ?>" data-lf-filtres>
	<ul class="lf-puces">
		<li>
			<a class="lf-puce" href="<?php echo esc_url( (string) get_post_type_archive_link( 'recette' ) ); ?>" data-lf-methode=""<?php echo '' === $la_fabrique_courante ? ' aria-current="page"' : ''; ?>>
				<?php esc_html_e( 'Toutes', 'la-fabrique' ); ?>
				<span class="lf-puce__nombre"><?php echo (int) ( $la_fabrique_comptes->publish ?? 0 ); ?></span>
			</a>
		</li>
		<?php foreach ( $la_fabrique_methodes as $la_fabrique_methode ) : ?>
			<li>
				<a class="lf-puce" href="<?php echo esc_url( $la_fabrique_methode['lien'] ); ?>" data-lf-methode="<?php echo esc_attr( $la_fabrique_methode['slug'] ); ?>"<?php echo $la_fabrique_methode['slug'] === $la_fabrique_courante ? ' aria-current="page"' : ''; ?>>
					<?php echo esc_html( $la_fabrique_methode['nom'] ); ?>
					<span class="lf-puce__nombre"><?php echo (int) $la_fabrique_methode['nombre']; ?></span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
