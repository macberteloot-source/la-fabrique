"""Identité de La Fabrique : symbole, logo, favicons, tampon, motif des sheds, grain, atelier.

Le symbole est un grain de café dont le sillon est la ligne en dents de scie des toits
d'usine du Nord (les sheds). La géométrie est calculée ici (décalage de polyligne avec
jointures en onglet, intersections avec l'ellipse) : le grain est un seul tracé plein,
sans masque, qui s'affiche partout (navigateur, cairosvg, favicon).
"""

from __future__ import annotations

import io
import math
import random

from PIL import Image

import typo
from primitives import (
    ACIER_FONCE, BLANC, BRIQUE, BRIQUE_FONCE, CREME, ENCRE, KRAFT, PAPIER, VERT, Toile,
    assombrir, cercle, ellipse, n, polygone, rect_arrondi, toits_sheds,
)

PAPIER_3 = "#DDD0BC"


# ---------------------------------------------------------------------------
# Géométrie du symbole
# ---------------------------------------------------------------------------

def _decaler(points, distance):
    """Décale une polyligne d'une distance (positive : vers le haut en repère SVG)."""
    resultat = []
    normales = []
    for (x0, y0), (x1, y1) in zip(points, points[1:]):
        dx, dy = x1 - x0, y1 - y0
        lg = math.hypot(dx, dy)
        normales.append((dy / lg, -dx / lg))
    for i, (x, y) in enumerate(points):
        if i == 0:
            nx, ny = normales[0]
            resultat.append((x + nx * distance, y + ny * distance))
        elif i == len(points) - 1:
            nx, ny = normales[-1]
            resultat.append((x + nx * distance, y + ny * distance))
        else:
            (ax, ay), (bx, by) = normales[i - 1], normales[i]
            mx, my = ax + bx, ay + by
            lg = math.hypot(mx, my)
            mx, my = mx / lg, my / lg
            echelle = distance / (mx * ax + my * ay)
            resultat.append((x + mx * echelle, y + my * echelle))
    return resultat


def _intersection_ellipse(p, q, a, b):
    """Point d'entrée du segment pq (p dehors, q dedans) sur l'ellipse x²/a² + y²/b² = 1."""
    (x0, y0), (x1, y1) = p, q
    dx, dy = x1 - x0, y1 - y0
    A = dx * dx / (a * a) + dy * dy / (b * b)
    B = 2 * (x0 * dx / (a * a) + y0 * dy / (b * b))
    C = x0 * x0 / (a * a) + y0 * y0 / (b * b) - 1
    t = (-B - math.sqrt(B * B - 4 * A * C)) / (2 * A)
    return (x0 + t * dx, y0 + t * dy)


def _morceau(ligne, a, b, haut: bool) -> str:
    """Moitié du grain : arc d'ellipse + bord du sillon (ligne déjà décalée)."""
    gauche = _intersection_ellipse(ligne[0], ligne[1], a, b)
    droite = _intersection_ellipse(ligne[-1], ligne[-2], a, b)
    milieu = [gauche] + ligne[1:-1] + [droite]
    d = "M" + " L".join(f"{n(x)},{n(y)}" for x, y in milieu)
    # Retour par l'arc : par le haut (sens antihoraire à l'écran) ou par le bas.
    ang_d = math.atan2(droite[1] / b, droite[0] / a)
    ang_g = math.atan2(gauche[1] / b, gauche[0] / a)
    if haut:
        balayage = (ang_d - ang_g) % (2 * math.pi)
        d += f" A{n(a)},{n(b)} 0 {1 if balayage > math.pi else 0} 0 {n(gauche[0])},{n(gauche[1])}Z"
    else:
        balayage = (ang_g - ang_d) % (2 * math.pi)
        d += f" A{n(a)},{n(b)} 0 {1 if balayage > math.pi else 0} 1 {n(gauche[0])},{n(gauche[1])}Z"
    return d


def symbole_d(dents: int = 3, demi_epaisseur: float = 1.7) -> str:
    """Le grain (centré sur 0,0, rx 30, ry 21) coupé par un sillon en sheds."""
    a, b = 30.0, 21.0
    base, crete = 4.2, -5.2
    largeur_dents = 42.0 if dents == 3 else 34.0
    pas = largeur_dents / dents
    x = -largeur_dents / 2 + pas * 0.18  # léger décalage : les deux replats paraissent égaux
    points = [(-a - 6, base), (x, base)]
    for _ in range(dents):
        points.append((x + pas, crete))
        points.append((x + pas, base))
        x += pas
    points.append((a + 6, base))
    haut = _decaler(points, demi_epaisseur)
    bas = _decaler(points, -demi_epaisseur)
    return _morceau(haut, a, b, True) + " " + _morceau(bas, a, b, False)


