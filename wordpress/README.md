# La Fabrique — WordPress / WooCommerce

Boutique de démonstration d'un torréfacteur lillois **fictif** : thème sur mesure, recettes (type de contenu + ACF + route REST), personnalisations WooCommerce par hooks, connexion avec Google. Projet de portfolio, aucune commande réelle.

## Démarrer (Docker)

Prérequis : Docker avec Compose v2, `make`.

```sh
cd wordpress
make up       # crée .env depuis .env.example, démarre db, wordpress et mailpit
make setup    # installe et configure tout (idempotent, relançable)
```

| Service | Adresse |
|---|---|
| Site | http://localhost:8080 |
| Administration | http://localhost:8080/wp-admin/ — `admin` / `admin123` (dev uniquement) |
| E-mails (Mailpit) | http://localhost:8025 |

Images épinglées : `wordpress:7.1.3-php8.3-apache`, `wordpress:cli-2.12.0-php8.3`, `mariadb:11.8.9`, `axllent/mailpit:v1.31.1`. WooCommerce 10.9.4 et ACF 6.8.9 sont téléchargés depuis wordpress.org par `bin/setup.sh` (versions modifiables avec `WOOCOMMERCE_VERSION` / `ACF_VERSION`).

| Commande | Effet |
|---|---|
| `make setup` | WordPress en `fr_FR`, WooCommerce + ACF, thème, plugins, permaliens, réglages WooCommerce, contenus |
| `make seed` | contenus de démonstration seuls (importe aussi les nouvelles illustrations de `seed/images/`) |
| `make commandes` | 24 commandes fictives sur 30 jours pour remplir le tableau de bord des ventes (stock non touché) |
| `make test` / `make e2e` | tests PHP dans le conteneur WP-CLI / parcours navigateur depuis l'hôte |
| `make wp ARGS="…"` | n'importe quelle commande WP-CLI |
| `make lint` | WordPress Coding Standards (`phpcs.xml.dist`) |
| `make logs`, `make down`, `make reset` | journaux, arrêt, remise à zéro (volumes supprimés) |

**Sans Docker**, sur un WordPress existant : `WP="wp --path=/chemin/du/site" WP_URL=http://localhost:8090 bash bin/setup.sh`. Ce qui est déjà fait est ignoré ; les téléchargements impossibles (hors ligne) sont signalés et sautés.

## Organisation

```
bin/setup.sh       installation + configuration (WP-CLI), puis reglages.php et seed.php
bin/reglages.php   réglages WooCommerce : euro, France, comptes, zone « France métropolitaine », paiement
bin/seed.php       catégories, attributs, 9 produits, 6 recettes, pages, menus (retrouvés par sku / slug)
seed/images/       illustrations générées ({sku}.png, recette-{slug}.png), importées si présentes
wp-content/themes/la-fabrique/           présentation (thème classique)
wp-content/plugins/fabrique-recettes/    données : recettes, méthodes, champs ACF, fiche café, REST
wp-content/plugins/fabrique-boutique/    boutique : livraison offerte, créneau de retrait, Google, réglages, tableau de bord des ventes
wp-content/mu-plugins/fabrique-mailpit.php   dev : e-mails envoyés à Mailpit
tests/             tests PHP (wp eval-file) et parcours Playwright
```

Principe : **les données dans les plugins, la présentation dans le thème**. Le thème n'appelle que `fabrique_recette_infos()`, `fabrique_cafe_fiche()`, `do_action( 'fabrique_livraison_barre', 'entete' )`, et, derrière `function_exists()`, `fabrique_horaires_retrait()` et `fabrique_seuil_livraison_offerte()` (horaires et seuil lus dans les réglages, jamais recopiés dans le thème), et stylise les classes `lf-livraison*`, `lf-creneau*`, `lf-google*`. Le détail de chaque plugin est dans son `readme.txt`.

## Choix techniques

