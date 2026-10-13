"""Illustrations des recettes (format paysage 3:2, viewBox 900 × 600).

Même décor que les produits (mur de briques chaulé, fenêtre en arc, plan de travail),
et au premier plan le matériel et la boisson de chaque recette.
"""

from __future__ import annotations

import math

from primitives import (
    ACIER, ACIER_CLAIR, ACIER_FONCE, BLANC, BOIS, BOIS_CLAIR, BRIQUE, CAFE_LIQUIDE, CREMA,
    ENCRE, EP, GRES, KRAFT, KRAFT_CLAIR, KRAFT_FONCE, LAIT, VERT, Toile, assombrir, cercle,
    briques, ellipse, fibres, grain_cafe, grains_epars, melanger, n, polygone, rect_arrondi, toits_sheds, vapeur,
)
import produits

L, H = 900, 600
HORIZON = 404
SOL = 498
VERRE = "#DCE6E2"
NUIT = "#2E3557"


def decor(t: Toile, fond: str, vue: str | None = None) -> None:
    t.forme(f"M0,0H{L}V{H}H0Z", fond, None)
    briques(t, fond, L, HORIZON)
    x0, x1, haut = 250, 650, 26
    rayon = (x1 - x0) / 2
    arche = f"M{x0},{HORIZON}V{haut + rayon}A{rayon},{rayon} 0 0 1 {x1},{haut + rayon}V{HORIZON}Z"
    t.forme(arche, melanger(fond, BLANC, 0.5), None)
    if vue:
        ident = t.ident("arche")
        t.defs.append(f'<clipPath id="{ident}"><path d="{arche}"/></clipPath>')
        t.brut(f'<g clip-path="url(#{ident})">')
        produits_vue = {
            "fives": lambda c: t.forme(toits_sheds([x0 + i * 50 for i in range(10)], 330, 34)
                                       + f"V{HORIZON}H{x0}Z", c, None),
        }
        produits_vue[vue](assombrir(fond, 0.88))
        t.brut("</g>")
    cadre = assombrir(fond, 0.8)
    lignes = [f"M{n(x0 + (x1 - x0) * i / 4)},{n(haut + 34 if i == 2 else haut + 52)}V{HORIZON}" for i in (1, 2, 3)]
    lignes += [f"M{x0},{haut + rayon}H{x1}", f"M{x0},{haut + rayon + 120}H{x1}"]
    t.trait(" ".join(lignes), cadre, 6)
    t.trait(arche, cadre, 10)
    t.forme(f"M0,{HORIZON}H{L}V{H}H0Z", assombrir(fond, 0.86), None)
    t.trait(f"M0,{HORIZON}H{L}", ENCRE, EP)
    t.trait(f"M0,{HORIZON + 12}H{L}", assombrir(fond, 0.78), 3)


def ombre(t: Toile, x0: float, x1: float, base: float, longueur: float = 120) -> None:
    t.hachures(polygone([(x0, base + 2), (x1, base - 8), (x1 + longueur, base + 18), (x0 + longueur * 0.6, base + 30)]),
               angle=-35, pas=7, opacite=0.3)


# ---------------------------------------------------------------------------
# Objets
# ---------------------------------------------------------------------------

