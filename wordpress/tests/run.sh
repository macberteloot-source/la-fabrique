#!/usr/bin/env bash
# Lance les tests PHP (wp eval-file) puis, avec --e2e, le parcours navigateur (Playwright).
#
#   make test                                   # dans Docker
#   WP="/chemin/wp --path=/site" bash tests/run.sh [--e2e]
#   FABRIQUE_URL=http://localhost:8090 …        # URL du site pour le parcours navigateur
#
# Prérequis : site installé avec bin/setup.sh (réglages WooCommerce et contenus de démonstration).

set -uo pipefail

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
read -r -a WPCMD <<< "${WP:-wp}"
echecs=0

lancer() {
	printf '\n\033[1m▸ %s\033[0m\n' "$*"
	"${WPCMD[@]}" eval-file "$@" || echecs=$((echecs + 1))
}

lancer "$DIR/test-recettes.php"
# Même fichier sans ACF : les fonctions publiques doivent se replier sur get_post_meta().
lancer "$DIR/test-recettes.php" --skip-plugins=advanced-custom-fields
lancer "$DIR/test-livraison.php"
lancer "$DIR/test-creneaux.php"
lancer "$DIR/test-google.php"

if [ "${1:-}" = "--e2e" ]; then
	# Les parcours appellent WP-CLI avec la même commande (variable WP).
	export WP="${WP:-wp}"
	for parcours in parcours_commande parcours_compte; do
		printf '\n\033[1m▸ navigateur : %s\033[0m\n' "$parcours"
		python3 "$DIR/e2e/$parcours.py" || echecs=$((echecs + 1))
	done
fi

if [ "$echecs" -gt 0 ]; then
	printf '\n\033[31m%d suite(s) en échec\033[0m\n' "$echecs"
	exit 1
fi
printf '\n\033[32mToutes les suites passent.\033[0m\n'