- **Tunnel de commande classique.** Panier et Commande utilisent `[woocommerce_cart]` et `[woocommerce_checkout]` : le créneau de retrait et la barre passent par les hooks PHP du tunnel classique (`woocommerce_review_order_after_shipping`, `woocommerce_after_checkout_validation`, `woocommerce_checkout_create_order`, fragments AJAX). Alternative « blocs » : l'API *Additional Checkout Fields* de WooCommerce (champ déclaré en PHP, rendu par le bloc Checkout) et un slot-fill React pour la barre ; le plugin déclare donc son incompatibilité avec les blocs panier / commande.
- **ACF déclaré en PHP** (`acf_add_local_field_group`) : les champs sont versionnés et relus en revue de code. Les noms de champs sont les clés de méta, donc tout reste lisible sans ACF (`get_post_meta`), et `register_post_meta()` nettoie les valeurs.
- **Route REST** `fabrique/v1/recettes` : arguments typés (400 au format WordPress), recettes publiées uniquement, `Cache-Control: public, max-age=300`, cafés liés préchargés en une requête.
- **Livraison offerte** : seuil lu dans la méthode `free_shipping` de la zone du client, même calcul que WooCommerce (remises). Quand elle est atteinte, Colissimo payant est masqué pour rester cohérent avec la barre.
- **HPOS** : le créneau est enregistré avec `WC_Order::update_meta_data()`, compatibilité déclarée.
- **Connexion Google** sans bibliothèque : jeton vérifié côté serveur (RS256 imposé, clés JWKS de Google en cache selon `Cache-Control`, `openssl_verify`, `iss`, `aud`, `exp`/`iat` ± 5 min, `email_verified`), nonce à usage unique lié à un cookie HttpOnly SameSite=Lax, en-tête `Origin` vérifié, limitation de débit par IP, **aucune liaison automatique par e-mail** (risque de pré-détournement de compte : WooCommerce crée des comptes sans vérifier l'adresse). Configuration : `FABRIQUE_GOOGLE_CLIENT_ID` dans `.env`, origine autorisée `http://localhost:8080`.

## Tests

```sh
make test                                              # dans Docker
WP="wp --path=/chemin/du/site" bash tests/run.sh --e2e  # hors Docker, avec le parcours navigateur
```

- `test-recettes.php` (lancé avec puis **sans ACF**) : fonctions publiques, filtres et erreurs de la route REST, contenus non publics invisibles.
- `test-livraison.php` : seuil de la zone ou de repli, remises, textes, balisage, fragments, tarifs masqués.
- `test-creneaux.php` : calcul (délai, changement d'heure), validation serveur, enregistrement HPOS, affichage (récapitulatif, e-mail, administration).
- `test-google.php` : jetons signés avec des clés RSA générées localement (`alg` none/HS256, `kid`, signature, `aud`, `iss`, `exp`, `iat`, `nonce`, `email_verified`), cache JWKS simulé, routes REST (création, refus par e-mail, association, rejeu, Origin, débit).
- `e2e/parcours_commande.py` (Playwright) : ajout au panier AJAX → barre à jour → « Retrait à l'atelier » → commande refusée sans créneau → commande enregistrée avec le créneau.
- `e2e/parcours_compte.py` (Playwright) : réglages WooCommerce → La Fabrique (erreur de saisie, enregistrement), zone Google sur Mon compte, onglet « Connexions » et dissociation.

Les tests utilisent un petit assert maison (`tests/lib.php`) : PHPUnit et la suite de tests de WordPress ne sont pas nécessaires.

## Usage de l'IA

Le code a été écrit avec l'aide d'un assistant IA (Claude), à partir d'un cahier des charges rédigé en amont. Ce qui a été vérifié plutôt que cru sur parole : chaque règle de sécurité a son test (jetons forgés, rejeu, Origin, débit), les tests et `phpcs` passent, le parcours de commande est rejoué dans un vrai navigateur, et les versions d'images Docker ont été contrôlées sur Docker Hub. Limites assumées : pas de vrai jeton Google en test automatique (clés injectées), tunnel « blocs » non couvert.