def symbole_svg(couleur: str = BRIQUE, taille: int = 64, dents: int = 3, epaisseur: float = 1.7,
                fond: str | None = None, rayon_fond: float = 0, angle: float = 0) -> str:
    fond_svg = ""
    if fond:
        fond_svg = f'<rect width="64" height="64" rx="{rayon_fond}" fill="{fond}"/>'
    return (
        f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="{taille}" height="{taille}">'
        f"{fond_svg}<path transform=\"translate(32 32) rotate({angle})\" fill=\"{couleur}\" "
        f'd="{symbole_d(dents, epaisseur)}"/></svg>'
    )


# ---------------------------------------------------------------------------
# Logo (symbole + mot-symbole vectorisé)
# ---------------------------------------------------------------------------

def logo_svg(couleur_mot: str = ENCRE, couleur_symbole: str = BRIQUE, signature: bool = False) -> str:
    taille = 46
    mot = typo.chemin("La Fabrique", "serif", taille, 66, 46)
    largeur_mot = typo.largeur("La Fabrique", "serif", taille)
    largeur = math.ceil(66 + largeur_mot + 2)
    hauteur = 60
    extra = ""
    if signature:
        hauteur = 80
        sig = typo.chemin("TORRÉFACTEUR · LILLE", "sans-600", 10.5, 67, 72, interlettrage=0.2)
        extra = f'<path class="lf-logo__signature" fill="{couleur_mot}" d="{sig}"/>'
    return (
        f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {largeur} {hauteur}" width="{largeur}" '
        f'height="{hauteur}" role="img" aria-labelledby="lf-logo-titre"><title id="lf-logo-titre">La Fabrique</title>'
        f'<path class="lf-logo__symbole" fill="{couleur_symbole}" transform="translate(29 30) scale(.9)" '
        f'd="{symbole_d()}"/>'
        f'<path class="lf-logo__mot" fill="{couleur_mot}" d="{mot}"/>{extra}</svg>'
    )


def tampon_svg(couleur: str = BRIQUE) -> str:
    """Tampon rond : texte circulaire autour du grain (badge qui tourne sur l'accueil)."""
    texte = "TORRÉFIÉ À FIVES · CHAQUE MARDI · LILLE · "
    d = typo.sur_cercle(texte, "sans-600", 13.5, 100, 100, 72, -90, interlettrage=0.14)
    # Répartir le texte sur tout le cercle : on ajuste l'interlettrage pour boucler.
    circonference = 2 * math.pi * 72
    brut = typo.largeur(texte, "sans-600", 13.5)
    inter = (circonference - brut) / (13.5 * len(texte))
    d = typo.sur_cercle(texte, "sans-600", 13.5, 100, 100, 72, -90, interlettrage=inter)
    return (
        f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="200" height="200">'
        f'<circle cx="100" cy="100" r="96" fill="none" stroke="{couleur}" stroke-width="2"/>'
        f'<circle cx="100" cy="100" r="58" fill="none" stroke="{couleur}" stroke-width="1.5"/>'
        f'<path class="lf-tampon__texte" fill="{couleur}" d="{d}"/>'
        f'<path fill="{couleur}" transform="translate(100 100) scale(1.2)" d="{symbole_d()}"/>'
        f"</svg>"
    )


def toits_svg() -> str:
    """Motif répétable des sheds (silhouette pour un masque CSS), période 120 × 40."""
    d = toits_sheds([0, 40, 80, 120], 30, 22) + "V40H0Z"
    return (
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 40" width="120" height="40">'
        f'<path d="{d}" fill="#000"/></svg>'
    )


def grain_png() -> bytes:
    """Tuile de grain papier (bruit transparent) pour une texture CSS très légère."""
    hasard = random.Random(5)
    taille = 180
    image = Image.new("LA", (taille, taille))
    pixels = image.load()
    for y in range(taille):
        for x in range(taille):
            v = hasard.random()
            if v < 0.5:
                pixels[x, y] = (40, int(hasard.uniform(0, 26)))
            else:
                pixels[x, y] = (255, int(hasard.uniform(0, 18)))
    sortie = io.BytesIO()
    image.save(sortie, "PNG", optimize=True)
    return sortie.getvalue()


