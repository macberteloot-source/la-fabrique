#!/usr/bin/env python3
"""Parcours navigateur : page de réglages « WooCommerce → La Fabrique » (enregistrement, erreur de saisie)
et onglet « Connexions » de Mon compte (état associé, dissociation protégée par nonce).

WP-CLI est requis pour préparer les données (client de test, identifiant Google factice) :

    FABRIQUE_URL=http://localhost:8080 WP="docker compose run --rm -T cli wp" python3 tests/e2e/parcours_compte.py

Le bouton Google lui-même n'est pas cliqué (il faudrait un vrai compte Google) : la vérification du jeton
est couverte par tests/test-google.php.
"""

import json
import os
import re
import secrets
import shlex
import subprocess
import sys

from playwright.sync_api import TimeoutError as DelaiDepasse
from playwright.sync_api import expect, sync_playwright

URL = os.environ.get("FABRIQUE_URL", "http://localhost:8080").rstrip("/")
WP = os.environ.get("WP", "wp")
ADMIN = os.environ.get("FABRIQUE_ADMIN", "admin")
ADMIN_MDP = os.environ.get("FABRIQUE_ADMIN_MDP", "admin123")
DELAI = 30_000


def wp(*arguments: str) -> str:
    sortie = subprocess.run(shlex.split(WP) + list(arguments), capture_output=True, text=True, check=False)
    if sortie.returncode != 0:
        raise RuntimeError(f"wp {' '.join(arguments)} : {sortie.stderr.strip()[-300:]}")
    return sortie.stdout.strip()


def etape(message: str) -> None:
    print(f"  ✓ {message}", flush=True)


def connexion_admin(page) -> None:
    page.goto(f"{URL}/wp-login.php", wait_until="domcontentloaded")
    page.fill("#user_login", ADMIN)
    page.fill("#user_pass", ADMIN_MDP)
    page.click("#wp-submit")
    page.wait_for_url(re.compile(r"wp-admin"), timeout=DELAI)


def reglages(page) -> None:
    page.goto(f"{URL}/wp-admin/admin.php?page=fabrique-boutique", wait_until="domcontentloaded")
    expect(page.locator("h1")).to_have_text("La Fabrique — réglages")
    horaires = page.locator("#fabrique_horaires")
    avant = horaires.input_value()

    # Saisie invalide : refusée avec un message, valeur conservée.
    horaires.fill("lundi 25h-26h")
    page.click("#submit")
    expect(page.locator(".notice-error, .error").filter(has_text="Horaires non enregistrés").first).to_be_visible()
    assert page.locator("#fabrique_horaires").input_value() == avant, "horaires invalides enregistrés"
    etape("réglages : horaires invalides refusés avec un message, valeur conservée")

    # Saisie valide : normalisée et enregistrée.
    page.locator("#fabrique_horaires").fill("Samedi 9h-11h\nmercredi 17h-19h")
    page.locator("#fabrique_seuil").fill("50")
    page.click("#submit")
    expect(page.locator(".notice-success, .updated").first).to_be_visible()
    enregistre = json.loads(wp("option", "get", "fabrique_boutique_reglages", "--format=json"))
    assert enregistre["horaires"] == "mercredi 17:00-19:00\nsamedi 09:00-11:00", enregistre["horaires"]
    assert float(enregistre["seuil"]) == 50.0, enregistre["seuil"]
    etape("réglages : horaires normalisés (« mercredi 17:00-19:00 ») et seuil enregistrés via la Settings API")


def connexions(page, identifiant: str, mot_de_passe: str) -> None:
    page.goto(f"{URL}/mon-compte/", wait_until="domcontentloaded")
    expect(page.locator('[data-lf-google="connexion"]')).to_have_count(1)
    etape("Mon compte (visiteur) : zone « Continuer avec Google » sous le formulaire de connexion")

    page.fill("#username", identifiant)
    page.fill("#password", mot_de_passe)
    page.click('button[name="login"]')
    page.wait_for_load_state("domcontentloaded")

    lien = page.locator(".woocommerce-MyAccount-navigation a", has_text="Connexions")
    expect(lien).to_have_count(1)
    lien.click()
    expect(page.locator(".lf-google-connexions__statut.is-associe")).to_be_visible()
    etape("onglet « Connexions » : compte Google associé affiché")

    page.click('button[name="fabrique_google_dissocier"]')
    expect(page.locator(".woocommerce-message", has_text="dissocié")).to_be_visible()
    expect(page.locator('[data-lf-google="association"]')).to_have_count(1)
    utilisateur = wp("user", "get", identifiant, "--field=ID")
    restant = wp("eval", f"echo get_user_meta( {int(utilisateur)}, 'fabrique_google_sub', true );")
    assert restant == "", f"méta non supprimée : {restant!r}"
    etape("dissociation (formulaire + nonce) : méta supprimée, bouton d'association proposé")


def main() -> int:
    print(f"Parcours réglages et connexions sur {URL}")
    reglages_avant = wp("option", "get", "fabrique_boutique_reglages", "--format=json") if wp(
        "option", "list", "--search=fabrique_boutique_reglages", "--field=option_name"
    ) else ""
    identifiant = "client-e2e-" + secrets.token_hex(3)
    mot_de_passe = secrets.token_urlsafe(12)
    reglages_test = json.loads(reglages_avant) if reglages_avant else {}
    if not reglages_test.get("google_client_id"):
        reglages_test["google_client_id"] = "e2e-test.apps.googleusercontent.com"
    wp("option", "update", "fabrique_boutique_reglages", json.dumps(reglages_test), "--format=json")
    utilisateur = wp("user", "create", identifiant, f"{identifiant}@example.com", "--role=customer", f"--user_pass={mot_de_passe}", "--porcelain")
    wp("user", "meta", "update", utilisateur, "fabrique_google_sub", "110000000000000000042")

    code = 0
    with sync_playwright() as p:
        navigateur = p.chromium.launch()
        try:
            admin = navigateur.new_context(locale="fr-FR").new_page()
            admin.set_default_timeout(DELAI)
            connexion_admin(admin)
            reglages(admin)

            client = navigateur.new_context(locale="fr-FR").new_page()
            client.set_default_timeout(DELAI)
            connexions(client, identifiant, mot_de_passe)
            print("Parcours réussi.")
        except (AssertionError, DelaiDepasse, RuntimeError) as erreur:
            print(f"  ✗ {type(erreur).__name__} : {erreur}")
            code = 1
        finally:
            navigateur.close()
            wp("user", "delete", utilisateur, "--yes")
            if reglages_avant:
                wp("option", "update", "fabrique_boutique_reglages", reglages_avant, "--format=json")
            else:
                wp("option", "delete", "fabrique_boutique_reglages")
    return code


if __name__ == "__main__":
    sys.exit(main())
