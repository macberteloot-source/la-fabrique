#!/usr/bin/env python3
"""Parcours navigateur : ajout au panier → barre « livraison offerte » mise à jour →
commande avec « Retrait à l'atelier » et créneau obligatoire → commande enregistrée avec le créneau.

Prérequis : site installé (bin/setup.sh), Playwright pour Python et Chromium.

    FABRIQUE_URL=http://localhost:8080 python3 tests/e2e/parcours_commande.py
    # option : WP="wp --path=…" pour vérifier aussi la méta de la commande en base ;
    #          FABRIQUE_CAPTURES=/tmp/captures pour enregistrer des captures d'écran.

Code de sortie 0 si tout passe, 1 sinon.
"""

import os
import re
import shlex
import subprocess
import sys

from playwright.sync_api import TimeoutError as DelaiDepasse
from playwright.sync_api import expect, sync_playwright

URL = os.environ.get("FABRIQUE_URL", "http://localhost:8080").rstrip("/")
WP = os.environ.get("WP", "")
CAPTURES = os.environ.get("FABRIQUE_CAPTURES", "")
DELAI = 30_000  # ms : le serveur de test peut être lent (un seul processus PHP).

BARRE = "[data-lf-livraison]"
TEXTE_BARRE = BARRE + " .lf-livraison__texte"


def normaliser(texte: str) -> str:
    """Espaces insécables → espaces simples, pour comparer « 13 € » quel que soit le format."""
    return re.sub(r"[   ]+", " ", texte or "").strip()


def etape(message: str) -> None:
    print(f"  ✓ {message}", flush=True)


def capture(page, nom: str) -> None:
    if CAPTURES:
        os.makedirs(CAPTURES, exist_ok=True)
        page.screenshot(path=os.path.join(CAPTURES, f"{nom}.png"), full_page=True)


def attendre_texte_barre(page, motif: str, selecteur: str = TEXTE_BARRE) -> str:
    """Attend qu'une barre de la page affiche un texte correspondant au motif (espaces normalisés)."""
    page.wait_for_function(
        """([sel, motif]) => Array.from(document.querySelectorAll(sel)).some(
            (el) => new RegExp(motif).test(el.textContent.replace(/[\\u00a0\\u202f]/g, ' ')))""",
        arg=[selecteur, motif],
        timeout=DELAI,
    )
    return normaliser(page.locator(selecteur).first.text_content())


def attendre_toutes_les_barres(page, motif: str) -> int:
    """Attend que toutes les barres de la page (en-tête du thème, panier…) affichent le texte attendu."""
    page.wait_for_function(
        """(motif) => { const barres = Array.from(document.querySelectorAll('[data-lf-livraison] .lf-livraison__texte'));
            return barres.length > 0 && barres.every(
                (el) => new RegExp(motif).test(el.textContent.replace(/[\\u00a0\\u202f]/g, ' '))); }""",
        arg=motif,
        timeout=DELAI,
    )
    return page.locator(BARRE).count()


