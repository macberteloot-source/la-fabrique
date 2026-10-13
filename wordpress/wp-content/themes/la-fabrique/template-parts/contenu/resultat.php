<?php
/**
 * Un résultat de recherche, avec son type (café, accessoire, recette, page…).
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;

$la_fabrique_type = get_post_type();
switch ( $la_fabrique_type ) {
	case 'product':
		$la_fabrique_libelle = has_term( 'cafes', 'product_cat' ) ? __( 'Café', 'la-fabrique' ) : __( 'Boutique', 'la-fabrique' );
		break;
	case 'recette':
		$la_fabrique_libelle = __( 'Recette', 'la-fabrique' );
		break;
	case 'page':
		$la_fabrique_libelle = __( 'Page', 'la-fabrique' );
		break;
	case 'post':
		$la_fabrique_libelle = __( 'Journal', 'la-fabrique' );
		break;
	default:
		$la_fabrique_objet   = get_post_type_object( $la_fabrique_type );
		$la_fabrique_libelle = $la_fabrique_objet ? $la_fabrique_objet->labels->singular_name : '';
}
?>
<li <?php post_class( 'lf-resultat' ); ?>>
	<div class="lf-resultat__image">
		<?php
		if ( has_post_thumbnail() ) {
			the_post_thumbnail( 'thumbnail', array( 'alt' => '' ) );
		} else {
			la_fabrique_icone( 'recette' === $la_fabrique_type ? 'tasse' : 'grain' );
		}
		?>
	</div>
	<div class="lf-resultat__texte">
		<p class="lf-resultat__type"><?php echo esc_html( $la_fabrique_libelle ); ?></p>
		<h2 class="lf-resultat__titre"><a class="lf-carte__lien" href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
		<p class="lf-resultat__extrait"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 24, '…' ) ); ?></p>
	</div>
</li>
