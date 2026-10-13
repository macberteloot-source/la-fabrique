"""Primitives de dessin : une « toile » SVG et quelques formes réutilisables.

Le style de La Fabrique : aplats chaleureux, contour encre fin, ombres en hachures
légères. Tout est vectoriel ; le hasard (grains éparpillés, fibres du kraft) est
déterministe (graine fixe) pour que deux générations donnent les mêmes images.
"""

from __future__ import annotations

import math
import random

import typo

# Palette de la marque (cf. cahier des charges, § 1) et teintes d'illustration.
ENCRE = "#1B1512"
CAFE = "#3B2A20"
PAPIER = "#F4EEE3"
BLANC = "#FFF8EE"
BRIQUE = "#B8502E"
BRIQUE_FONCE = "#93401F"
CREME = "#D9B38C"
VERT = "#2F4A3A"
KRAFT = "#C9A27A"
KRAFT_CLAIR = "#D8B993"
KRAFT_FONCE = "#AE8558"
GRAIN = "#5E3B27"
GRAIN_CLAIR = "#7B4F33"
ACIER = "#B3AEA6"
ACIER_CLAIR = "#D6D2CB"
ACIER_FONCE = "#827C74"
BOIS = "#9A6440"
BOIS_CLAIR = "#B57D52"
GRES = "#E6D9C3"
ARGILE = "#9C6644"
CAFE_LIQUIDE = "#3A2416"
CREMA = "#B9814F"
LAIT = "#F6EBDD"

EP = 2.6  # épaisseur du contour encre (unités du viewBox)


def assombrir(hexa: str, facteur: float) -> str:
    """Multiplie chaque composante (facteur < 1 : plus sombre, > 1 : plus clair)."""
    r, v, b = (int(hexa[i:i + 2], 16) for i in (1, 3, 5))
    r, v, b = (max(0, min(255, round(c * facteur))) for c in (r, v, b))
    return f"#{r:02X}{v:02X}{b:02X}"


def melanger(a: str, b: str, t: float) -> str:
    """Mélange linéaire de deux couleurs (t = 0 → a, t = 1 → b)."""
    ca = [int(a[i:i + 2], 16) for i in (1, 3, 5)]
    cb = [int(b[i:i + 2], 16) for i in (1, 3, 5)]
    return "#" + "".join(f"{round(x + (y - x) * t):02X}" for x, y in zip(ca, cb))


def n(v: float) -> str:
    """Nombre court pour les attributs SVG."""
    return f"{v:.2f}".rstrip("0").rstrip(".")


# ---------------------------------------------------------------------------
# Constructeurs de tracés
# ---------------------------------------------------------------------------

def rect_arrondi(x, y, l, h, r=0):
    if r <= 0:
        return f"M{n(x)},{n(y)}h{n(l)}v{n(h)}h{n(-l)}Z"
    return (
        f"M{n(x + r)},{n(y)}H{n(x + l - r)}A{n(r)},{n(r)} 0 0 1 {n(x + l)},{n(y + r)}"
        f"V{n(y + h - r)}A{n(r)},{n(r)} 0 0 1 {n(x + l - r)},{n(y + h)}"
        f"H{n(x + r)}A{n(r)},{n(r)} 0 0 1 {n(x)},{n(y + h - r)}"
        f"V{n(y + r)}A{n(r)},{n(r)} 0 0 1 {n(x + r)},{n(y)}Z"
    )


def ellipse(cx, cy, rx, ry):
    return (
        f"M{n(cx - rx)},{n(cy)}A{n(rx)},{n(ry)} 0 1 0 {n(cx + rx)},{n(cy)}"
        f"A{n(rx)},{n(ry)} 0 1 0 {n(cx - rx)},{n(cy)}Z"
    )


def cercle(cx, cy, r):
    return ellipse(cx, cy, r, r)


def polygone(points, ferme=True):
    d = "M" + " L".join(f"{n(x)},{n(y)}" for x, y in points)
    return d + ("Z" if ferme else "")


def tourner(points, cx, cy, angle_deg):
    a = math.radians(angle_deg)
    c, s = math.cos(a), math.sin(a)
    return [(cx + (x - cx) * c - (y - cy) * s, cy + (x - cx) * s + (y - cy) * c) for x, y in points]


# ---------------------------------------------------------------------------
# La toile
# ---------------------------------------------------------------------------

