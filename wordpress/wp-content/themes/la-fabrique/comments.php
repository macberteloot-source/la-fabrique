<?php
/**
 * Commentaires des articles.
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;

if ( post_password_required() ) {
	return;
}
?>
<section id="commentaires" class="lf-conteneur lf-commentaires" aria-labelledby="lf-commentaires-titre">
	<div class="lf-prose">
		<?php if ( have_comments() ) : ?>
			<h2 id="lf-commentaires-titre" class="lf-commentaires__titre">
				<?php
				$la_fabrique_nombre = (int) get_comments_number();
				/* translators: %d : nombre de commentaires. */
				echo esc_html( sprintf( _n( '%d commentaire', '%d commentaires', $la_fabrique_nombre, 'la-fabrique' ), $la_fabrique_nombre ) );
				?>
			</h2>
			<ol class="lf-commentaires__liste">
				<?php
				wp_list_comments(
					array(
						'style'       => 'ol',
						'short_ping'  => true,
						'avatar_size' => 48,
					)
				);
				?>
			</ol>
			<?php the_comments_navigation(); ?>
		<?php else : ?>
			<h2 id="lf-commentaires-titre" class="screen-reader-text"><?php esc_html_e( 'Commentaires', 'la-fabrique' ); ?></h2>
		<?php endif; ?>

		<?php if ( ! comments_open() && get_comments_number() ) : ?>
			<p class="lf-commentaires__clos"><?php esc_html_e( 'Les commentaires sont fermés.', 'la-fabrique' ); ?></p>
		<?php endif; ?>

		<?php
		comment_form(
			array(
				'title_reply'        => __( 'Laisser un commentaire', 'la-fabrique' ),
				'class_submit'       => 'lf-bouton',
				'title_reply_before' => '<h2 id="reply-title" class="lf-commentaires__titre">',
				'title_reply_after'  => '</h2>',
			)
		);
		?>
	</div>
</section>