def tasse(t: Toile, cx: float, base: float, l: float, h: float, couleur: str, liquide: str | None = CAFE_LIQUIDE,
          anse: bool = True, sous_tasse: bool = True) -> None:
    """Tasse en grès vue de face, avec son anse et sa soucoupe."""
    if sous_tasse:
        t.forme(ellipse(cx, base, l * 0.95, l * 0.16), assombrir(couleur, 1.1))
        t.forme(ellipse(cx, base - 4, l * 0.62, l * 0.08), assombrir(couleur, 0.9), None)
        base -= 6
    if anse:
        d = f"M{n(cx + l * 0.42)},{n(base - h * 0.78)}C{n(cx + l * 0.95)},{n(base - h * 0.85)} {n(cx + l * 0.9)},{n(base - h * 0.2)} {n(cx + l * 0.34)},{n(base - h * 0.28)}"
        t.forme(d, "none", ENCRE, 16)
        t.forme(d, "none", couleur, 9)
    corps = (f"M{n(cx - l / 2)},{n(base - h)}L{n(cx - l * 0.42)},{n(base - h * 0.2)}Q{n(cx - l * 0.36)},{n(base)} {n(cx - l * 0.2)},{n(base)}"
             f"H{n(cx + l * 0.2)}Q{n(cx + l * 0.36)},{n(base)} {n(cx + l * 0.42)},{n(base - h * 0.2)}L{n(cx + l / 2)},{n(base - h)}Z")
    t.forme(corps, couleur, None)
    t.hachures(polygone([(cx + l * 0.18, base - h), (cx + l * 0.55, base - h), (cx + l * 0.45, base), (cx + l * 0.12, base)]),
               pas=6, opacite=0.3)
    t.forme(corps)
    t.forme(ellipse(cx, base - h, l / 2, l * 0.1), assombrir(couleur, 1.08))
    if liquide:
        t.forme(ellipse(cx, base - h + 3, l * 0.44, l * 0.075), liquide, None)
        t.forme(ellipse(cx - l * 0.06, base - h + 2, l * 0.2, l * 0.03), CREMA, None, opacite=0.9)


def verre(t: Toile, cx: float, base: float, l: float, h: float, couches, reflet: bool = True) -> None:
    """Verre droit ; couches = [(fraction de hauteur, couleur), …] du bas vers le haut."""
    x0, x1 = cx - l / 2, cx + l / 2
    contour = f"M{n(x0)},{n(base - h)}L{n(x0 + l * 0.06)},{n(base - 6)}Q{n(x0 + l * 0.08)},{n(base)} {n(x0 + l * 0.18)},{n(base)}H{n(x1 - l * 0.18)}Q{n(x1 - l * 0.08)},{n(base)} {n(x1 - l * 0.06)},{n(base - 6)}L{n(x1)},{n(base - h)}Z"
    t.forme(contour, VERRE, None, opacite=0.7)
    ident = t.ident("verre")
    t.defs.append(f'<clipPath id="{ident}"><path d="{contour}"/></clipPath>')
    t.brut(f'<g clip-path="url(#{ident})">')
    y = base
    for fraction, couleur in couches:
        hauteur = h * fraction
        t.forme(f"M{n(x0 - 5)},{n(y - hauteur)}H{n(x1 + 5)}V{n(y + 5)}H{n(x0 - 5)}Z", couleur, None)
        t.trait(f"M{n(x0)},{n(y - hauteur)}H{n(x1)}", ENCRE, 1.4, 0.5)
        y -= hauteur
    t.brut("</g>")
    if reflet:
        t.trait(f"M{n(x0 + l * 0.16)},{n(base - h + 14)}L{n(x0 + l * 0.2)},{n(base - 20)}", BLANC, 5, 0.75)
    t.forme(contour)
    t.forme(ellipse(cx, base - h, l / 2, 6), "none", ENCRE, EP)


def grain_vert(t, x, y, s, a):
    grain_cafe(t, x, y, s, a, "#9DA77A")


# ---------------------------------------------------------------------------
# Scènes
# ---------------------------------------------------------------------------

