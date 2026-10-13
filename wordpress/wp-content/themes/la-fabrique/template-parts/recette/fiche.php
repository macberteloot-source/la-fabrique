<?php
/**
 * Fiche technique d'une recette : temps, difficulté, ratio, température, mouture.
 *
 * Données de fabrique_recette_infos() ; une valeur absente n'affiche pas sa case
 * (par exemple la température d'un cold brew, préparé à froid).
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;

$la_fabrique_infos = $args['infos'] ?? array();
$la_fabrique_cases = array();

if ( $la_fabrique_infos['temps'] ) {
	$la_fabrique_cases[] = array( 'temps', __( 'Temps', 'la-fabrique' ), la_fabrique_temps_lisible( $la_fabrique_infos['temps'] ), '' );
}
if ( $la_fabrique_infos['difficulte_libelle'] ) {
	$la_fabrique_cases[] = array( 'difficulte', __( 'Difficulté', 'la-fabrique' ), $la_fabrique_infos['difficulte_libelle'], '' );
}
if ( $la_fabrique_infos['ratio'] ) {
	$la_fabrique_detail = '';
	if ( $la_fabrique_infos['dose_cafe'] && $la_fabrique_infos['volume_eau'] ) {
		/* translators: 1 : grammes de café, 2 : millilitres d'eau. */
		$la_fabrique_detail = sprintf( __( '%1$s g pour %2$s ml', 'la-fabrique' ), number_format_i18n( $la_fabrique_infos['dose_cafe'], floor( $la_fabrique_infos['dose_cafe'] ) === $la_fabrique_infos['dose_cafe'] ? 0 : 1 ), number_format_i18n( $la_fabrique_infos['volume_eau'] ) );
	}
	$la_fabrique_cases[] = array( 'ratio', __( 'Ratio', 'la-fabrique' ), $la_fabrique_infos['ratio'], $la_fabrique_detail );
}
if ( $la_fabrique_infos['temperature'] ) {
	/* translators: %d : température de l'eau en degrés Celsius. */
	$la_fabrique_cases[] = array( 'temperature', __( 'Eau', 'la-fabrique' ), sprintf( __( '%d °C', 'la-fabrique' ), $la_fabrique_infos['temperature'] ), '' );
} elseif ( $la_fabrique_infos['methodes'] && 'cold-brew' === $la_fabrique_infos['methodes'][0]['slug'] ) {
	$la_fabrique_cases[] = array( 'temperature', __( 'Eau', 'la-fabrique' ), __( 'Froide', 'la-fabrique' ), '' );
}
if ( $la_fabrique_infos['mouture_libelle'] ) {
	$la_fabrique_cases[] = array( 'mouture', __( 'Mouture', 'la-fabrique' ), $la_fabrique_infos['mouture_libelle'], '' );
}

if ( ! $la_fabrique_cases ) {
	return;
}
?>
<div class="lf-conteneur">
	<dl class="lf-fiche-recette" style="--lf-cases: <?php echo count( $la_fabrique_cases ); ?>">
		<?php foreach ( $la_fabrique_cases as $la_fabrique_case ) : ?>
			<div class="lf-fiche-recette__case" data-lf-revele>
				<dt><?php la_fabrique_icone( $la_fabrique_case[0] ); ?><?php echo esc_html( $la_fabrique_case[1] ); ?></dt>
				<dd>
					<?php echo esc_html( $la_fabrique_case[2] ); ?>
					<?php if ( $la_fabrique_case[3] ) : ?>
						<span class="lf-fiche-recette__detail"><?php echo esc_html( $la_fabrique_case[3] ); ?></span>
					<?php endif; ?>
				</dd>
			</div>
		<?php endforeach; ?>
	</dl>
</div>
