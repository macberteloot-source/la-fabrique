# Installer La Fabrique

Trois façons de faire, de la plus simple à la plus « production ».

| | Pour qui | Démo complète en 1 commande ? |
|---|---|---|
| **A. Docker** | développeurs, Mac / Linux / Windows avec Docker Desktop | oui (`make setup`) |
| **B. Local (LocalWP)** | Windows ou Mac sans Docker : le plus simple pour une démo sur son ordinateur | oui, avec 8 commandes WP-CLI (+1 facultative) |
| **C. Hébergeur** | mettre le site en ligne | avec SSH : oui ; sans SSH : thème + plugins seulement |

Dans tous les cas, il faut WordPress 6.5+ (testé sur 7.1.3), PHP 8.1+ (testé sur 8.3), WooCommerce (testé sur 10.9.4) et ACF (testé sur 6.8.9).

---

## A. Avec Docker

Prérequis : [Docker Desktop](https://www.docker.com/products/docker-desktop/) (Windows : avec WSL 2). `make` est inclus sur Mac et Linux ; sous Windows, utiliser le terminal WSL (Ubuntu) ou les commandes équivalentes ci-dessous.

```sh
cd wordpress
make up       # = cp .env.example .env  puis  docker compose up -d
make setup    # = docker compose run --rm cli bash /opt/fabrique/bin/setup.sh
```

- Site : http://localhost:8080
- Admin : http://localhost:8080/wp-admin/ (`admin` / `admin123`, valeurs du fichier `.env`)
- E-mails envoyés par le site (commande, mot de passe oublié…) : http://localhost:8025 (Mailpit)

Sans `make` : `cp .env.example .env`, puis `docker compose up -d`, puis `docker compose run --rm cli bash /opt/fabrique/bin/setup.sh`.
Repartir de zéro : `make reset` (efface la base). Relancer seulement la démo : `make seed`.

---

## B. Sur son ordinateur avec Local (sans Docker)

[Local](https://localwp.com/) (gratuit, Windows / Mac) crée un WordPress en quelques clics et fournit WP-CLI.

1. **Créer le site** : « + » → *Create a new site* → nom `la-fabrique` → environnement *Custom* : PHP 8.3, serveur web au choix, MySQL 8 → identifiants admin de votre choix.
2. **Copier le code** : clic droit sur le site → *Show folder* → `app/public/wp-content/`.
   - copier `wordpress/wp-content/themes/la-fabrique` dans `wp-content/themes/` ;
   - copier `wordpress/wp-content/plugins/fabrique-recettes` et `fabrique-boutique` dans `wp-content/plugins/`.
   Le dossier `wordpress/` du dépôt peut rester où il est : on va seulement exécuter ses scripts.
3. **Ouvrir le terminal du site** : clic droit sur le site → *Open site shell*. Puis, en remplaçant `CHEMIN` par le chemin du dossier `wordpress` du dépôt :
   ```sh
   wp plugin install woocommerce advanced-custom-fields --activate
   wp language core install fr_FR --activate
   wp language plugin install woocommerce advanced-custom-fields fr_FR
   wp plugin activate fabrique-recettes fabrique-boutique
   wp theme activate la-fabrique
   wp rewrite structure "/%postname%/"
   wp eval-file CHEMIN/bin/reglages.php
   wp eval-file CHEMIN/bin/seed.php
   wp eval-file CHEMIN/bin/commandes-demo.php   # facultatif : commandes fictives pour le tableau de bord des ventes
   ```
   `reglages.php` configure WooCommerce : euro, France, zone de livraison avec Colissimo 5,90 €, livraison offerte dès 45 € et retrait à l'atelier, paiement à la livraison, stock.
   `seed.php` crée les catégories, les attributs, les 9 produits avec leurs illustrations, les 6 recettes, les pages et les menus. Les deux scripts peuvent être relancés sans créer de doublons.
4. **Ouvrir le site** avec *Open site* et l'admin avec *WP Admin*.

---

## C. Chez un hébergeur (mise en ligne)

### 1. Préparer les zips
Le thème et les deux plugins se téléversent depuis l'administration de WordPress sous forme de zip. Il faut un zip par dossier, avec le dossier à la racine du zip : `theme-la-fabrique.zip`, `plugin-fabrique-recettes.zip` et `plugin-fabrique-boutique.zip`. Ils sont fournis avec ce projet, ou se créent par clic droit → *Compresser* sur chaque dossier (`wordpress/wp-content/themes/la-fabrique`, `…/plugins/fabrique-recettes`, `…/plugins/fabrique-boutique`).

### 2. Installer
1. Installer WordPress chez l'hébergeur (installation en un clic chez la plupart d'entre eux), en **HTTPS**.
2. *Extensions → Ajouter* : installer et activer **WooCommerce** et **Advanced Custom Fields**.
3. *Extensions → Ajouter → Téléverser une extension* : `plugin-fabrique-recettes.zip`, puis `plugin-fabrique-boutique.zip`. Les activer.
4. *Apparence → Thèmes → Ajouter → Téléverser un thème* : `theme-la-fabrique.zip`. L'activer.
5. *Réglages → Permaliens* : « Titre de la publication ».

### 3. Contenus et réglages
- **Avec un accès SSH et WP-CLI** (o2switch, Hostinger, OVH Pro, Infomaniak…) : envoyer le dossier `wordpress/` sur le serveur (SFTP), puis lancer les deux dernières commandes de la méthode B (`wp eval-file …/bin/reglages.php` et `…/bin/seed.php`). Le site est alors identique à la démo.
- **Sans SSH** : régler à la main dans *WooCommerce → Réglages* :
  - **Général** : France, euro ;
  - **Livraison** : une zone « France » avec trois méthodes : *Forfait* 5,90 € (Colissimo), *Livraison gratuite* avec montant minimum 45 €, et *Point de retrait / Retrait local* 0 € ;
  - **Paiements** : « Paiement à la livraison » ;
  - **Produits → Inventaire** : gérer le stock ;
  - **Avancé** : pages Panier et Commande avec les shortcodes `[woocommerce_cart]` et `[woocommerce_checkout]`.
  Créez ensuite quelques produits et recettes. Les illustrations de démonstration sont dans `wordpress/seed/images/`.

### 4. Avant d'ouvrir au public
- Un compte administrateur avec un identifiant et un mot de passe **forts et uniques** : jamais `admin` / `admin123`, qui ne servent qu'en local.
- La double authentification pour les administrateurs (extension « Two Factor » par exemple) et une limite de tentatives de connexion (« Limit Login Attempts Reloaded » ou le pare-feu de l'hébergeur).
- `WP_DEBUG` désactivé, sauvegardes automatiques, mises à jour de WordPress, WooCommerce et ACF.
- L'envoi d'e-mails par un vrai service (extension SMTP de l'hébergeur ou de Brevo, Mailjet…). Le mu-plugin Mailpit ne sert qu'en local : ne pas l'envoyer en ligne.
- **Connexion avec Google** : dans Google Cloud, ajouter l'adresse du site (`https://votre-domaine.fr`) aux *Origines JavaScript autorisées* de l'identifiant client OAuth, puis coller l'identifiant dans *WooCommerce → La Fabrique*.