def scene_espresso() -> str:
    fond = "#E8D2BE"
    t = Toile(L, H, graine=101)
    decor(t, fond)
    cx = 470
    # Tête de groupe de la machine (coupée par le cadre).
    t.forme(rect_arrondi(cx - 150, -20, 300, 120, 14), ACIER_CLAIR)
    t.hachures(rect_arrondi(cx + 60, -20, 90, 120, 14), pas=6, opacite=0.28)
    t.forme(rect_arrondi(cx - 150, -20, 300, 120, 14))
    t.forme(rect_arrondi(cx - 70, 96, 140, 30, 8), ACIER)
    # Porte-filtre : poignée en bois vers la gauche.
    t.forme(rect_arrondi(cx - 84, 122, 168, 46, 16), ACIER)
    t.forme(polygone([(cx - 82, 134), (cx - 270, 160), (cx - 272, 186), (cx - 80, 158)]), ACIER_FONCE)
    t.forme(rect_arrondi(cx - 380, 156, 140, 36, 18), BOIS, extra=f'transform="rotate(-7 {cx - 310} 174)"')
    for dx in (-22, 22):
        t.forme(polygone([(cx + dx - 9, 166), (cx + dx + 9, 166), (cx + dx + 5, 194), (cx + dx - 5, 194)]), ACIER_FONCE)
        t.trait(f"M{cx + dx},{196}L{cx + dx * 0.4},{352}", CAFE_LIQUIDE, 5)
    # Balance numérique et tasse.
    t.forme(rect_arrondi(cx - 120, SOL - 34, 240, 36, 8), "#3A3430")
    t.forme(rect_arrondi(cx - 104, SOL - 44, 208, 14, 5), "#55504B")
    t.forme(rect_arrondi(cx - 50, SOL - 26, 100, 22, 4), "#1F2A24", ENCRE, 1.6)
    t.texte("36,0 g", "sans-600", 16, cx, SOL - 9, "#CFE7C9", "middle")
    ombre(t, cx - 120, cx + 120, SOL, 140)
    tasse(t, cx, SOL - 44, 96, 76, GRES, CAFE_LIQUIDE)
    # Tasseur et dose de grains à droite.
    tx = 720
    ombre(t, tx - 40, tx + 40, SOL + 20, 80)
    t.forme(rect_arrondi(tx - 44, SOL - 6, 88, 26, 6), ACIER)
    t.forme(f"M{tx - 20},{SOL - 6}Q{tx - 26},{SOL - 80} {tx},{SOL - 92}Q{tx + 26},{SOL - 80} {tx + 20},{SOL - 6}Z", BOIS)
    t.trait(f"M{tx - 8},{SOL - 76}Q{tx - 12},{SOL - 40} {tx - 8},{SOL - 14}", BOIS_CLAIR, 4)
    t.forme(ellipse(165, SOL + 22, 70, 16), GRES)
    t.forme(ellipse(165, SOL + 14, 62, 10), assombrir(GRES, 0.9), None)
    for i in range(7):
        grain_cafe(t, 125 + i * 13, SOL + 10 + (i % 2) * 6, 11, -30 + i * 20)
    t.texte("18 g", "serif-italique", 30, 165, SOL + 76, ENCRE, "middle")
    vapeur(t, cx, SOL - 140, 70, 26)
    return t.svg("Espresso : porte-filtre, tasse sur la balance (36 g)")


