=== La Fabrique ===
Contributors: lafabrique
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Tags: e-commerce, blog, custom-logo, custom-menu, editor-style, featured-images, translation-ready

Thème classique sur mesure pour La Fabrique, torréfacteur (fictif) à Fives, Lille.

== Description ==

La Fabrique est un thème PHP « classique » écrit pour une boutique WooCommerce de cafés de
spécialité. Projet de démonstration : la marque est fictive.

Ce que le thème montre :

* la hiérarchie des templates (front-page, page, page-atelier, single, archive, search, 404,
  single-recette, archive-recette, taxonomy-methode, header, footer, template-parts) ;
* un functions.php découpé en modules (dossier inc/) et un theme.json (palette et typographies
  de l'éditeur, polices locales déclarées une seule fois) ;
* WooCommerce personnalisé uniquement par des hooks (inc/woocommerce.php) : aucune copie de
  gabarit WooCommerce dans le thème ;
* l'API REST consommée en JavaScript natif : filtres des recettes sans rechargement et section
  « Recettes avec ce café » sur la fiche produit, avec repli rendu côté serveur ;
* des animations fluides (transform et opacity uniquement) désactivées si l'utilisateur limite
  les animations.

== Plugins associés ==

Les données vivent dans les plugins, la présentation dans le thème :

* fabrique-recettes : type « recette », taxonomie « methode », champs ACF, fonctions
  fabrique_recette_infos() et fabrique_cafe_fiche(), route REST fabrique/v1/recettes ;
* fabrique-boutique : barre « livraison offerte » (placée dans l'en-tête par
  do_action( 'fabrique_livraison_barre', 'entete' )), créneau de retrait, connexion Google,
  fonctions fabrique_horaires_retrait() et fabrique_seuil_livraison_offerte() : le thème
  affiche les horaires de retrait et le seuil de la livraison offerte réglés dans le plugin
  (appels protégés par function_exists(), avec les valeurs par défaut en repli).

Sans ces plugins (ou sans WooCommerce), le thème fonctionne : les sections correspondantes
ne s'affichent pas.

== Installation ==

1. Copier le dossier la-fabrique dans wp-content/themes/ et l'activer.
2. Attribuer les menus « Menu principal » et « Menu du pied de page » (Apparence → Menus).
3. Pour la boutique : WooCommerce, avec les pages Panier et Commande en shortcodes classiques
   ([woocommerce_cart] et [woocommerce_checkout]) ; les personnalisations passent par les hooks
   PHP du tunnel classique.

La page dont le slug est « atelier » utilise page-atelier.php (hiérarchie page-{slug}.php,
sans code supplémentaire).

== Filtres du thème ==

* la_fabrique_horaires_retrait : lignes d'horaires de retrait affichées par le thème
  (par défaut, celles du plugin fabrique-boutique).
* la_fabrique_recettes_par_page : nombre de recettes par page (1 à 12).
* la_fabrique_page_large : mise en page pleine largeur d'une page.

== Accessibilité ==

Lien d'évitement, focus visibles, contrastes vérifiés (la brique ne sert qu'aux grands éléments
et aux boutons), menu mobile, recherche et mini-panier dans des <dialog> (piège à focus, Échap,
retour du focus), annonces aria-live pour le panier et les filtres, textes alternatifs.

== Copyright ==

La Fabrique WordPress Theme, (C) 2026 La Fabrique (marque fictive).
Distribué sous licence GNU GPL v2 ou ultérieure.

Ce thème inclut :

* Instrument Sans et Instrument Serif, (C) 2022 The Instrument Sans / Instrument Serif Project
  Authors, SIL Open Font License 1.1 (assets/fonts/OFL-*.txt).
* Logo, favicons, illustrations et textures : créés pour le projet par le générateur
  wordpress/tools/illustrations (GPL v2 ou ultérieure).

== Changelog ==

= 1.0.0 =
* Première version.
