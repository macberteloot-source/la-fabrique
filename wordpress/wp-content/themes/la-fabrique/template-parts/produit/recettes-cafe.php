<?php
/**
 * Fiche produit : « Recettes avec ce café ».
 *
 * Rendu en deux temps :
 * 1. côté serveur, une liste simple de liens (repli sans JavaScript, et ce que voient les robots),
 *    obtenue par la route REST du plugin appelée en interne (rest_do_request, sans requête HTTP) ;
 * 2. côté navigateur, recettes.js appelle GET /wp-json/fabrique/v1/recettes?cafe=ID quand la section
 *    approche de l'écran et remplace la liste par des cartes illustrées (images chargées à la demande).
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;

$la_fabrique_produit = $args['produit'] ?? null;
if ( ! $la_fabrique_produit instanceof WC_Product ) {
	return;
}

$la_fabrique_donnees = la_fabrique_rest_recettes(
	array(
		'cafe'     => $la_fabrique_produit->get_id(),
		'par_page' => 3,
	)
);
if ( ! $la_fabrique_donnees || empty( $la_fabrique_donnees['recettes'] ) ) {
	return;
}
$la_fabrique_nom = la_fabrique_wc_nom( $la_fabrique_produit->get_name() );
?>
<section class="lf-recettes-cafe" aria-labelledby="lf-recettes-cafe-titre" data-lf-recettes-cafe="<?php echo (int) $la_fabrique_produit->get_id(); ?>">
	<div class="lf-section__tete">
		<h2 class="lf-section__titre" id="lf-recettes-cafe-titre"><?php esc_html_e( 'Recettes avec ce café', 'la-fabrique' ); ?></h2>
		<p class="lf-section__intro">
			<?php
			/* translators: %s : nom du café. */
			echo esc_html( sprintf( __( 'Ce que nous préparons avec %s au comptoir, pas à pas.', 'la-fabrique' ), $la_fabrique_nom['nom'] ) );
			?>
		</p>
	</div>
	<div class="lf-recettes-cafe__contenu" data-lf-recettes-zone>
		<ul class="lf-recettes-cafe__repli">
			<?php foreach ( $la_fabrique_donnees['recettes'] as $la_fabrique_recette ) : ?>
				<li>
					<a class="lf-lien" href="<?php echo esc_url( $la_fabrique_recette['lien'] ); ?>"><?php echo esc_html( $la_fabrique_recette['titre'] ); ?></a>
					<span class="lf-recettes-cafe__infos">
						<?php
						$la_fabrique_infos = array_filter(
							array(
								$la_fabrique_recette['methode']['nom'] ?? '',
								la_fabrique_temps_lisible( $la_fabrique_recette['temps'] ?? null ),
								$la_fabrique_recette['ratio'] ?? '',
							)
						);
						echo esc_html( implode( ' · ', $la_fabrique_infos ) );
						?>
					</span>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
<?php
get_template_part( 'template-parts/recette/modele-carte' );