def scene_v60() -> str:
    fond = "#ECDDC5"
    t = Toile(L, H, graine=102)
    decor(t, fond, "fives")
    cx = 540
    ombre(t, cx - 90, cx + 90, SOL, 150)
    # Carafe en verre.
    carafe = (f"M{cx - 64},{SOL - 190}Q{cx - 100},{SOL - 120} {cx - 92},{SOL - 50}Q{cx - 86},{SOL} {cx - 40},{SOL}"
              f"H{cx + 40}Q{cx + 86},{SOL} {cx + 92},{SOL - 50}Q{cx + 100},{SOL - 120} {cx + 64},{SOL - 190}Z")
    t.forme(carafe, VERRE, None, opacite=0.75)
    liquide = (f"M{cx - 92},{SOL - 70}Q{cx},{SOL - 64} {cx + 92},{SOL - 70}Q{cx + 86},{SOL} {cx + 40},{SOL}H{cx - 40}"
               f"Q{cx - 86},{SOL} {cx - 92},{SOL - 70}Z")
    t.forme(liquide, "#5A3420", None)
    t.trait(f"M{cx - 70},{SOL - 160}Q{cx - 86},{SOL - 110} {cx - 76},{SOL - 70}", BLANC, 5, 0.8)
    t.forme(carafe)
    t.forme(f"M{cx + 70},{SOL - 160}C{cx + 140},{SOL - 160} {cx + 140},{SOL - 70} {cx + 86},{SOL - 70}", "none", ENCRE, 14)
    t.forme(f"M{cx + 70},{SOL - 160}C{cx + 140},{SOL - 160} {cx + 140},{SOL - 70} {cx + 86},{SOL - 70}", "none", "#C9D5D0", 7)
    # Le cône V60 en céramique blanche.
    t.forme(rect_arrondi(cx - 96, SOL - 204, 192, 18, 8), BLANC)
    cone = (f"M{cx - 34},{SOL - 204}L{cx - 116},{SOL - 330}Q{cx - 118},{SOL - 342} {cx - 104},{SOL - 342}H{cx + 104}"
            f"Q{cx + 118},{SOL - 342} {cx + 116},{SOL - 330}L{cx + 34},{SOL - 204}Z")
    t.forme(cone, BLANC, None)
    for k in range(5):
        x = cx - 60 + k * 30
        t.trait(f"M{x},{SOL - 330}Q{x + 18},{SOL - 270} {x + 6},{SOL - 214}", "#E2D8C8", 3)
    t.hachures(polygone([(cx + 50, SOL - 342), (cx + 120, SOL - 342), (cx + 34, SOL - 204), (cx + 10, SOL - 204)]),
               pas=6, opacite=0.25)
    t.forme(cone)
    t.forme(ellipse(cx, SOL - 342, 104, 10), "#F5EBDD")
    plis = " ".join(f"L{cx - 96 + i * 12},{SOL - 352 - (i % 2) * 8}" for i in range(17))
    t.forme(f"M{cx - 96},{SOL - 342}{plis}L{cx + 96},{SOL - 342}Z", "#FBF4E8", ENCRE, 1.8)
    # Bouilloire col de cygne.
    bx, by = 250, SOL
    ombre(t, bx - 90, bx + 90, by, 120)
    corps = (f"M{bx - 92},{by - 20}Q{bx - 100},{by - 150} {bx},{by - 160}Q{bx + 100},{by - 150} {bx + 92},{by - 20}"
             f"Q{bx + 90},{by} {bx + 60},{by}H{bx - 60}Q{bx - 90},{by} {bx - 92},{by - 20}Z")
    bec = f"M{bx + 70},{by - 50}C{bx + 150},{by - 40} {bx + 130},{by - 200} {bx + 236},{by - 224}"
    t.forme(bec, "none", ENCRE, 15)
    t.forme(bec, "none", "#4A4440", 9)
    t.forme(corps, "#4A4440", None)
    t.hachures(polygone([(bx + 30, by - 170), (bx + 100, by - 170), (bx + 100, by), (bx + 20, by)]), pas=6,
               couleur=BLANC, opacite=0.18)
    t.forme(corps)
    t.trait(f"M{bx - 60},{by - 120}Q{bx - 74},{by - 80} {bx - 64},{by - 40}", "#8D857E", 6)
    t.forme(f"M{bx - 86},{by - 120}C{bx - 170},{by - 130} {bx - 170},{by - 30} {bx - 90},{by - 40}", "none", ENCRE, 17)
    t.forme(f"M{bx - 86},{by - 120}C{bx - 170},{by - 130} {bx - 170},{by - 30} {bx - 90},{by - 40}", "none", BOIS, 10)
    t.forme(ellipse(bx, by - 158, 54, 12), "#5A534E")
    t.forme(rect_arrondi(bx - 12, by - 188, 24, 26, 8), BOIS)
    # Filet d'eau.
    t.trait(f"M{bx + 238},{by - 222}Q{bx + 262},{by - 210} {cx - 10},{SOL - 330}", "#9CC0CF", 3.4)
    vapeur(t, cx + 30, SOL - 380, 60, 22, 2)
    grains_epars(t, [(700, 820, SOL + 20, SOL + 70), (60, 120, SOL + 30, SOL + 80)], 5, 12)
    return t.svg("V60 sur sa carafe, bouilloire col de cygne qui verse")