class Toile:
    """Accumule les éléments SVG d'une illustration."""

    def __init__(self, largeur: int, hauteur: int, graine: int = 42):
        self.l = largeur
        self.h = hauteur
        self.defs: list[str] = []
        self.corps: list[str] = []
        self._compteur = 0
        self.hasard = random.Random(graine)

    def ident(self, prefixe: str = "i") -> str:
        self._compteur += 1
        return f"{prefixe}{self._compteur}"

    def brut(self, svg: str) -> None:
        self.corps.append(svg)

    def forme(self, d: str, rempli: str = "none", trait: str | None = ENCRE,
              ep: float = EP, opacite: float = 1.0, extra: str = "") -> None:
        """Un tracé rempli, avec contour encre par défaut."""
        attrs = [f'd="{d}"', f'fill="{rempli}"']
        if trait:
            attrs += [f'stroke="{trait}"', f'stroke-width="{n(ep)}"',
                      'stroke-linejoin="round"', 'stroke-linecap="round"']
        if opacite < 1:
            attrs.append(f'opacity="{n(opacite)}"')
        if extra:
            attrs.append(extra)
        self.corps.append(f"<path {' '.join(attrs)}/>")

    def trait(self, d: str, couleur: str = ENCRE, ep: float = EP, opacite: float = 1.0,
              extra: str = "") -> None:
        """Une ligne seule (non remplie)."""
        self.forme(d, "none", couleur, ep, opacite, extra)

    def texte(self, texte: str, police: str, taille: float, x: float, y: float,
              couleur: str = ENCRE, ancre: str = "start", interlettrage: float = 0.0,
              extra: str = "") -> None:
        d = typo.chemin(texte, police, taille, x, y, ancre, interlettrage)
        self.corps.append(f'<path d="{d}" fill="{couleur}" {extra}/>')

    def hachures(self, d: str, angle: float = -50, pas: float = 7.5, ep: float = 1.5,
                 couleur: str = ENCRE, opacite: float = 0.42, boite=None) -> None:
        """Ombre en hachures parallèles, découpées par la forme d."""
        ident = self.ident("h")
        self.defs.append(f'<clipPath id="{ident}"><path d="{d}"/></clipPath>')
        x0, y0, x1, y1 = boite or (0, 0, self.l, self.h)
        cx, cy = (x0 + x1) / 2, (y0 + y1) / 2
        rayon = math.hypot(x1 - x0, y1 - y0) / 2 + pas
        lignes = []
        k = -rayon
        while k <= rayon:
            lignes.append(f"M{n(cx + k)},{n(cy - rayon)}V{n(cy + rayon)}")
            k += pas
        self.corps.append(
            f'<g clip-path="url(#{ident})"><path d="{" ".join(lignes)}" '
            f'transform="rotate({n(angle)} {n(cx)} {n(cy)})" stroke="{couleur}" '
            f'stroke-width="{n(ep)}" opacity="{n(opacite)}" fill="none"/></g>'
        )

    def groupe_debut(self, transform: str = "", extra: str = "") -> None:
        t = f' transform="{transform}"' if transform else ""
        self.corps.append(f"<g{t} {extra}>")

    def groupe_fin(self) -> None:
        self.corps.append("</g>")

    def svg(self, titre: str = "") -> str:
        t = f"<title>{titre}</title>" if titre else ""
        return (
            f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {self.l} {self.h}" '
            f'width="{self.l}" height="{self.h}">{t}<defs>{"".join(self.defs)}</defs>'
            f'{"".join(self.corps)}</svg>'
        )


# ---------------------------------------------------------------------------
# Motifs récurrents
# ---------------------------------------------------------------------------

def grain_cafe(t: Toile, cx: float, cy: float, taille: float, angle: float = 0,
               couleur: str = GRAIN, ep: float = EP * 0.8) -> None:
    """Un grain de café torréfié : ovale et sillon en S."""
    rx, ry = taille, taille * 0.7
    t.groupe_debut(f"rotate({n(angle)} {n(cx)} {n(cy)})")
    t.forme(ellipse(cx, cy, rx, ry), couleur, ENCRE, ep)
    reflet = (f"M{n(cx - rx * 0.55)},{n(cy - ry * 0.45)} "
              f"Q{n(cx - rx * 0.1)},{n(cy - ry * 0.85)} {n(cx + rx * 0.45)},{n(cy - ry * 0.6)}")
    t.trait(reflet, assombrir(couleur, 1.45), ep * 0.9, 0.9)
    sillon = (f"M{n(cx - rx * 0.86)},{n(cy + ry * 0.08)} "
              f"C{n(cx - rx * 0.35)},{n(cy - ry * 0.42)} {n(cx + rx * 0.3)},{n(cy + ry * 0.42)} "
              f"{n(cx + rx * 0.86)},{n(cy - ry * 0.08)}")
    t.trait(sillon, ENCRE, ep)
    t.groupe_fin()


def grains_epars(t: Toile, zones, nombre: int, taille: float, couleur: str = GRAIN) -> None:
    """Quelques grains posés au sol dans des zones (x0, x1, y0, y1)."""
    for i in range(nombre):
        x0, x1, y0, y1 = zones[i % len(zones)]
        x = t.hasard.uniform(x0, x1)
        y = t.hasard.uniform(y0, y1)
        grain_cafe(t, x, y, taille * t.hasard.uniform(0.85, 1.1), t.hasard.uniform(-70, 70), couleur)


