<?php
/**
 * Carte d'un article dans une liste (journal, archives).
 *
 * Lien « étiré » : seul le titre est un lien, sa zone cliquable couvre la carte.
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'lf-carte' ); ?> data-lf-revele>
	<?php if ( has_post_thumbnail() ) : ?>
		<div class="lf-carte__image">
			<?php the_post_thumbnail( 'la-fabrique-recette', array( 'sizes' => '(min-width: 960px) 30vw, (min-width: 600px) 45vw, 92vw' ) ); ?>
		</div>
	<?php endif; ?>
	<div class="lf-carte__corps">
		<p class="lf-carte__meta"><time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time></p>
		<h2 class="lf-carte__titre"><a class="lf-carte__lien" href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
		<div class="lf-carte__extrait"><?php the_excerpt(); ?></div>
	</div>
</article>