def scene_cold_brew() -> str:
    fond = "#E2DCCB"
    t = Toile(L, H, graine=103)
    decor(t, fond)
    cx = 400
    ombre(t, cx - 120, cx + 120, SOL, 170)
    # Bocal à joint orange (fermeture à étrier).
    bocal = (f"M{cx - 110},{SOL - 270}Q{cx - 124},{SOL - 250} {cx - 122},{SOL - 220}V{SOL - 30}Q{cx - 122},{SOL} {cx - 90},{SOL}"
             f"H{cx + 90}Q{cx + 122},{SOL} {cx + 122},{SOL - 30}V{SOL - 220}Q{cx + 124},{SOL - 250} {cx + 110},{SOL - 270}Z")
    t.forme(bocal, VERRE, None, opacite=0.7)
    ident = t.ident("bocal")
    t.defs.append(f'<clipPath id="{ident}"><path d="{bocal}"/></clipPath>')
    t.brut(f'<g clip-path="url(#{ident})">')
    t.forme(f"M{cx - 130},{SOL - 210}Q{cx},{SOL - 200} {cx + 130},{SOL - 210}V{SOL + 4}H{cx - 130}Z", "#3E2416", None)
    t.forme(f"M{cx - 130},{SOL - 60}Q{cx - 40},{SOL - 74} {cx + 30},{SOL - 58}T{cx + 130},{SOL - 64}V{SOL + 4}H{cx - 130}Z",
            "#6B4A35", None)
    for _ in range(60):
        x = cx + t.hasard.uniform(-110, 110)
        y = SOL - t.hasard.uniform(6, 56)
        t.forme(cercle(x, y, t.hasard.uniform(1.4, 2.6)), "#8C6A50", None)
    t.brut("</g>")
    t.trait(f"M{cx - 96},{SOL - 236}V{SOL - 40}", BLANC, 7, 0.7)
    t.forme(bocal)
    t.forme(rect_arrondi(cx - 116, SOL - 284, 232, 18, 6), "#E07B3C")
    couvercle = (f"M{cx - 112},{SOL - 284}Q{cx - 110},{SOL - 330} {cx},{SOL - 334}Q{cx + 110},{SOL - 330} {cx + 112},{SOL - 284}Z")
    t.forme(couvercle, VERRE, None, opacite=0.75)
    t.forme(couvercle)
    t.trait(f"M{cx - 124},{SOL - 262}L{cx - 70},{SOL - 336}H{cx + 70}L{cx + 124},{SOL - 262}", ACIER_FONCE, 5)
    # Étiquette « 12 h » attachée par une ficelle.
    t.trait(f"M{cx + 100},{SOL - 272}Q{cx + 140},{SOL - 240} {cx + 150},{SOL - 190}", "#8A6D50", 2.4)
    t.groupe_debut(f"rotate(10 {cx + 150} {SOL - 190})")
    t.forme(f"M{cx + 124},{SOL - 190}H{cx + 176}V{SOL - 110}H{cx + 124}Z", KRAFT_CLAIR)
    t.forme(cercle(cx + 150, SOL - 178, 5), "none", ENCRE, 1.6)
    t.texte("12 h", "serif", 30, cx + 150, SOL - 132, ENCRE, "middle")
    t.groupe_fin()
    # Verre de cold brew avec glaçons.
    gx = 680
    ombre(t, gx - 56, gx + 56, SOL, 100)
    verre(t, gx, SOL, 112, 196, [(0.72, "#4A2C1B")])
    for i, (dx, dy) in enumerate(((-26, -150), (14, -136), (-6, -112))):
        t.forme(rect_arrondi(gx + dx - 18, SOL + dy - 18, 36, 36, 6), "#EEF3F2", ENCRE, 1.8, 0.9,
                extra=f'transform="rotate({12 * (i - 1)} {gx + dx} {SOL + dy})"')
    for dx, dy in ((-170, 30), (-120, 46), (130, 40)):
        t.forme(rect_arrondi(gx + dx - 15, SOL + dy - 15, 30, 30, 6), "#EEF3F2", ENCRE, 1.8, extra=f'transform="rotate(18 {gx + dx} {SOL + dy})"')
    grains_epars(t, [(110, 200, SOL + 20, SOL + 70)], 4, 13)
    return t.svg("Cold brew : bocal de café infusé à froid, étiquette 12 h, verre avec glaçons")