def parcours(page) -> None:
    # 1. Boutique : barre de l'en-tête (posée par le thème avec do_action( 'fabrique_livraison_barre', 'entete' )).
    page.goto(f"{URL}/boutique/", wait_until="domcontentloaded")
    if page.locator(BARRE).count() == 0:
        # Thème sans barre dans l'en-tête : on simule l'emplacement du thème ; les fragments WooCommerce
        # doivent remplacer cet élément comme ils remplacent celui du thème.
        page.evaluate(
            """() => document.body.insertAdjacentHTML('afterbegin',
            '<div class="lf-livraison" data-lf-livraison><p class="lf-livraison__texte">?</p></div>')"""
        )
        print("  (aucune barre dans l'en-tête du thème : emplacement simulé)")
    else:
        texte = normaliser(page.locator(TEXTE_BARRE).first.text_content())
        assert texte == "Livraison offerte dès 45 €", f"barre initiale : {texte!r}"
        etape("en-tête : « Livraison offerte dès 45 € » (panier vide)")

    # 2. Ajout au panier en AJAX (produit simple) : la barre est remplacée par le fragment à jour.
    bouton = page.locator('a.ajax_add_to_cart[data-product_sku="dripper-gres"]').first
    with page.expect_response(lambda r: "wc-ajax=add_to_cart" in r.url, timeout=DELAI) as reponse:
        bouton.click()
    assert reponse.value.ok, "ajout au panier refusé"
    fragments = reponse.value.json().get("fragments", {})
    assert "div[data-lf-livraison]" in fragments, "fragment de la barre absent de la réponse AJAX"
    texte = attendre_texte_barre(page, "Plus que 13 € pour la livraison offerte")
    etape(f"ajout AJAX du dripper (32 €) → « {texte} » sans rechargement")
    progression = page.locator(BARRE + " [role=progressbar]").first.get_attribute("aria-valuenow")
    assert progression == "71", f"aria-valuenow = {progression}"
    capture(page, "1-boutique-apres-ajout")

    # 3. Panier : quantité 2 → mise à jour AJAX du panier → livraison offerte.
    page.goto(f"{URL}/panier/", wait_until="domcontentloaded")
    attendre_texte_barre(page, "Plus que 13 €", ".woocommerce-cart-form " + TEXTE_BARRE)
    page.locator("input.qty").first.fill("2")
    page.locator('button[name="update_cart"]').click()
    texte = attendre_texte_barre(page, "La livraison est offerte ✓", ".woocommerce-cart-form " + TEXTE_BARRE)
    etape(f"panier : quantité 2 (64 €) → « {texte} »")
    nombre = attendre_toutes_les_barres(page, "La livraison est offerte ✓")
    if nombre > 1:
        etape(f"les {nombre} barres de la page sont à jour (en-tête du thème compris, via les fragments)")
    capture(page, "2-panier")

    # 4. Commande : coordonnées.
    page.goto(f"{URL}/commande/", wait_until="domcontentloaded")
    champs = {
        "#billing_first_name": "Camille",
        "#billing_last_name": "Test",
        "#billing_address_1": "12 rue de la Filature",
        "#billing_postcode": "59800",
        "#billing_city": "Lille",
        "#billing_phone": "0600000000",
        "#billing_email": "camille.parcours@example.com",
    }
    for selecteur, valeur in champs.items():
        page.locator(selecteur).fill(valeur)

    creneau = page.locator("#fabrique_creneau")
    expect(page.locator('input.shipping_method[value^="free_shipping"]')).to_be_checked(timeout=DELAI)
    assert creneau.count() == 0, "créneau affiché alors que la livraison est choisie"
    assert page.locator('input.shipping_method[value^="flat_rate"]').count() == 0, "Colissimo payant encore proposé"
    etape("commande : livraison offerte cochée par défaut, pas de champ créneau, Colissimo masqué")

    # 5. Retrait à l'atelier : le champ apparaît (récapitulatif recalculé par WooCommerce).
    page.locator('input.shipping_method[value^="local_pickup"]').check()
    expect(creneau).to_be_visible(timeout=DELAI)
    options = creneau.locator("option").all_text_contents()
    assert len(options) == 7, f"6 créneaux + l'invite attendus, obtenu {options}"
    assert re.match(r"^(Mercredi|Samedi) \d{1,2} \S+ · \d{1,2} h – \d{1,2} h$", options[1]), options[1]
    etape(f"« Retrait à l'atelier » → champ créneau affiché ({len(options) - 1} créneaux, ex. « {options[1]} »)")

    # Retour à la livraison : le champ disparaît ; puis retour au retrait.
    page.locator('input.shipping_method[value^="free_shipping"]').check()
    expect(creneau).to_have_count(0, timeout=DELAI)
    page.locator('input.shipping_method[value^="local_pickup"]').check()
    expect(creneau).to_be_visible(timeout=DELAI)
    etape("le champ disparaît si l'on revient à la livraison, réapparaît avec le retrait")

    # 6. Commander sans créneau → refus côté serveur.
    page.locator("#place_order").click()
    erreur = page.locator(".woocommerce-error, .woocommerce-NoticeGroup-checkout, [role=alert]").filter(
        has_text="Choisissez un créneau de retrait"
    )
    expect(erreur.first).to_be_visible(timeout=DELAI)
    assert "order-received" not in page.url
    etape("commande sans créneau refusée : « Choisissez un créneau de retrait à l’atelier. »")
    capture(page, "3-commande-erreur")

    # 7. Créneau choisi → commande enregistrée.
    creneau.select_option(index=1)
    valeur = creneau.input_value()
    libelle = normaliser(creneau.locator("option:checked").text_content())
    page.locator("#place_order").click()
    page.wait_for_url(re.compile(r"order-received"), timeout=DELAI)
    corps = normaliser(page.locator("body").inner_text())
    assert "Créneau de retrait" in corps, "créneau absent de la page de remerciement"
    assert libelle in corps, f"libellé {libelle!r} absent de la page de remerciement"
    etape(f"commande passée : page de remerciement avec « Créneau de retrait : {libelle} »")
    capture(page, "4-remerciement")

    # 8. Vérification en base (facultative) : méta enregistrée via WC_Order.
    numero = re.search(r"order-received/(\d+)", page.url)
    if WP and numero:
        commande = numero.group(1)
        sortie = subprocess.run(
            shlex.split(WP)
            + ["eval", f"$o = wc_get_order( {commande} ); echo $o->get_meta( '_fabrique_creneau' ), '|', $o->get_shipping_method();"],
            capture_output=True,
            text=True,
            check=False,
        ).stdout.strip().splitlines()[-1]
        assert sortie.startswith(valeur + "|"), f"méta en base : {sortie!r}, attendu {valeur!r}"
        etape(f"commande n° {commande} en base : _fabrique_creneau = « {valeur} », expédition « {sortie.split('|')[1]} »")


def main() -> int:
    print(f"Parcours de commande sur {URL}")
    with sync_playwright() as p:
        navigateur = p.chromium.launch()
        contexte = navigateur.new_context(locale="fr-FR", viewport={"width": 1280, "height": 900})
        page = contexte.new_page()
        page.set_default_timeout(DELAI)
        try:
            parcours(page)
        except (AssertionError, DelaiDepasse) as erreur:
            capture(page, "echec")
            print(f"  ✗ {type(erreur).__name__} : {erreur}")
            return 1
        finally:
            navigateur.close()
    print("Parcours réussi.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
