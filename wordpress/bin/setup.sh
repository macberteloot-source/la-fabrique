#!/usr/bin/env bash
# Installation et configuration de La Fabrique (idempotent : on peut le relancer sans risque).
#
# Dans Docker (conteneur « cli ») :   make setup
# Hors Docker, sur un site existant : WP="/chemin/vers/wp --path=/chemin/du/site" bash bin/setup.sh
#
# Étapes : attendre la base, installer WordPress en français, installer/activer WooCommerce et ACF,
# activer le thème et nos plugins, permaliens, réglages WooCommerce (bin/reglages.php),
# puis contenus de démonstration (bin/seed.php : catalogue, recettes, pages, menus).
# Ce qui est déjà fait est ignoré ; ce qui est impossible hors ligne (téléchargements) est signalé puis sauté.

set -euo pipefail

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Commande WP-CLI (peut contenir des options, ex. « php wp-cli.phar --path=… »).
read -r -a WPCMD <<< "${WP:-wp}"
w() { "${WPCMD[@]}" "$@"; }

WP_URL="${WP_URL:-http://localhost:8080}"
WP_TITLE="${WP_TITLE:-La Fabrique}"
WP_ADMIN_USER="${WP_ADMIN_USER:-admin}"
WP_ADMIN_PASSWORD="${WP_ADMIN_PASSWORD:-admin123}"
WP_ADMIN_EMAIL="${WP_ADMIN_EMAIL:-bonjour@la-fabrique.test}"
WP_LOCALE="${WP_LOCALE:-fr_FR}"
# Versions épinglées (téléchargées depuis wordpress.org).
WOOCOMMERCE_VERSION="${WOOCOMMERCE_VERSION:-10.9.4}"
ACF_VERSION="${ACF_VERSION:-6.8.9}"

etape() { printf '\n\033[1m▸ %s\033[0m\n' "$*"; }
info()  { printf '  %s\n' "$*"; }
alerte() { printf '  \033[33m! %s\033[0m\n' "$*"; }

# 1. Base de données et fichiers WordPress -----------------------------------------------------
etape "Attente des fichiers WordPress et de la base"
# Dans Docker, le conteneur « wordpress » copie WordPress dans le volume au premier démarrage.
for i in $(seq 1 60); do
	if w core version >/dev/null 2>&1 && [ -n "$(w config path 2>/dev/null)" ]; then
		break
	fi
	if [ "$i" -eq 60 ]; then
		echo "WordPress introuvable (WP-CLI : ${WPCMD[*]}). Le conteneur « wordpress » est-il démarré ?" >&2
		exit 1
	fi
	sleep 2
done
base_ok=0
for i in $(seq 1 30); do
	if w db check --quiet >/dev/null 2>&1 || w db query 'SELECT 1' >/dev/null 2>&1; then
		base_ok=1
		break
	fi
	sleep 2
done
if [ "$base_ok" -eq 1 ]; then
	info "fichiers et base disponibles"
else
	# Le client MySQL peut manquer (ou refuser la connexion) alors que PHP se connecte : on tente quand même.
	alerte "la base ne répond pas au client MySQL : on poursuit, WordPress dira si la connexion échoue"
fi

# 2. Installation de WordPress en français ------------------------------------------------------
etape "WordPress"
if w core is-installed >/dev/null 2>&1; then
	info "déjà installé ($(w core version))"
else
	w core install --url="$WP_URL" --title="$WP_TITLE" --admin_user="$WP_ADMIN_USER" \
		--admin_password="$WP_ADMIN_PASSWORD" --admin_email="$WP_ADMIN_EMAIL" --locale="$WP_LOCALE" --skip-email
fi

if w language core is-installed "$WP_LOCALE" >/dev/null 2>&1 || w language core install "$WP_LOCALE" >/dev/null 2>&1; then
	w site switch-language "$WP_LOCALE" >/dev/null 2>&1 || true
	info "langue : $WP_LOCALE"
else
	alerte "traduction $WP_LOCALE indisponible (hors ligne ?) : l'administration reste en anglais"
fi

w option update blogname "$WP_TITLE" >/dev/null
w option update blogdescription "Torréfacteur · Lille" >/dev/null
w option update timezone_string "Europe/Paris" >/dev/null
w option update date_format "j F Y" >/dev/null
w option update time_format 'G \h i' >/dev/null
w option update start_of_week 1 >/dev/null
w option update default_comment_status closed >/dev/null
w option update default_ping_status closed >/dev/null
info "titre, fuseau Europe/Paris, formats de date français"

# 3. Extensions ---------------------------------------------------------------------------------
# installe_plugin <slug> <version> : version épinglée, sinon dernière version, sinon on continue.
installe_plugin() {
	local slug="$1" version="$2"
	if w plugin is-installed "$slug" >/dev/null 2>&1; then
		info "$slug déjà installé"
	elif w plugin install "$slug" --version="$version" >/dev/null 2>&1; then
		info "$slug $version installé"
	elif w plugin install "$slug" >/dev/null 2>&1; then
		alerte "$slug $version introuvable : dernière version installée"
	else
		alerte "$slug : téléchargement impossible (hors ligne ?)"
		return 1
	fi
}

etape "Extensions"
installe_plugin woocommerce "$WOOCOMMERCE_VERSION" || { echo "WooCommerce est indispensable." >&2; exit 1; }
installe_plugin advanced-custom-fields "$ACF_VERSION" || alerte "sans ACF, les champs restent lisibles mais sans interface de saisie"

for plugin in woocommerce advanced-custom-fields fabrique-recettes fabrique-boutique; do
	if w plugin is-installed "$plugin" >/dev/null 2>&1; then
		w plugin activate "$plugin" >/dev/null 2>&1 && info "$plugin actif"
	fi
done

if w language plugin install woocommerce advanced-custom-fields "$WP_LOCALE" >/dev/null 2>&1; then
	info "traductions des extensions : $WP_LOCALE"
else
	alerte "traductions des extensions indisponibles (hors ligne ?)"
fi

# 4. Thème --------------------------------------------------------------------------------------
etape "Thème"
if w theme is-installed la-fabrique >/dev/null 2>&1 && w theme activate la-fabrique >/dev/null 2>&1; then
	info "la-fabrique actif"
else
	alerte "thème la-fabrique absent ou incomplet : le thème actuel est conservé"
fi

# 5. Permaliens ---------------------------------------------------------------------------------
etape "Permaliens"
w rewrite structure '/%postname%/' >/dev/null
info "/%postname%/"

# 6. Réglages WooCommerce puis contenus -------------------------------------------------------
etape "WooCommerce"
w eval-file "$DIR/reglages.php"

etape "Contenus"
w eval-file "$DIR/seed.php"

w rewrite flush >/dev/null
w cache flush >/dev/null 2>&1 || true

etape "Prêt"
info "Site : $(w option get home)"
info "Administration : $(w option get siteurl)/wp-admin/ ($WP_ADMIN_USER)"