def scene_latte() -> str:
    fond = "#EFD9C2"
    t = Toile(L, H, graine=104)
    decor(t, fond)
    cx = 430
    ombre(t, cx - 80, cx + 80, SOL, 150)
    verre(t, cx, SOL, 150, 290, [(0.32, "#6B3E22"), (0.38, "#C99A6E"), (0.2, LAIT)])
    t.forme(ellipse(cx, SOL - 290 + 2, 70, 7), "#F9F1E6", None)
    # Spéculoos posé sur le bord du verre.
    t.groupe_debut(f"rotate(-18 {cx + 70} {SOL - 300})")
    t.forme(rect_arrondi(cx + 30, SOL - 336, 96, 60, 6), "#C47D3E")
    t.forme(rect_arrondi(cx + 40, SOL - 326, 76, 40, 4), "none", "#8A4D20", 3)
    t.groupe_fin()
    # Assiette de spéculoos et bâton de cannelle.
    px = 700
    t.forme(ellipse(px, SOL + 26, 120, 26), GRES)
    t.forme(ellipse(px, SOL + 22, 90, 16), assombrir(GRES, 0.93), None)
    produits.speculoos(t, px - 30, SOL + 40, 1.05, -8)
    produits.speculoos(t, px + 30, SOL + 30, 1.0, 10)
    t.forme(rect_arrondi(150, SOL + 20, 160, 18, 9), "#8E4F28", extra=f'transform="rotate(-8 230 {SOL + 29})"')
    t.trait(f"M160,{SOL + 30}L300,{SOL + 10}", "#B36F3D", 2.4)
    vapeur(t, cx, SOL - 320, 60, 24)
    grains_epars(t, [(150, 300, SOL + 50, SOL + 80)], 3, 12)
    return t.svg("Latte au spéculoos : verre de latte, biscuits spéculoos")


def scene_chicoree() -> str:
    fond = "#E7DAC1"
    t = Toile(L, H, graine=105)
    decor(t, fond, "fives")
    cx = 450
    ombre(t, cx - 110, cx + 110, SOL, 170)
    # Cafetière émaillée crème à liseré bleu.
    corps = (f"M{cx - 80},{SOL - 250}Q{cx - 130},{SOL - 120} {cx - 116},{SOL - 30}Q{cx - 110},{SOL} {cx - 80},{SOL}"
             f"H{cx + 80}Q{cx + 110},{SOL} {cx + 116},{SOL - 30}Q{cx + 130},{SOL - 120} {cx + 80},{SOL - 250}Z")
    bec = f"M{cx - 100},{SOL - 150}Q{cx - 150},{SOL - 170} {cx - 176},{SOL - 236}L{cx - 160},{SOL - 242}Q{cx - 130},{SOL - 196} {cx - 96},{SOL - 196}Z"
    t.forme(bec, "#F1E7D3")
    anse = f"M{cx + 96},{SOL - 220}C{cx + 190},{SOL - 230} {cx + 190},{SOL - 70} {cx + 112},{SOL - 60}"
    t.forme(anse, "none", ENCRE, 20)
    t.forme(anse, "none", NUIT, 13)
    t.forme(corps, "#F1E7D3", None)
    t.forme(f"M{cx - 116},{SOL - 30}Q{cx - 110},{SOL} {cx - 80},{SOL}H{cx + 80}Q{cx + 110},{SOL} {cx + 116},{SOL - 30}"
            f"L{cx + 118},{SOL - 44}H{cx - 118}Z", NUIT, None)
    for _ in range(26):
        x = cx + t.hasard.uniform(-90, 90)
        y = SOL - t.hasard.uniform(60, 230)
        t.forme(cercle(x, y, t.hasard.uniform(1.2, 2.2)), "#7C8299", None, opacite=0.7)
    t.hachures(polygone([(cx + 40, SOL - 250), (cx + 140, SOL - 250), (cx + 120, SOL), (cx + 40, SOL)]), pas=6, opacite=0.25)
    t.forme(corps)
    t.forme(ellipse(cx, SOL - 252, 84, 14), NUIT)
    t.forme(f"M{cx - 70},{SOL - 256}Q{cx},{SOL - 300} {cx + 70},{SOL - 256}Z", "#F1E7D3")
    t.forme(cercle(cx, SOL - 290, 12), NUIT)
    # Bol de café-chicorée.
    bx = 700
    ombre(t, bx - 80, bx + 80, SOL + 20, 110)
    bol = f"M{bx - 92},{SOL - 70}Q{bx - 90},{SOL + 20} {bx},{SOL + 22}Q{bx + 90},{SOL + 20} {bx + 92},{SOL - 70}Z"
    t.forme(bol, BRIQUE, None)
    t.hachures(polygone([(bx + 30, SOL - 70), (bx + 100, SOL - 70), (bx + 90, SOL + 30), (bx + 20, SOL + 30)]), pas=6, opacite=0.28)
    t.forme(bol)
    t.forme(ellipse(bx, SOL - 70, 92, 16), "#C9693F")
    t.forme(ellipse(bx, SOL - 68, 80, 11), "#4A2A18", None)
    vapeur(t, bx, SOL - 96, 60, 24)
    # Boîte de chicorée en kraft.
    kx = 175
    ombre(t, kx - 70, kx + 70, SOL + 10, 90)
    t.forme(polygone([(kx + 70, SOL - 180), (kx + 100, SOL - 196), (kx + 100, SOL - 6), (kx + 70, SOL + 10)]), KRAFT_FONCE)
    t.forme(polygone([(kx - 70, SOL - 180), (kx - 40, SOL - 196), (kx + 100, SOL - 196), (kx + 70, SOL - 180)]), KRAFT_CLAIR)
    face = rect_arrondi(kx - 70, SOL - 180, 140, 190, 2)
    t.forme(face, KRAFT)
    fibres(t, face, (kx - 70, SOL - 180, kx + 70, SOL + 10), KRAFT_FONCE, 30)
    t.forme(face)
    t.forme(rect_arrondi(kx - 54, SOL - 130, 108, 92, 3), "#E9C443")
    t.texte("Chicorée", "serif-italique", 30, kx, SOL - 84, ENCRE, "middle")
    t.texte("DU NORD", "sans-700", 11, kx, SOL - 62, ENCRE, "middle", 0.2)
    return t.svg("Café-chicorée à l'ancienne : cafetière émaillée, bol et boîte de chicorée")


