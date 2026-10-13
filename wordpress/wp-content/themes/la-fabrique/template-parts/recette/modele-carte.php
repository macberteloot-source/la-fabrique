<?php
/**
 * Modèle de carte recette pour JavaScript (<template>, jamais affiché tel quel).
 *
 * Même balisage que template-parts/recette/carte.php : recettes.js le clone et le remplit
 * avec les données de la route REST, en textContent (aucune insertion de HTML).
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;

if ( wp_script_is( 'la-fabrique-recettes', 'enqueued' ) ) :
	?>
	<template id="lf-modele-carte-recette">
		<article class="lf-carte lf-carte-recette">
			<div class="lf-carte__image"><img src="" alt="" width="900" height="600" loading="lazy" decoding="async"></div>
			<div class="lf-carte__corps">
				<p class="lf-carte-recette__methode" data-champ="methode"></p>
				<h2 class="lf-carte__titre"><a class="lf-carte__lien" href="" data-champ="titre"></a></h2>
				<ul class="lf-carte-recette__infos">
					<li data-champ="temps"><?php la_fabrique_icone( 'temps' ); ?><span class="screen-reader-text"><?php esc_html_e( 'Temps :', 'la-fabrique' ); ?> </span><span data-valeur></span></li>
					<li data-champ="difficulte"><?php la_fabrique_icone( 'difficulte' ); ?><span class="screen-reader-text"><?php esc_html_e( 'Difficulté :', 'la-fabrique' ); ?> </span><span data-valeur></span></li>
					<li data-champ="ratio"><?php la_fabrique_icone( 'ratio' ); ?><span class="screen-reader-text"><?php esc_html_e( 'Ratio café / eau :', 'la-fabrique' ); ?> </span><span data-valeur></span></li>
				</ul>
			</div>
		</article>
	</template>
	<?php
endif;
