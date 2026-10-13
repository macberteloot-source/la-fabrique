<?php
/**
 * Carte recette (accueil, archive, fiche produit, recherche).
 *
 * Reçoit des données au format de la route REST fabrique/v1/recettes : le même balisage est
 * reproduit en JavaScript (recettes.js) à partir du modèle <template> de modele-carte.php.
 *
 * Arguments : recette (array), niveau (2|3), variante (''|'une'|'ligne').
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;

$la_fabrique_r       = $args['recette'] ?? array();
$la_fabrique_niveau  = 2 === (int) ( $args['niveau'] ?? 3 ) ? 'h2' : 'h3';
$la_fabrique_classes = 'lf-carte lf-carte-recette' . ( empty( $args['variante'] ) ? '' : ' lf-carte-recette--' . sanitize_html_class( $args['variante'] ) );
if ( empty( $la_fabrique_r['titre'] ) ) {
	return;
}
$la_fabrique_sizes = 'une' === ( $args['variante'] ?? '' ) ? '(min-width: 960px) 55vw, 92vw' : '(min-width: 960px) 30vw, (min-width: 600px) 45vw, 92vw';
?>
<article class="<?php echo esc_attr( $la_fabrique_classes ); ?>" data-lf-revele>
	<div class="lf-carte__image">
		<?php
		if ( ! empty( $la_fabrique_r['image_id'] ) ) {
			echo wp_get_attachment_image(
				$la_fabrique_r['image_id'],
				la_fabrique_taille_image_recette(),
				false,
				array(
					'sizes'   => $la_fabrique_sizes,
					'loading' => 'lazy',
					'alt'     => '',
				)
			);
		} elseif ( ! empty( $la_fabrique_r['image']['url'] ) ) {
			printf(
				'<img src="%1$s" width="%2$d" height="%3$d" alt="" loading="lazy" decoding="async">',
				esc_url( $la_fabrique_r['image']['url'] ),
				(int) $la_fabrique_r['image']['largeur'],
				(int) $la_fabrique_r['image']['hauteur']
			);
		}
		?>
	</div>
	<div class="lf-carte__corps">
		<?php if ( ! empty( $la_fabrique_r['methode']['nom'] ) ) : ?>
			<p class="lf-carte-recette__methode"><?php echo esc_html( $la_fabrique_r['methode']['nom'] ); ?></p>
		<?php endif; ?>
		<<?php echo esc_attr( $la_fabrique_niveau ); ?> class="lf-carte__titre">
			<a class="lf-carte__lien" href="<?php echo esc_url( $la_fabrique_r['lien'] ); ?>"><?php echo esc_html( $la_fabrique_r['titre'] ); ?></a>
		</<?php echo esc_attr( $la_fabrique_niveau ); ?>>
		<?php if ( 'une' === ( $args['variante'] ?? '' ) && ! empty( $la_fabrique_r['extrait'] ) ) : ?>
			<p class="lf-carte__extrait"><?php echo esc_html( $la_fabrique_r['extrait'] ); ?></p>
		<?php endif; ?>
		<ul class="lf-carte-recette__infos">
			<?php if ( ! empty( $la_fabrique_r['temps'] ) ) : ?>
				<li><?php la_fabrique_icone( 'temps' ); ?><span class="screen-reader-text"><?php esc_html_e( 'Temps :', 'la-fabrique' ); ?> </span><?php echo esc_html( la_fabrique_temps_lisible( $la_fabrique_r['temps'] ) ); ?></li>
			<?php endif; ?>
			<?php if ( ! empty( $la_fabrique_r['difficulte_libelle'] ) ) : ?>
				<li><?php la_fabrique_icone( 'difficulte' ); ?><span class="screen-reader-text"><?php esc_html_e( 'Difficulté :', 'la-fabrique' ); ?> </span><?php echo esc_html( $la_fabrique_r['difficulte_libelle'] ); ?></li>
			<?php endif; ?>
			<?php if ( ! empty( $la_fabrique_r['ratio'] ) ) : ?>
				<li><?php la_fabrique_icone( 'ratio' ); ?><span class="screen-reader-text"><?php esc_html_e( 'Ratio café / eau :', 'la-fabrique' ); ?> </span><?php echo esc_html( $la_fabrique_r['ratio'] ); ?></li>
			<?php endif; ?>
		</ul>
	</div>
</article>