def scene_affogato() -> str:
    fond = "#EBD3C7"
    t = Toile(L, H, graine=106)
    decor(t, fond)
    cx = 420
    ombre(t, cx - 70, cx + 70, SOL, 140)
    # Coupe sur pied.
    t.forme(ellipse(cx, SOL - 4, 66, 12), VERRE)
    t.forme(rect_arrondi(cx - 8, SOL - 110, 16, 106, 6), VERRE)
    coupe = f"M{cx - 120},{SOL - 250}Q{cx - 116},{SOL - 110} {cx},{SOL - 104}Q{cx + 116},{SOL - 110} {cx + 120},{SOL - 250}Z"
    t.forme(coupe, VERRE, None, opacite=0.75)
    t.forme(f"M{cx - 112},{SOL - 200}Q{cx},{SOL - 186} {cx + 112},{SOL - 200}Q{cx + 100},{SOL - 112} {cx},{SOL - 108}"
            f"Q{cx - 100},{SOL - 112} {cx - 112},{SOL - 200}Z", "#6A3A20", None)
    t.forme(coupe)
    # Boule de glace vanille et café versé.
    boule = (f"M{cx - 92},{SOL - 236}Q{cx - 100},{SOL - 330} {cx},{SOL - 336}Q{cx + 100},{SOL - 330} {cx + 92},{SOL - 236}"
             f"Q{cx + 60},{SOL - 220} {cx + 30},{SOL - 236}Q{cx},{SOL - 216} {cx - 30},{SOL - 236}Q{cx - 60},{SOL - 220} {cx - 92},{SOL - 236}Z")
    t.forme(boule, "#F6EAD0")
    for _ in range(18):
        x = cx + t.hasard.uniform(-60, 60)
        y = SOL - t.hasard.uniform(250, 316)
        t.forme(ellipse(x, y, 2.2, 1.2), "#3A2416", None)
    coulee = (f"M{cx - 20},{SOL - 336}Q{cx + 10},{SOL - 300} {cx + 6},{SOL - 270}Q{cx + 4},{SOL - 250} {cx + 20},{SOL - 240}"
              f"Q{cx + 30},{SOL - 260} {cx + 28},{SOL - 290}Q{cx + 24},{SOL - 320} {cx + 10},{SOL - 336}Z")
    t.forme(coulee, "#4A2A18")
    # Tasse d'espresso qui verse.
    t.groupe_debut(f"rotate(-38 {cx + 40} {SOL - 400})")
    t.forme(rect_arrondi(cx + 0, SOL - 440, 84, 64, 14), BLANC)
    t.forme(f"M{cx + 84},{SOL - 424}c34,0 34,36 0,36", "none", ENCRE, 12)
    t.forme(f"M{cx + 84},{SOL - 424}c34,0 34,36 0,36", "none", BLANC, 6)
    t.groupe_fin()
    t.trait(f"M{cx - 6},{SOL - 380}Q{cx - 14},{SOL - 356} {cx - 14},{SOL - 336}", "#4A2A18", 6)
    # Cafetière italienne (moka) à droite.
    mx = 700
    ombre(t, mx - 70, mx + 70, SOL, 110)
    bas = polygone([(mx - 70, SOL), (mx - 56, SOL - 120), (mx + 56, SOL - 120), (mx + 70, SOL)])
    t.forme(bas, ACIER)
    t.forme(rect_arrondi(mx - 64, SOL - 134, 128, 18, 4), ACIER_FONCE)
    haut = polygone([(mx - 56, SOL - 134), (mx - 70, SOL - 260), (mx + 70, SOL - 260), (mx + 56, SOL - 134)])
    t.forme(haut, ACIER)
    t.hachures(polygone([(mx + 20, SOL - 260), (mx + 70, SOL - 260), (mx + 70, SOL), (mx + 20, SOL)]), pas=6, opacite=0.3)
    t.forme(bas)
    t.forme(haut)
    t.trait(f"M{mx - 30},{SOL - 250}V{SOL - 146}M{mx - 32},{SOL - 110}V{SOL - 10}", ACIER_CLAIR, 5)
    t.forme(polygone([(mx - 70, SOL - 260), (mx - 106, SOL - 274), (mx - 64, SOL - 244)]), ACIER)
    t.forme(f"M{mx - 70},{SOL - 274}Q{mx},{SOL - 300} {mx + 70},{SOL - 274}Z", ACIER_FONCE)
    t.forme(cercle(mx, SOL - 292, 9), "#1E1A17")
    t.forme(f"M{mx + 66},{SOL - 240}C{mx + 130},{SOL - 236} {mx + 124},{SOL - 150} {mx + 60},{SOL - 156}", "none", ENCRE, 20)
    t.forme(f"M{mx + 66},{SOL - 240}C{mx + 130},{SOL - 236} {mx + 124},{SOL - 150} {mx + 60},{SOL - 156}", "none", "#2B2623", 13)
    # Cuillère.
    t.forme(f"M120,{SOL + 50}L270,{SOL + 26}", "none", ENCRE, 9)
    t.forme(f"M120,{SOL + 50}L270,{SOL + 26}", "none", ACIER, 4)
    t.forme(ellipse(290, SOL + 22, 26, 12), ACIER, extra=f'transform="rotate(-9 290 {SOL + 22})"')
    vapeur(t, mx, SOL - 320, 50, 22, 2)
    return t.svg("Affogato : boule de glace vanille arrosée d'espresso, cafetière italienne")


def scenes() -> dict[str, str]:
    """Toutes les illustrations recettes, indexées par slug de recette."""
    return {
        "espresso-le-reglage-de-base": scene_espresso(),
        "v60-du-dimanche": scene_v60(),
        "cold-brew-12-heures": scene_cold_brew(),
        "latte-au-speculoos": scene_latte(),
        "cafe-chicoree-a-lancienne": scene_chicoree(),
        "affogato-du-vieux-lille": scene_affogato(),
    }
