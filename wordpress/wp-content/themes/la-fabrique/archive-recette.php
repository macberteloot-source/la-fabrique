<?php
/**
 * Archive des recettes (/recettes/).
 *
 * Hiérarchie : archive-{type}.php. Le corps est partagé avec taxonomy-methode.php
 * (template-parts/recette/archive.php) : mêmes filtres, mêmes cartes.
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;

get_header();
get_template_part( 'template-parts/recette/archive' );
get_footer();
