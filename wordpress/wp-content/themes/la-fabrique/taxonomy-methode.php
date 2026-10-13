<?php
/**
 * Recettes d'une méthode (/recettes/methode/<slug>/).
 *
 * Hiérarchie : taxonomy-{taxonomie}.php. Ce sont les « vrais liens » des filtres de l'archive :
 * sans JavaScript (ou en ouvrant le lien dans un nouvel onglet), chaque méthode a sa page complète.
 *
 * @package La_Fabrique
 */

defined( 'ABSPATH' ) || exit;

get_header();
get_template_part( 'template-parts/recette/archive' );
get_footer();
