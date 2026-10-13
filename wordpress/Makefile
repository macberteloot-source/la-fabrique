# La Fabrique — raccourcis pour l'environnement Docker.
#
#   make up | setup | seed | test | e2e | down | reset | logs | lint | config
#   make wp ARGS="plugin list"

COMPOSE ?= docker compose
CLI     := $(COMPOSE) run --rm cli
CLI_T   := $(COMPOSE) run --rm -T cli
PHPCS   ?= phpcs
WP_PORT ?= $(shell sed -n 's/^WP_PORT=//p' .env 2>/dev/null || echo 8080)

.PHONY: up setup seed commandes test e2e down reset logs wp lint config

## Démarre les conteneurs (crée .env depuis .env.example s'il manque).
up: .env
	$(COMPOSE) up -d

.env:
	cp .env.example .env

## Installe et configure tout (idempotent) : WordPress fr_FR, WooCommerce, ACF, thème, plugins, contenus.
setup: up
	$(CLI) bash /opt/fabrique/bin/setup.sh

## Recrée ou met à jour les contenus de démonstration (et importe les nouvelles illustrations).
seed:
	$(CLI) wp eval-file /opt/fabrique/bin/seed.php

## Commandes fictives des 30 derniers jours (remplit le tableau de bord des ventes).
commandes:
	$(CLI) wp eval-file /opt/fabrique/bin/commandes-demo.php

## Tests PHP (wp eval-file) dans le conteneur WP-CLI.
test:
	$(CLI) bash /opt/fabrique/tests/run.sh

## Parcours navigateur depuis la machine hôte (Playwright pour Python + Chromium requis).
e2e:
	FABRIQUE_URL=http://localhost:$(WP_PORT) WP="$(CLI_T) wp" python3 tests/e2e/parcours_commande.py
	FABRIQUE_URL=http://localhost:$(WP_PORT) WP="$(CLI_T) wp" python3 tests/e2e/parcours_compte.py

## Arrête les conteneurs (les données restent dans les volumes).
down:
	$(COMPOSE) down

## Repart de zéro : supprime les volumes (base et WordPress) puis réinstalle.
reset:
	$(COMPOSE) down -v
	$(MAKE) setup

logs:
	$(COMPOSE) logs -f --tail=100

## Commande WP-CLI quelconque : make wp ARGS="option get home".
wp:
	$(CLI) wp $(ARGS)

## WordPress Coding Standards (phpcs + WPCS 3 installés sur la machine, ou PHPCS=vendor/bin/phpcs).
lint:
	$(PHPCS) --standard=phpcs.xml.dist

## Vérifie la syntaxe de docker-compose.yml.
config:
	$(COMPOSE) config --quiet && echo "docker-compose.yml valide"