def fibres(t: Toile, d_clip: str, boite, couleur: str, nombre: int = 70, longueur: float = 14) -> None:
    """Fibres du papier kraft : courts traits clairsemés, découpés par la forme."""
    ident = t.ident("f")
    t.defs.append(f'<clipPath id="{ident}"><path d="{d_clip}"/></clipPath>')
    x0, y0, x1, y1 = boite
    traits = []
    for _ in range(nombre):
        x = t.hasard.uniform(x0, x1)
        y = t.hasard.uniform(y0, y1)
        a = math.radians(t.hasard.uniform(-25, 25))
        lg = longueur * t.hasard.uniform(0.5, 1.2)
        traits.append(f"M{n(x)},{n(y)}l{n(lg * math.cos(a))},{n(lg * math.sin(a))}")
    t.brut(f'<g clip-path="url(#{ident})"><path d="{" ".join(traits)}" stroke="{couleur}" '
           f'stroke-width="1.6" stroke-linecap="round" opacity="0.55" fill="none"/></g>')


def vapeur(t: Toile, x: float, y: float, hauteur: float, ecart: float = 22, nombre: int = 3,
           couleur: str = ENCRE) -> None:
    """Volutes de vapeur : lignes ondulées fines au-dessus d'une boisson chaude."""
    for i in range(nombre):
        bx = x + (i - (nombre - 1) / 2) * ecart
        h = hauteur * (0.85 if i % 2 else 1.0)
        d = (f"M{n(bx)},{n(y)} C{n(bx - 12)},{n(y - h * 0.25)} {n(bx + 12)},{n(y - h * 0.45)} "
             f"{n(bx)},{n(y - h * 0.62)} S{n(bx - 10)},{n(y - h * 0.9)} {n(bx + 2)},{n(y - h)}")
        t.trait(d, couleur, EP * 0.8, 0.55)


def fenetre_arche(t: Toile, x0: float, x1: float, sol: float, haut: float, fond: str,
                  meneaux: bool = True) -> None:
    """Grande fenêtre en arc de la filature, en fond de scène."""
    rayon = (x1 - x0) / 2
    cx = (x0 + x1) / 2
    d = (f"M{n(x0)},{n(sol)}V{n(haut + rayon)}A{n(rayon)},{n(rayon)} 0 0 1 {n(x1)},{n(haut + rayon)}"
         f"V{n(sol)}Z")
    t.forme(d, fond, None)
    if meneaux:
        couleur = assombrir(fond, 0.9)
        lignes = []
        for i in (1, 2, 3):
            x = x0 + (x1 - x0) * i / 4
            dy = rayon - math.sqrt(max(rayon ** 2 - (x - cx) ** 2, 0))
            lignes.append(f"M{n(x)},{n(haut + dy)}V{n(sol)}")
        for frac in (0.42, 0.7):
            y = haut + rayon + (sol - haut - rayon) * frac - rayon * 0.3
            lignes.append(f"M{n(x0)},{n(y)}H{n(x1)}")
        lignes.append(f"M{n(x0)},{n(haut + rayon)}H{n(x1)}")
        t.trait(" ".join(lignes), couleur, 5, 1)


def briques(t: Toile, fond: str, largeur: float, horizon: float, pas: float = 26,
            longueur: float = 64) -> None:
    """Appareil de briques chaulées (mur de la filature), très discret."""
    couleur = assombrir(fond, 0.94)
    lignes = []
    y, rang = pas, 0
    while y < horizon:
        lignes.append(f"M0,{n(y)}H{n(largeur)}")
        x = (longueur / 2) * (rang % 2)
        while x < largeur:
            lignes.append(f"M{n(x)},{n(y - pas)}V{n(y)}")
            x += longueur
        y += pas
        rang += 1
    t.trait(" ".join(lignes), couleur, 1.6)


def toits_sheds(points_x, base: float, hauteur: float) -> str:
    """Silhouette en dents de scie (sheds) : pente montante puis verticale vitrée."""
    d = [f"M{n(points_x[0])},{n(base)}"]
    for a, b in zip(points_x, points_x[1:]):
        d.append(f"L{n(b)},{n(base - hauteur)}L{n(b)},{n(base)}")
    return "".join(d)


def etiquette_ronde(t: Toile, cx: float, cy: float, r: float, couleur: str, lignes,
                    angle: float = -12, couleur_texte: str = BLANC) -> None:
    """Pastille ronde (édition limitée, décaféiné…) avec une à deux lignes de texte."""
    t.groupe_debut(f"rotate({n(angle)} {n(cx)} {n(cy)})")
    t.forme(cercle(cx, cy, r), couleur, ENCRE, EP)
    t.trait(cercle(cx, cy, r - 7), couleur_texte, 1.4, 0.8, 'stroke-dasharray="3 4"')
    hauteur = 0
    tailles = []
    for texte, police in lignes:
        taille = typo.ajuster(texte, police, r * 1.45, r * 0.42)
        tailles.append(taille)
        hauteur += taille * 1.05
    y = cy - hauteur / 2
    for (texte, police), taille in zip(lignes, tailles):
        y += taille * 0.95
        t.texte(texte, police, taille, cx, y, couleur_texte, "middle")
        y += taille * 0.1
    t.groupe_fin()
