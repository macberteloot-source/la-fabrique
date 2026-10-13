#!/usr/bin/env python3
"""Générateur d'illustrations de La Fabrique (SVG → PNG).

Usage (depuis n'importe quel dossier) :
    python3 wordpress/tools/illustrations/generer.py            # tout régénérer
    python3 wordpress/tools/illustrations/generer.py beffroi     # seulement certaines images
    python3 wordpress/tools/illustrations/generer.py --svg       # garder aussi les SVG (dossier build/)

Dépendances Python (versions épinglées) : Pillow, cairosvg, fontTools (+ brotli pour lire
les woff2) et uharfbuzz.
    pip install -r wordpress/tools/illustrations/requirements.txt
cairosvg s'appuie sur la bibliothèque système cairo :
    apt install libcairo2      (Debian, Ubuntu)
    brew install cairo         (macOS)

Sorties :
- wordpress/seed/images/{sku}.png             produits, 1200 × 1500 (4:5)
- wordpress/seed/images/recette-{slug}.png    recettes, 1500 × 1000 (3:2)
- wordpress/wp-content/themes/la-fabrique/assets/img/   logo, symbole, favicons, grain, atelier
Les textes sont vectorisés (voir typo.py) : aucune police n'est nécessaire au rendu.
"""

from __future__ import annotations

import io
import sys
from pathlib import Path

import cairosvg
from PIL import Image

sys.path.insert(0, str(Path(__file__).resolve().parent))

import marque  # noqa: E402
import produits  # noqa: E402
import recettes  # noqa: E402

RACINE = Path(__file__).resolve().parents[2]
SEED = RACINE / "seed" / "images"
THEME_IMG = RACINE / "wp-content" / "themes" / "la-fabrique" / "assets" / "img"
BUILD = Path(__file__).resolve().parent / "build"


def png(svg: str, largeur: int, hauteur: int, couleurs: int = 256) -> bytes:
    """Rend le SVG puis réduit la palette (aplats : gain de poids sans perte visible)."""
    brut = cairosvg.svg2png(bytestring=svg.encode("utf-8"), output_width=largeur, output_height=hauteur)
    image = Image.open(io.BytesIO(brut)).convert("RGB")
    if couleurs:
        image = image.quantize(colors=couleurs, method=Image.Quantize.MEDIANCUT, dither=Image.Dither.NONE)
    sortie = io.BytesIO()
    image.save(sortie, "PNG", optimize=True)
    return sortie.getvalue()


def ecrire(chemin: Path, contenu: bytes | str) -> None:
    chemin.parent.mkdir(parents=True, exist_ok=True)
    if isinstance(contenu, str):
        chemin.write_text(contenu, encoding="utf-8")
    else:
        chemin.write_bytes(contenu)
    print(f"  {chemin.relative_to(RACINE.parent)}  ({chemin.stat().st_size // 1024} Ko)")


def main(arguments: list[str]) -> None:
    garder_svg = "--svg" in arguments
    filtres = [a for a in arguments if not a.startswith("--")]

    def voulu(nom: str) -> bool:
        return not filtres or any(f in nom for f in filtres)

    print("Produits (4:5)")
    for sku, svg in produits.scenes().items():
        if voulu(sku):
            ecrire(SEED / f"{sku}.png", png(svg, 1200, 1500))
            if garder_svg:
                ecrire(BUILD / f"{sku}.svg", svg)

    print("Recettes (3:2)")
    for slug, svg in recettes.scenes().items():
        if voulu(slug):
            ecrire(SEED / f"recette-{slug}.png", png(svg, 1500, 1000))
            if garder_svg:
                ecrire(BUILD / f"recette-{slug}.svg", svg)

    if voulu("marque"):
        print("Identité (thème)")
        for nom, contenu in marque.fichiers(png).items():
            ecrire(THEME_IMG / nom, contenu)


if __name__ == "__main__":
    main(sys.argv[1:])