def encre_png() -> bytes:
    """Masque CSS « encre de tampon » : surtout opaque, avec des manques irréguliers d'impression."""
    import numpy as np
    from PIL import ImageFilter

    taille = 240
    bruit = np.random.default_rng(9).random((taille, taille))
    flou = Image.fromarray((bruit * 255).astype("uint8"), "L").filter(ImageFilter.GaussianBlur(1.4))
    valeurs = np.asarray(flou).astype(float)
    valeurs = (valeurs - valeurs.mean()) / valeurs.std()
    # Quelques pour cent de manques nets, bords adoucis.
    alpha = np.clip((valeurs + 1.85) / 0.45, 0, 1)
    masque = Image.new("RGBA", (taille, taille), (0, 0, 0, 0))
    masque.putalpha(Image.fromarray((alpha * 255).astype("uint8"), "L"))
    sortie = io.BytesIO()
    masque.save(sortie, "PNG", optimize=True)
    return sortie.getvalue()


# ---------------------------------------------------------------------------
# L'atelier (illustration de l'accueil et de la page « L'atelier »)
# ---------------------------------------------------------------------------

def atelier_svg() -> str:
    t = Toile(900, 600, graine=3)
    sol = 540
    # Fumée de torréfaction (cercles clairs).
    for cx, cy, r in ((168, 70, 20), (190, 40, 26), (226, 14, 32)):
        t.forme(cercle(cx, cy, r), PAPIER_3, ENCRE, 2, 0.9)
    # Cheminée de briques.
    t.forme(polygone([(140, sol), (150, 92), (186, 92), (196, sol)]), BRIQUE_FONCE)
    t.forme(rect_arrondi(144, 80, 48, 16, 2), BRIQUE)
    for y in range(110, sol, 22):
        t.trait(f"M{150 + (y - 92) * 0.02},{y}H{186 - (y - 92) * -0.02}", assombrir(BRIQUE_FONCE, 0.8), 1.4, 0.7)
    for i, lettre in enumerate("FABRIQUE"):
        t.texte(lettre, "sans-700", 15, 168, 150 + i * 22, PAPIER, "middle")
    # Toits en sheds avec leurs verrières.
    xs = [214 + i * 146 for i in range(5)]
    t.forme(toits_sheds(xs, 214, 92) + "Z", "#4A3A33")
    for x in xs[1:]:
        t.forme(polygone([(x - 2, 128), (x - 2, 212), (x - 12, 212), (x - 12, 136)]), "#BFD0D3", ENCRE, 1.6)
        t.trait(f"M{x - 7},132V212", ENCRE, 1.2)
    # Façade de briques.
    facade = rect_arrondi(214, 212, 584, sol - 212, 0)
    t.forme(facade, BRIQUE)
    lignes = []
    for rang, y in enumerate(range(230, sol, 18)):
        lignes.append(f"M214,{y}H798")
        for x in range(214 + (rang % 2) * 22, 798, 44):
            lignes.append(f"M{x},{y - 18}V{y}")
    t.trait(" ".join(lignes), assombrir(BRIQUE, 0.82), 1.3, 0.6)
    t.forme(facade)
    t.forme(rect_arrondi(206, 204, 600, 16, 2), assombrir(BRIQUE, 0.8))
    t.forme(rect_arrondi(214, sol - 34, 584, 34, 0), PAPIER_3)
    # Fenêtres en arc éclairées.
    chaud = "#F3D29A"
    for x in (250, 640):
        fen = f"M{x},{sol - 60}V330A{n(70)},{n(70)} 0 0 1 {x + 140},330V{sol - 60}Z"
        t.forme(fen, chaud)
        t.trait(f"M{x + 70},262V{sol - 60}M{x},380H{x + 140}M{x},440H{x + 140}", ENCRE, 2)
        t.forme(fen)
        t.forme(rect_arrondi(x - 8, sol - 64, 156, 12, 2), PAPIER_3)
    # Grande porte ouverte : le torréfacteur à l'intérieur.
    px = 432
    porte = f"M{px},{sol - 34}V318A{n(84)},{n(84)} 0 0 1 {px + 168},318V{sol - 34}Z"
    t.forme(porte, "#6E4A33")
    ident = t.ident("porte")
    t.defs.append(f'<clipPath id="{ident}"><path d="{porte}"/></clipPath>')
    t.brut(f'<g clip-path="url(#{ident})">')
    t.forme(f"M{px},{sol - 34}V250H{px + 168}V{sol - 34}Z", "#E9B877", None)
    t.forme(f"M{px},{sol - 80}H{px + 168}V{sol}H{px}Z", "#C99358", None)
    # Torréfacteur à tambour : trémie, tambour, bac de refroidissement.
    t.forme(polygone([(px + 70, 300), (px + 120, 300), (px + 104, 340), (px + 86, 340)]), ACIER_FONCE)
    t.forme(rect_arrondi(px + 46, 340, 100, 88, 12), "#2F2723")
    t.forme(cercle(px + 96, 384, 26), "#8C7B6A")
    t.forme(cercle(px + 96, 384, 12), "#E9B877")
    t.forme(rect_arrondi(px + 136, 270, 14, 74, 3), ACIER_FONCE)
    t.forme(ellipse(px + 70, 470, 70, 14), ACIER_FONCE)
    t.forme(f"M{px},458H{px + 140}V470H{px}Z", ACIER_FONCE, None)
    t.forme(ellipse(px + 70, 458, 70, 14), "#5A3B27")
    t.forme(rect_arrondi(px + 66, 470, 8, 60, 2), ENCRE)
    t.brut("</g>")
    t.forme(porte)
    # Enseigne.
    t.forme(rect_arrondi(px - 6, 232, 180, 44, 3), VERT)
    t.texte("La Fabrique", "serif", 30, px + 84, 263, PAPIER, "middle")
    # Sacs de café vert tamponnés devant la porte.
    for i, (sx, couleur) in enumerate(((618, "#D8C39E"), (668, "#CDB590"))):
        sac = (f"M{sx - 4},{sol}Q{sx - 10},{sol - 40} {sx},{sol - 70}L{sx + 6},{sol - 86}L{sx + 16},{sol - 72}"
               f"Q{sx + 24},{sol - 78} {sx + 32},{sol - 72}L{sx + 42},{sol - 86}L{sx + 48},{sol - 70}"
               f"Q{sx + 58},{sol - 40} {sx + 52},{sol}Z")
        t.forme(sac, couleur)
        t.trait(f"M{sx + 2},{sol - 62}Q{sx + 24},{sol - 56} {sx + 46},{sol - 62}", assombrir(couleur, 0.75), 2)
        t.trait(f"M{sx + 8},{sol - 40}H{sx + 40}", BRIQUE, 2)
        t.forme(cercle(sx + 24, sol - 22, 9), "none", BRIQUE, 2)
    # Pavés.
    t.trait(f"M90,{sol}H850", ENCRE, 2.6)
    paves = []
    for rang, y in enumerate((sol + 16, sol + 34, sol + 54)):
        for x in range(110 + rang * 14, 840, 38 + rang * 6):
            paves.append(f"M{x},{y}h{20 + rang * 4}")
    t.trait(" ".join(paves), assombrir(PAPIER_3, 0.8), 2.4)
    return t.svg("L'atelier de La Fabrique : une ancienne filature de briques aux toits en sheds, à Fives")


# ---------------------------------------------------------------------------
# Fichiers du thème
# ---------------------------------------------------------------------------

def fichiers(png) -> dict[str, str | bytes]:
    """Renvoie {nom de fichier: contenu} pour assets/img du thème."""
    favicon = symbole_svg(BRIQUE, 64, dents=2, epaisseur=2.6)
    tuile = symbole_svg(BRIQUE, 64, dents=2, epaisseur=2.6, fond=PAPIER, rayon_fond=14)
    return {
        "logo.svg": logo_svg(),
        "logo-signature.svg": logo_svg(signature=True),
        "symbole.svg": symbole_svg(),
        "favicon.svg": favicon,
        "favicon-32.png": png(favicon.replace('width="64" height="64"', 'width="32" height="32"'), 32, 32, 0),
        "apple-touch-icon.png": png(tuile, 180, 180, 0),
        "icone-512.png": png(tuile, 512, 512, 0),
        "tampon.svg": tampon_svg(),
        "toits.svg": toits_svg(),
        "grain.png": grain_png(),
        "encre.png": encre_png(),
        "atelier.svg": atelier_svg(),
    }
