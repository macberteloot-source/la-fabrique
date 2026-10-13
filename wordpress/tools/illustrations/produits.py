"""Illustrations des produits (format portrait 4:5, viewBox 800 × 1000).

Mise en scène commune : le mur de l'atelier, une grande fenêtre en arc de la filature
(avec un repère lillois en silhouette pour chaque café), un plan de travail. Au premier
plan : le sac kraft du café et un ou deux accessoires qui illustrent ses notes.
"""

from __future__ import annotations

from primitives import (
    ACIER, ACIER_CLAIR, ACIER_FONCE, ARGILE, BLANC, briques, BOIS, BOIS_CLAIR, BRIQUE, CAFE_LIQUIDE,
    ENCRE, EP, GRAIN, GRES, KRAFT, KRAFT_CLAIR, KRAFT_FONCE, VERT, Toile, assombrir, cercle,
    ellipse, etiquette_ronde, fibres, grain_cafe, grains_epars, melanger, n, polygone,
    rect_arrondi, toits_sheds, vapeur,
)
import typo

L, H = 800, 1000
HORIZON = 778   # bord arrière du plan de travail
SOL = 868       # ligne de pose des objets
VERT_FEUILLE = "#6F8A5B"

CAFES = {
    "wazemmes": {
        "nom": "Wazemmes", "origine": "Brésil & Inde", "detail": "Nature · 1 100 m",
        "notes": "chocolat noir, noisette", "accent": BRIQUE, "fond": "#E8D2BB",
        "torref": 3, "vue": "wazemmes",
    },
    "beffroi": {
        "nom": "Beffroi", "origine": "Éthiopie · Yirgacheffe", "detail": "Lavé · 1 950 m",
        "notes": "jasmin, bergamote, pêche", "accent": "#C0812A", "fond": "#EFDDC3",
        "torref": 1, "vue": "beffroi",
    },
    "vieux-lille": {
        "nom": "Vieux-Lille", "origine": "Colombie · Huila", "detail": "Lavé · 1 700 m",
        "notes": "caramel, pomme rouge, cacao", "accent": "#8C2E2A", "fond": "#ECD1C1",
        "torref": 2, "vue": "vieux-lille",
    },
    "fives": {
        "nom": "Fives", "origine": "Pérou & Honduras", "detail": "Lavé · 1 400 m",
        "notes": "biscuit, amande, sucre roux", "accent": VERT, "fond": "#E3DCC6",
        "torref": 2, "vue": "fives",
    },
    "braderie": {
        "nom": "La Braderie", "origine": "Kenya · Nyeri", "detail": "Lavé · 1 800 m",
        "notes": "cassis, pamplemousse rose", "accent": "#7A2B4A", "fond": "#ECD2D0",
        "torref": 1, "vue": "braderie", "pastille": [("Édition", "serif-italique"), ("limitée", "serif-italique")],
    },
    "nuit-blanche": {
        "nom": "Nuit Blanche", "origine": "Mexique", "detail": "Déca à l'eau · 1 300 m",
        "notes": "cacao, pain d'épices", "accent": "#2E3557", "fond": "#DCD3D3",
        "torref": 2, "vue": "nuit", "pastille": [("Déca", "serif-italique")],
    },
}


# ---------------------------------------------------------------------------
# Décor
# ---------------------------------------------------------------------------

def decor(t: Toile, fond: str, vue: str | None = None) -> None:
    """Mur, fenêtre en arc avec une vue, plan de travail."""
    t.forme(f"M0,0H{L}V{H}H0Z", fond, None)
    briques(t, fond, L, HORIZON)
    x0, x1, haut = 128, 672, 96
    rayon = (x1 - x0) / 2
    arche = (f"M{x0},{HORIZON}V{haut + rayon}A{rayon},{rayon} 0 0 1 {x1},{haut + rayon}"
             f"V{HORIZON}Z")
    ciel = melanger(fond, BLANC, 0.5) if vue != "nuit" else "#3A4166"
    t.forme(arche, ciel, None)
    ident = t.ident("arche")
    t.defs.append(f'<clipPath id="{ident}"><path d="{arche}"/></clipPath>')
    t.brut(f'<g clip-path="url(#{ident})">')
    silhouette = assombrir(fond, 0.9)
    if vue:
        VUES[vue](t, silhouette, x0, x1)
    t.brut("</g>")
    # Menuiserie de la fenêtre.
    cadre = assombrir(fond, 0.8)
    lignes = [f"M{x0 + (x1 - x0) * i / 4},{haut + 40 if i == 2 else haut + 62}V{HORIZON}" for i in (1, 2, 3)]
    lignes += [f"M{x0},{haut + rayon}H{x1}", f"M{x0},{haut + rayon + 210}H{x1}"]
    t.trait(" ".join(lignes), cadre, 7)
    t.trait(arche, cadre, 12)
    # Plan de travail.
    plan = assombrir(fond, 0.86)
    t.forme(f"M0,{HORIZON}H{L}V{H}H0Z", plan, None)
    t.trait(f"M0,{HORIZON}H{L}", ENCRE, EP)
    t.trait(f"M0,{HORIZON + 14}H{L}", assombrir(fond, 0.78), 3)


def vue_wazemmes(t, c, x0, x1):
    # Le clocher de Saint-Pierre-Saint-Paul et les halles (toit en berceau).
    t.forme(f"M{x0},{HORIZON}V610H{x1}V{HORIZON}Z", c, None)
    t.forme(polygone([(150, 610), (150, 470), (166, 470), (166, 420), (188, 300), (210, 420), (210, 470),
                      (226, 470), (226, 610)]), c, None)
    t.forme(f"M240,610V560Q420,470 600,560V610Z", c, None)
    t.forme(f"M600,610V575Q636,548 672,575V610Z", c, None)
    t.forme(cercle(188, 440, 10), assombrir(c, 0.93), None)


def vue_beffroi(t, c, x0, x1):
    # Le beffroi de l'hôtel de ville (tour élancée et lanterne) et des toits bas.
    t.forme(f"M{x0},{HORIZON}V630L170,610V{HORIZON}Z", c, None)
    t.forme(polygone([(166, HORIZON), (166, 330), (158, 330), (158, 300), (176, 300), (176, 270), (186, 270),
                      (192, 200), (198, 270), (208, 270), (208, 300), (226, 300), (226, 330), (218, 330),
                      (218, HORIZON)]), c, None)
    t.forme(f"M226,{HORIZON}V620L300,596L380,620V605H{x1}V{HORIZON}Z", c, None)
    t.forme(cercle(192, 352, 11), assombrir(c, 0.93), None)
    for y in (420, 470, 520):
        t.forme(rect_arrondi(184, y, 16, 30, 8), assombrir(c, 0.93), None)


def vue_vieux_lille(t, c, x0, x1):
    # Rangée de façades flamandes à pignons à pas de moineau.
    x = x0
    hauteurs = [520, 560, 530, 575, 540, 515]
    largeur = (x1 - x0) / len(hauteurs)
    for i, h in enumerate(hauteurs):
        pts = [(x, HORIZON), (x, h + 40), (x + largeur * 0.2, h + 40), (x + largeur * 0.2, h + 20),
               (x + largeur * 0.35, h + 20), (x + largeur * 0.35, h), (x + largeur * 0.65, h),
               (x + largeur * 0.65, h + 20), (x + largeur * 0.8, h + 20), (x + largeur * 0.8, h + 40),
               (x + largeur, h + 40), (x + largeur, HORIZON)]
        t.forme(polygone(pts), assombrir(c, 1 - 0.02 * (i % 2)), None)
        for j in range(2):
            t.forme(rect_arrondi(x + largeur * 0.26 + j * largeur * 0.3, h + 70, largeur * 0.16, 32, 2),
                    assombrir(c, 0.93), None)
        x += largeur


def vue_fives(t, c, x0, x1):
    # Les sheds de l'ancienne filature et sa cheminée de briques.
    xs = [x0 + i * 68 for i in range(10)]
    t.forme(toits_sheds(xs, 640, 46) + f"V{HORIZON}H{x0}Z", c, None)
    t.forme(f"M{x0},640H{x1}V{HORIZON}H{x0}Z", c, None)
    t.forme(polygone([(170, 640), (178, 330), (204, 330), (212, 640)]), c, None)
    t.forme(rect_arrondi(172, 320, 38, 16, 2), c, None)
    for k, (fx, fy, r) in enumerate(((196, 296, 16), (214, 262, 21), (240, 222, 27))):
        t.forme(cercle(fx, fy, r), assombrir(c, 1.04), None, opacite=0.9)


def vue_braderie(t, c, x0, x1):
    # Guirlandes de fanions et étals.
    t.forme(f"M{x0},{HORIZON}V640L230,600L330,640V620L420,585L510,620V640L600,600L{x1},640V{HORIZON}Z", c, None)
    couleurs = [BRIQUE, "#C0812A", VERT, "#7A2B4A", BLANC]
    for k, (ya, courbe) in enumerate(((300, 360), (430, 480))):
        d = f"M{x0},{ya}Q400,{courbe} {x1},{ya}"
        t.trait(d, assombrir(c, 0.8), 2.4)
        for i in range(9):
            u = (i + 0.5) / 9
            px = (1 - u) ** 2 * x0 + 2 * (1 - u) * u * 400 + u ** 2 * x1
            py = (1 - u) ** 2 * ya + 2 * (1 - u) * u * courbe + u ** 2 * ya
            t.forme(polygone([(px - 16, py), (px + 16, py), (px, py + 34)]),
                    couleurs[(i + k) % len(couleurs)], ENCRE, 1.6, 0.9)


def vue_nuit(t, c, x0, x1):
    # Nuit blanche : ciel de nuit, lune et étoiles.
    lune = "#F3E2B6"
    t.forme(cercle(520, 250, 46), lune, None)
    t.forme(cercle(540, 236, 42), "#3A4166", None)
    for x, y, r in ((250, 220, 7), (330, 160, 5), (420, 300, 6), (600, 380, 5), (230, 420, 6),
                    (470, 190, 4), (360, 420, 4), (630, 200, 4)):
        t.forme(polygone([(x, y - r * 2), (x + r * 0.5, y - r * 0.5), (x + r * 2, y), (x + r * 0.5, y + r * 0.5),
                          (x, y + r * 2), (x - r * 0.5, y + r * 0.5), (x - r * 2, y), (x - r * 0.5, y - r * 0.5)]),
                lune, None)
    t.forme(f"M{x0},{HORIZON}V650L250,620L330,650V610L420,590L470,610V640H{x1}V{HORIZON}Z", "#2B3150", None)


VUES = {
    "wazemmes": vue_wazemmes, "beffroi": vue_beffroi, "vieux-lille": vue_vieux_lille,
    "fives": vue_fives, "braderie": vue_braderie, "nuit": vue_nuit,
}


# ---------------------------------------------------------------------------
# Le sac kraft
# ---------------------------------------------------------------------------

def sac(t: Toile, cx: float, base: float, info: dict, largeur: float = 290, hauteur: float = 520,
        poids: str = "250 g", echelle_etiquette: float = 1.0) -> None:
    x0, x1 = cx - largeur / 2, cx + largeur / 2
    haut = base - hauteur
    milieu = (base + haut) / 2
    # Ombre portée au sol (lumière venant de la gauche).
    ombre = polygone([(x0 + 30, base + 2), (x1 + 30, base - 12), (x1 + 170, base + 26), (x0 + 150, base + 44)])
    t.hachures(ombre, angle=-35, pas=8, opacite=0.32)
    # Soufflet droit.
    soufflet = (f"M{n(x1)},{n(base - 6)}Q{n(x1 + 10)},{n(milieu)} {n(x1 - 6)},{n(haut)}"
                f"L{n(x1 + 30)},{n(haut + 14)}Q{n(x1 + 44)},{n(milieu)} {n(x1 + 34)},{n(base - 16)}Z")
    t.forme(soufflet, KRAFT_FONCE)
    t.hachures(soufflet, angle=-50, pas=7, opacite=0.32)
    t.forme(soufflet)
    # Face avant.
    corps = (f"M{n(x0)},{n(base)}Q{n(x0 - 8)},{n(milieu)} {n(x0 + 6)},{n(haut)}L{n(x1 - 6)},{n(haut)}"
             f"Q{n(x1 + 10)},{n(milieu)} {n(x1)},{n(base - 6)}Q{n(cx)},{n(base + 10)} {n(x0)},{n(base)}Z")
    t.forme(corps, KRAFT, None)
    fibres(t, corps, (x0, haut, x1, base), KRAFT_FONCE, nombre=90)
    bande = (f"M{n(x1 - 40)},{n(haut)}L{n(x1 - 6)},{n(haut)}Q{n(x1 + 10)},{n(milieu)} {n(x1)},{n(base - 6)}"
             f"L{n(x1 - 38)},{n(base - 1)}Z")
    t.hachures(bande, angle=-50, pas=7, opacite=0.28)
    reflet = (f"M{n(x0 + 14)},{n(haut + 8)}L{n(x0 + 40)},{n(haut + 8)}L{n(x0 + 36)},{n(base - 8)}"
              f"L{n(x0 + 10)},{n(base - 6)}Z")
    t.forme(reflet, KRAFT_CLAIR, None, opacite=0.55)
    t.forme(corps)
    # Rabat roulé et attache métallique.
    rabat = (f"M{n(x0 + 6)},{n(haut)}L{n(x0 + 2)},{n(haut - 70)}Q{n(cx)},{n(haut - 78)} {n(x1 - 2)},{n(haut - 70)}"
             f"L{n(x1 - 6)},{n(haut)}Z")
    t.forme(polygone([(x1 - 2, haut - 70), (x1 + 24, haut - 52), (x1 + 30, haut + 14), (x1 - 6, haut)]), KRAFT_FONCE)
    t.forme(rabat, KRAFT_CLAIR)
    for k in (0.28, 0.62):
        xk = x0 + largeur * k
        t.trait(f"M{n(xk)},{n(haut - 70)}l6,70", KRAFT_FONCE, 2, 0.8)
    t.forme(rect_arrondi(x0 - 16, haut - 50, largeur + 32, 16, 8), ACIER)
    t.trait(f"M{n(x0 - 6)},{n(haut - 45)}H{n(x1 + 6)}", ACIER_CLAIR, 2.4)
    # Valve de dégazage.
    t.forme(cercle(cx, haut + 44, 15), "#EFE6D6")
    t.forme(cercle(cx, haut + 44, 6.5), "none", ENCRE, 1.8)
    # Étiquette.
    le = 228 * echelle_etiquette
    he = 296 * echelle_etiquette
    ex, ey = cx - le / 2, haut + 84
    etiquette(t, ex, ey, le, he, info, poids)
    if info.get("pastille"):
        etiquette_ronde(t, x1 - 6, haut + 38, 52, info["accent"], info["pastille"], angle=-14)


def etiquette(t: Toile, x: float, y: float, l: float, h: float, info: dict, poids: str) -> None:
    k = l / 228
    # L'espace d'Instrument Sans est étroite : on aère le point médian.
    info = {**info, "origine": info["origine"].replace(" · ", "  ·  "),
            "detail": info["detail"].replace(" · ", "  ·  ")}
    t.forme(rect_arrondi(x + 5, y + 6, l, h, 4), assombrir(KRAFT, 0.85), None)
    t.forme(rect_arrondi(x, y, l, h, 4), BLANC)
    cx = x + l / 2
    t.texte("LA FABRIQUE", "sans-700", 12.5 * k, cx, y + 30 * k, ENCRE, "middle", 0.24)
    t.trait(f"M{n(x + 18 * k)},{n(y + 44 * k)}H{n(x + l - 18 * k)}", ENCRE, 1.4)
    taille = typo.ajuster(info["nom"], "serif", l - 40 * k, 58 * k)
    t.texte(info["nom"], "serif", taille, cx, y + 102 * k, ENCRE, "middle")
    taille = typo.ajuster(info["origine"], "sans-600", l - 44 * k, 14 * k)
    t.texte(info["origine"], "sans-600", taille, cx, y + 130 * k, ENCRE, "middle")
    taille = typo.ajuster(info["detail"], "sans", l - 44 * k, 13 * k)
    t.texte(info["detail"], "sans", taille, cx, y + 150 * k, "#5A4D44", "middle")
    # Bandeau couleur.
    t.forme(f"M{n(x)},{n(y + 172 * k)}H{n(x + l)}V{n(y + 212 * k)}H{n(x)}Z", info["accent"], ENCRE, 1.6)
    t.texte("Lot n° 042", "sans-600", 14 * k, x + 16 * k, y + 197 * k, BLANC)
    t.texte(poids, "sans-600", 14 * k, x + l - 16 * k, y + 197 * k, BLANC, "end")
    # Torréfaction : quatre pastilles.
    t.texte("Torréfaction", "sans-500", 12 * k, x + 16 * k, y + 242 * k, "#5A4D44")
    for i in range(4):
        px = x + l - 22 * k - (3 - i) * 20 * k
        plein = i < info.get("torref", 2)
        t.forme(cercle(px, y + 238 * k, 6.5 * k), info["accent"] if plein else BLANC, ENCRE, 1.5)
    taille = typo.ajuster(info["notes"], "serif-italique", l - 36 * k, 19 * k)
    t.texte(info["notes"], "serif-italique", taille, cx, y + 276 * k, ENCRE, "middle")


# ---------------------------------------------------------------------------
# Accessoires de dégustation (ils illustrent les notes)
# ---------------------------------------------------------------------------

def noisette(t, x, y, s=1.0):
    d = (f"M{n(x)},{n(y - 62 * s)}C{n(x + 18 * s)},{n(y - 52 * s)} {n(x + 30 * s)},{n(y - 36 * s)} {n(x + 28 * s)},{n(y - 22 * s)}"
         f"C{n(x + 26 * s)},{n(y - 6 * s)} {n(x + 14 * s)},{n(y)} {n(x)},{n(y)}"
         f"C{n(x - 14 * s)},{n(y)} {n(x - 26 * s)},{n(y - 6 * s)} {n(x - 28 * s)},{n(y - 22 * s)}"
         f"C{n(x - 30 * s)},{n(y - 36 * s)} {n(x - 18 * s)},{n(y - 52 * s)} {n(x)},{n(y - 62 * s)}Z")
    t.forme(d, "#A8683A", None)
    t.forme(ellipse(x, y - 10 * s, 18 * s, 9 * s), "#D7AE7E", None)
    t.hachures(polygone([(x + 6 * s, y - 60 * s), (x + 34 * s, y - 40 * s), (x + 30 * s, y), (x + 8 * s, y)]),
               angle=-50, pas=6, opacite=0.3)
    t.trait(f"M{n(x - 8 * s)},{n(y - 50 * s)}Q{n(x - 16 * s)},{n(y - 34 * s)} {n(x - 12 * s)},{n(y - 18 * s)}", "#C98B55", 2)
    t.forme(d)


def carre_chocolat(t, x, y, s=1.0):
    dessus = [(x - 56 * s, y - 24 * s), (x + 16 * s, y - 38 * s), (x + 58 * s, y - 20 * s), (x - 14 * s, y - 6 * s)]
    face_g = [(x - 56 * s, y - 24 * s), (x - 14 * s, y - 6 * s), (x - 14 * s, y + 6 * s), (x - 56 * s, y - 12 * s)]
    face_d = [(x - 14 * s, y - 6 * s), (x + 58 * s, y - 20 * s), (x + 58 * s, y - 8 * s), (x - 14 * s, y + 6 * s)]
    t.forme(polygone(face_g), "#3D2217")
    t.forme(polygone(face_d), "#2E1910")
    t.forme(polygone(dessus), "#5B3423")
    a, b, c, d = dessus
    lignes = []
    for k in (1 / 3, 2 / 3):
        p = (a[0] + (b[0] - a[0]) * k, a[1] + (b[1] - a[1]) * k)
        q = (d[0] + (c[0] - d[0]) * k, d[1] + (c[1] - d[1]) * k)
        lignes.append(f"M{n(p[0])},{n(p[1])}L{n(q[0])},{n(q[1])}")
    p = ((a[0] + d[0]) / 2, (a[1] + d[1]) / 2)
    q = ((b[0] + c[0]) / 2, (b[1] + c[1]) / 2)
    lignes.append(f"M{n(p[0])},{n(p[1])}L{n(q[0])},{n(q[1])}")
    t.trait(" ".join(lignes), "#2A160D", 2)
    t.trait(f"M{n(a[0] + 8 * s)},{n(a[1] - 1 * s)}L{n(b[0] - 10 * s)},{n(b[1] + 2 * s)}", "#8A5A40", 2, 0.8)


def peche(t, x, y, s=1.0):
    cy = y - 36 * s
    t.forme(cercle(x, cy, 36 * s), "#EFA56E", None)
    joue = (f"M{n(x + 4 * s)},{n(cy - 35 * s)}A{n(36 * s)},{n(36 * s)} 0 0 1 {n(x + 10 * s)},{n(cy + 35 * s)}"
            f"Q{n(x + 22 * s)},{n(cy)} {n(x + 4 * s)},{n(cy - 35 * s)}Z")
    t.forme(joue, "#DC7350", None)
    t.hachures(cercle(x + 18 * s, cy + 14 * s, 26 * s), angle=-50, pas=6, opacite=0.25)
    t.forme(cercle(x, cy, 36 * s))
    t.trait(f"M{n(x + 2 * s)},{n(cy - 34 * s)}Q{n(x - 10 * s)},{n(cy - 6 * s)} {n(x + 4 * s)},{n(cy + 20 * s)}", ENCRE, 2)
    t.trait(f"M{n(x)},{n(cy - 34 * s)}l4,-14", BOIS, 3)
    feuille = (f"M{n(x + 4 * s)},{n(cy - 46 * s)}Q{n(x + 26 * s)},{n(cy - 66 * s)} {n(x + 46 * s)},{n(cy - 52 * s)}"
               f"Q{n(x + 24 * s)},{n(cy - 34 * s)} {n(x + 4 * s)},{n(cy - 46 * s)}Z")
    t.forme(feuille, VERT_FEUILLE)
    t.trait(f"M{n(x + 8 * s)},{n(cy - 46 * s)}Q{n(x + 26 * s)},{n(cy - 52 * s)} {n(x + 40 * s)},{n(cy - 52 * s)}", ENCRE, 1.4)


def jasmin(t, x, y, s=1.0):
    t.trait(f"M{n(x - 50 * s)},{n(y)}Q{n(x)},{n(y - 16 * s)} {n(x + 46 * s)},{n(y - 6 * s)}", VERT, 3)
    for fx, fy, ang in ((x - 30 * s, y - 8 * s, 20), (x + 24 * s, y - 12 * s, -10)):
        feuille = (f"M{n(fx)},{n(fy)}q{n(10 * s)},{n(-22 * s)} {n(26 * s)},{n(-24 * s)}"
                   f"q{n(-4 * s)},{n(18 * s)} {n(-26 * s)},{n(24 * s)}Z")
        t.forme(feuille, VERT_FEUILLE, ENCRE, 1.8, extra=f'transform="rotate({ang} {n(fx)} {n(fy)})"')
    for fx, fy, r in ((x - 6 * s, y - 30 * s, 1.0), (x + 40 * s, y - 34 * s, 0.8)):
        for i in range(5):
            ang = i * 72
            petale = ellipse(fx, fy - 11 * s * r, 6.5 * s * r, 11 * s * r)
            t.forme(petale, BLANC, ENCRE, 1.6, extra=f'transform="rotate({ang} {n(fx)} {n(fy)})"')
        t.forme(cercle(fx, fy, 4.5 * s * r), "#E2B850", ENCRE, 1.4)


def pomme(t, x, y, s=1.0):
    d = (f"M{n(x)},{n(y - 58 * s)}C{n(x + 14 * s)},{n(y - 70 * s)} {n(x + 42 * s)},{n(y - 64 * s)} {n(x + 42 * s)},{n(y - 34 * s)}"
         f"C{n(x + 42 * s)},{n(y - 10 * s)} {n(x + 22 * s)},{n(y)} {n(x + 8 * s)},{n(y - 2 * s)}"
         f"C{n(x + 3 * s)},{n(y - 1 * s)} {n(x - 3 * s)},{n(y - 1 * s)} {n(x - 8 * s)},{n(y - 2 * s)}"
         f"C{n(x - 22 * s)},{n(y)} {n(x - 42 * s)},{n(y - 10 * s)} {n(x - 42 * s)},{n(y - 34 * s)}"
         f"C{n(x - 42 * s)},{n(y - 64 * s)} {n(x - 14 * s)},{n(y - 70 * s)} {n(x)},{n(y - 58 * s)}Z")
    t.forme(d, "#B5372D", None)
    t.hachures(polygone([(x + 10 * s, y - 70 * s), (x + 50 * s, y - 50 * s), (x + 40 * s, y), (x + 4 * s, y)]),
               angle=-50, pas=6, opacite=0.3)
    t.trait(f"M{n(x - 28 * s)},{n(y - 46 * s)}Q{n(x - 32 * s)},{n(y - 30 * s)} {n(x - 24 * s)},{n(y - 18 * s)}", "#DB6C5F", 4)
    t.forme(d)
    t.trait(f"M{n(x)},{n(y - 58 * s)}q2,-10 6,-18", BOIS, 3.2)
    feuille = (f"M{n(x + 4 * s)},{n(y - 70 * s)}Q{n(x + 20 * s)},{n(y - 90 * s)} {n(x + 40 * s)},{n(y - 80 * s)}"
               f"Q{n(x + 22 * s)},{n(y - 64 * s)} {n(x + 4 * s)},{n(y - 70 * s)}Z")
    t.forme(feuille, VERT_FEUILLE)


def caramel(t, x, y, s=1.0):
    for dx, dy, k in ((0, 0, 1.0), (40 * s, 8 * s, 0.85)):
        cx, cy, c = x + dx, y + dy, s * k
        dessus = [(cx - 22 * c, cy - 30 * c), (cx + 4 * c, cy - 38 * c), (cx + 26 * c, cy - 30 * c), (cx, cy - 22 * c)]
        gauche = [(cx - 22 * c, cy - 30 * c), (cx, cy - 22 * c), (cx, cy + 2 * c), (cx - 22 * c, cy - 6 * c)]
        droite = [(cx, cy - 22 * c), (cx + 26 * c, cy - 30 * c), (cx + 26 * c, cy - 6 * c), (cx, cy + 2 * c)]
        t.forme(polygone(gauche), "#C17A33")
        t.forme(polygone(droite), "#A2622A")
        t.hachures(polygone(droite), angle=-50, pas=5, opacite=0.3)
        t.forme(polygone(droite))
        t.forme(polygone(dessus), "#DDA25A")


def speculoos(t, x, y, s=1.0, angle=0):
    """Biscuit posé à plat : dessiné de face puis aplati par une transformation."""
    t.groupe_debut(f"translate({n(x)} {n(y)}) rotate({angle}) skewX(-28) scale(1 0.5)")
    t.forme(rect_arrondi(-38 * s, -54 * s, 76 * s, 50 * s, 5 * s), "#9C5A26", ENCRE, EP * 1.4)
    t.forme(rect_arrondi(-38 * s, -66 * s, 76 * s, 50 * s, 5 * s), "#C47D3E", ENCRE, EP * 1.6)
    t.forme(rect_arrondi(-30 * s, -58 * s, 60 * s, 34 * s, 3 * s), "none", "#8A4D20", 3)
    cx, cy = 0, -41 * s
    for ang in (0, 90, 180, 270):
        t.forme(polygone([(cx, cy), (cx + 12 * s, cy - 4 * s), (cx + 12 * s, cy + 4 * s)]), "#A8622A", None,
                extra=f'transform="rotate({ang} {n(cx)} {n(cy)})"')
    t.groupe_fin()


def amande(t, x, y, s=1.0, angle=0):
    t.groupe_debut(f"rotate({angle} {n(x)} {n(y)})")
    d = (f"M{n(x - 24 * s)},{n(y)}C{n(x - 22 * s)},{n(y - 16 * s)} {n(x + 2 * s)},{n(y - 22 * s)} {n(x + 26 * s)},{n(y - 8 * s)}"
         f"C{n(x + 6 * s)},{n(y + 8 * s)} {n(x - 18 * s)},{n(y + 10 * s)} {n(x - 24 * s)},{n(y)}Z")
    t.forme(d, "#B7784A")
    for k in (-0.4, 0, 0.4):
        t.trait(f"M{n(x - 14 * s)},{n(y + k * 8 * s - 2 * s)}Q{n(x)},{n(y + k * 10 * s - 8 * s)} {n(x + 14 * s)},{n(y + k * 6 * s - 6 * s)}",
                "#8E5530", 1.4, 0.8)
    t.groupe_fin()


def pamplemousse(t, x, y, s=1.0):
    cy = y - 18 * s
    t.forme(ellipse(x, cy + 8 * s, 56 * s, 24 * s), "#D9785A")
    t.forme(ellipse(x, cy, 56 * s, 24 * s), "#E9956A")
    t.forme(ellipse(x, cy, 49 * s, 20.5 * s), "#FBE7D8", None)
    t.forme(ellipse(x, cy, 44 * s, 18 * s), "#E66F6E", None)
    rayons = []
    import math
    for i in range(10):
        a = i * math.pi / 5
        rayons.append(f"M{n(x)},{n(cy)}L{n(x + 44 * s * math.cos(a))},{n(cy + 18 * s * math.sin(a))}")
    t.trait(" ".join(rayons), "#FBE7D8", 2.4)
    t.forme(ellipse(x, cy, 6 * s, 3 * s), "#FBE7D8", None)
    t.forme(ellipse(x, cy, 56 * s, 24 * s))


def cassis(t, x, y, s=1.0):
    baies = [(-22, -10), (-6, -16), (10, -10), (-14, 6), (4, 2), (22, 4)]
    t.trait(f"M{n(x - 30 * s)},{n(y - 40 * s)}Q{n(x - 4 * s)},{n(y - 44 * s)} {n(x + 26 * s)},{n(y - 30 * s)}", VERT, 2.6)
    for bx, by in baies:
        t.trait(f"M{n(x + bx * s)},{n(y + by * s - 10 * s)}L{n(x + bx * 0.6 * s)},{n(y - 38 * s)}", VERT, 1.6)
    for bx, by in baies:
        cx, cy = x + bx * s, y + by * s
        t.forme(cercle(cx, cy, 10 * s), "#3B1D35")
        t.forme(cercle(cx - 3.5 * s, cy - 3.5 * s, 2.6 * s), "#9C7091", None)


def pain_epices(t, x, y, s=1.0):
    """Tranche de pain d'épices, debout et légèrement penchée."""
    t.groupe_debut(f"rotate(-8 {n(x)} {n(y)})")
    d = (f"M{n(x - 40 * s)},{n(y)}V{n(y - 52 * s)}Q{n(x - 40 * s)},{n(y - 74 * s)} {n(x)},{n(y - 76 * s)}"
         f"Q{n(x + 40 * s)},{n(y - 74 * s)} {n(x + 40 * s)},{n(y - 52 * s)}V{n(y)}Z")
    t.forme(d, "#8A4B21")
    interieur = (f"M{n(x - 32 * s)},{n(y - 6 * s)}V{n(y - 50 * s)}Q{n(x - 32 * s)},{n(y - 66 * s)} {n(x)},{n(y - 67 * s)}"
                 f"Q{n(x + 32 * s)},{n(y - 66 * s)} {n(x + 32 * s)},{n(y - 50 * s)}V{n(y - 6 * s)}Z")
    t.forme(interieur, "#C98A4A", None)
    for i in range(14):
        px = x + t.hasard.uniform(-26, 26) * s
        py = y - t.hasard.uniform(10, 58) * s
        t.forme(ellipse(px, py, 2.4 * s, 1.6 * s), "#9E5F2C", None)
    t.groupe_fin()


def cabosse(t, x, y, s=1.0):
    """Cabosse de cacao couchée, et deux fèves."""
    t.groupe_debut(f"rotate(-12 {n(x)} {n(y)})")
    d = (f"M{n(x - 70 * s)},{n(y - 20 * s)}C{n(x - 40 * s)},{n(y - 58 * s)} {n(x + 34 * s)},{n(y - 60 * s)} {n(x + 72 * s)},{n(y - 28 * s)}"
         f"C{n(x + 36 * s)},{n(y + 8 * s)} {n(x - 40 * s)},{n(y + 10 * s)} {n(x - 70 * s)},{n(y - 20 * s)}Z")
    t.forme(d, "#B0612F")
    for k in (-0.5, 0, 0.5):
        t.trait(f"M{n(x - 56 * s)},{n(y - 22 * s + k * 14 * s)}Q{n(x)},{n(y - 30 * s + k * 34 * s)} {n(x + 58 * s)},{n(y - 26 * s + k * 12 * s)}",
                "#6E3219", 3)
    t.hachures(d, angle=-50, pas=6, opacite=0.22)
    t.forme(d)
    t.groupe_fin()


# ---------------------------------------------------------------------------
# Scènes
# ---------------------------------------------------------------------------

ACCESSOIRES_NOTES = {
    "wazemmes": lambda t: (carre_chocolat(t, 170, SOL + 50, 1.5), noisette(t, 632, SOL + 40, 1.25),
                           noisette(t, 700, SOL + 66, 1.05)),
    "beffroi": lambda t: (peche(t, 170, SOL + 62, 1.55), jasmin(t, 650, SOL + 60, 1.5)),
    "vieux-lille": lambda t: (pomme(t, 168, SOL + 66, 1.45), caramel(t, 630, SOL + 64, 1.4)),
    "fives": lambda t: (speculoos(t, 148, SOL + 86, 1.5, -6), speculoos(t, 200, SOL + 54, 1.4, 4),
                        amande(t, 630, SOL + 50, 1.6, -20), amande(t, 700, SOL + 76, 1.45, 30)),
    "braderie": lambda t: (pamplemousse(t, 168, SOL + 70, 1.45), cassis(t, 650, SOL + 56, 1.5)),
    "nuit-blanche": lambda t: (pain_epices(t, 168, SOL + 66, 1.45), cabosse(t, 650, SOL + 66, 1.25)),
}


def scene_cafe(sku: str) -> str:
    info = CAFES[sku]
    t = Toile(L, H, graine=sum(map(ord, sku)))
    decor(t, info["fond"], info["vue"])
    sac(t, 400, SOL, info)
    ACCESSOIRES_NOTES[sku](t)
    grains_epars(t, [(270, 340, SOL + 46, SOL + 96), (470, 540, SOL + 60, SOL + 104)], 5, 17)
    return t.svg(f"{info['nom']} — sac de café La Fabrique")


def scene_dripper() -> str:
    fond = "#E2D6C6"
    t = Toile(L, H, graine=7)
    decor(t, fond, "fives")
    cx = 400
    # Ombre portée.
    t.hachures(polygone([(cx - 60, SOL + 8), (cx + 150, SOL - 6), (cx + 300, SOL + 30), (cx + 40, SOL + 48)]),
               angle=-35, pas=8, opacite=0.3)
    # Tasse en grès (support).
    tasse_haut, tasse_bas = SOL - 170, SOL
    tasse = (f"M{cx - 120},{tasse_haut}L{cx - 106},{tasse_bas - 20}Q{cx - 100},{tasse_bas} {cx - 76},{tasse_bas}"
             f"H{cx + 76}Q{cx + 100},{tasse_bas} {cx + 106},{tasse_bas - 20}L{cx + 120},{tasse_haut}Z")
    t.forme(f"M{cx + 112},{tasse_haut + 40}C{cx + 200},{tasse_haut + 30} {cx + 200},{tasse_haut + 130} {cx + 104},{tasse_haut + 120}",
            "none", ENCRE, 22)
    t.forme(f"M{cx + 112},{tasse_haut + 40}C{cx + 200},{tasse_haut + 30} {cx + 200},{tasse_haut + 130} {cx + 104},{tasse_haut + 120}",
            "none", BRIQUE, 15)
    t.forme(tasse, BRIQUE, None)
    t.forme(f"M{cx - 106},{tasse_bas - 60}H{cx + 106}L{cx + 106},{tasse_bas - 20}Q{cx + 100},{tasse_bas} {cx + 76},{tasse_bas}"
            f"H{cx - 76}Q{cx - 100},{tasse_bas} {cx - 106},{tasse_bas - 20}Z", ARGILE, None)
    t.hachures(polygone([(cx + 50, tasse_haut), (cx + 125, tasse_haut), (cx + 110, tasse_bas), (cx + 40, tasse_bas)]),
               angle=-50, pas=7, opacite=0.3)
    t.forme(tasse)
    t.trait(f"M{cx - 108},{tasse_bas - 60}Q{cx - 60},{tasse_bas - 52} {cx - 20},{tasse_bas - 62}T{cx + 108},{tasse_bas - 58}",
            ENCRE, 2)
    # Le dripper en grès.
    d_bas, d_haut = tasse_haut - 4, tasse_haut - 250
    pied = rect_arrondi(cx - 150, d_bas - 18, 300, 22, 10)
    t.forme(pied, GRES)
    cone = (f"M{cx - 64},{d_bas - 18}L{cx - 172},{d_haut + 26}Q{cx - 176},{d_haut} {cx - 150},{d_haut}"
            f"H{cx + 150}Q{cx + 176},{d_haut} {cx + 172},{d_haut + 26}L{cx + 64},{d_bas - 18}Z")
    anse = (f"M{cx + 150},{d_haut + 60}C{cx + 250},{d_haut + 50} {cx + 250},{d_haut + 170} {cx + 112},{d_haut + 168}")
    t.forme(anse, "none", ENCRE, 30)
    t.forme(anse, "none", GRES, 22)
    t.forme(cone, GRES, None)
    # Coulure d'émail brun au bord.
    coulure = (f"M{cx - 176},{d_haut + 10}Q{cx - 176},{d_haut} {cx - 150},{d_haut}H{cx + 150}Q{cx + 176},{d_haut} {cx + 176},{d_haut + 10}"
               f"L{cx + 168},{d_haut + 40}Q{cx + 140},{d_haut + 70} {cx + 120},{d_haut + 42}Q{cx + 80},{d_haut + 90} {cx + 50},{d_haut + 46}"
               f"Q{cx + 10},{d_haut + 70} {cx - 20},{d_haut + 44}Q{cx - 70},{d_haut + 96} {cx - 100},{d_haut + 48}"
               f"Q{cx - 140},{d_haut + 76} {cx - 168},{d_haut + 40}Z")
    t.forme(coulure, "#7E4A2E", None)
    for _ in range(26):
        px = cx + t.hasard.uniform(-110, 110)
        py = t.hasard.uniform(d_haut + 100, d_bas - 34)
        demi = 64 + (172 - 64) * (d_bas - 18 - py) / (d_bas - 18 - d_haut - 26)
        if abs(px - cx) < demi - 16:
            t.forme(cercle(px, py, t.hasard.uniform(1.0, 1.9)), "#A88C72", None, opacite=0.8)
    for k in (-1, 0, 1):
        t.trait(f"M{cx + k * 46},{d_haut + 96}L{cx + k * 22},{d_bas - 30}", "#D5C6AE", 3)
    t.hachures(polygone([(cx + 90, d_haut), (cx + 180, d_haut), (cx + 64, d_bas), (cx + 30, d_bas)]),
               angle=-50, pas=7, opacite=0.3)
    t.forme(cone)
    t.forme(ellipse(cx, d_haut + 2, 150, 12), "#5A3A26")
    t.forme(pied)
    vapeur(t, cx, d_haut - 30, 120, 34)
    grains_epars(t, [(150, 260, SOL + 40, SOL + 100), (560, 680, SOL + 50, SOL + 100)], 5, 15)
    return t.svg("Dripper en grès La Fabrique posé sur une tasse")


def scene_moulin() -> str:
    fond = "#DDD7CB"
    t = Toile(L, H, graine=11)
    decor(t, fond, "vieux-lille")
    cx = 400
    t.hachures(polygone([(cx - 40, SOL + 6), (cx + 110, SOL - 6), (cx + 280, SOL + 34), (cx + 40, SOL + 46)]),
               angle=-35, pas=8, opacite=0.3)
    bas, haut = SOL, SOL - 470
    # Godet inférieur.
    godet = rect_arrondi(cx - 86, bas - 150, 172, 150, 18)
    t.forme(godet, ACIER_FONCE)
    t.hachures(polygone([(cx + 30, bas - 150), (cx + 90, bas - 150), (cx + 90, bas), (cx + 30, bas)]), pas=6, opacite=0.3)
    t.forme(godet)
    t.trait(f"M{cx - 64},{bas - 132}V{bas - 24}", ACIER_CLAIR, 5)
    # Corps.
    corps = rect_arrondi(cx - 80, haut + 50, 160, bas - 150 - haut - 44, 12)
    t.forme(corps, ACIER)
    poignee = rect_arrondi(cx - 84, haut + 170, 168, 150, 10)
    t.forme(poignee, BOIS_CLAIR)
    lignes = " ".join(f"M{cx - 72 + i * 12},{haut + 176}V{haut + 314}" for i in range(13))
    t.trait(lignes, BOIS, 2.4, 0.9)
    t.hachures(polygone([(cx + 30, haut + 50), (cx + 84, haut + 50), (cx + 84, bas - 150), (cx + 30, bas - 150)]),
               pas=6, opacite=0.3)
    t.forme(poignee)
    t.forme(corps)
    t.trait(f"M{cx - 58},{haut + 64}V{haut + 160}M{cx - 58},{haut + 330}V{bas - 170}", ACIER_CLAIR, 6)
    # Chapeau, axe, manivelle et pommeau en bois.
    t.forme(rect_arrondi(cx - 84, haut + 30, 168, 30, 8), ACIER_FONCE)
    t.forme(rect_arrondi(cx - 10, haut - 6, 20, 40, 4), ACIER_FONCE)
    manivelle = (f"M{cx - 12},{haut - 4}L{cx + 170},{haut - 52}Q{cx + 186},{haut - 54} {cx + 184},{haut - 38}"
                 f"L{cx + 14},{haut + 16}Z")
    t.forme(manivelle, ACIER)
    t.forme(cercle(cx, haut + 4, 15), ACIER_CLAIR)
    t.forme(rect_arrondi(cx + 166, haut - 120, 30, 72, 14), BOIS)
    t.trait(f"M{cx + 174},{haut - 108}V{haut - 62}", BOIS_CLAIR, 4)
    # Petit écusson.
    t.forme(rect_arrondi(cx - 46, haut + 360, 92, 34, 4), BLANC)
    t.texte("LA FABRIQUE", "sans-700", 10.5, cx, haut + 382, ENCRE, "middle", 0.18)
    grains_epars(t, [(140, 270, SOL + 34, SOL + 100), (540, 690, SOL + 40, SOL + 100)], 9, 15)
    return t.svg("Moulin manuel La Fabrique en acier et bois")


def scene_coffret() -> str:
    fond = "#E7D5C0"
    t = Toile(L, H, graine=23)
    decor(t, fond, "braderie")
    cx = 400
    t.hachures(polygone([(cx - 210, SOL + 4), (cx + 240, SOL - 8), (cx + 360, SOL + 30), (cx - 120, SOL + 46)]),
               angle=-35, pas=8, opacite=0.3)
    # Couvercle appuyé derrière.
    couvercle = polygone([(cx - 230, SOL - 250), (cx - 190, SOL - 560), (cx + 250, SOL - 520), (cx + 236, SOL - 230)])
    t.forme(couvercle, KRAFT_FONCE)
    fibres(t, couvercle, (cx - 240, SOL - 560, cx + 250, SOL - 230), assombrir(KRAFT_FONCE, 0.85), 60)
    t.forme(couvercle)
    t.texte("Coffret découverte", "serif-italique", 40, cx + 24, SOL - 410, BLANC, "middle",
            extra=f'transform="rotate(4 {cx} {SOL - 410})"')
    # Trois petits sacs.
    for i, (dx, sku) in enumerate(((-130, "wazemmes"), (0, "beffroi"), (130, "fives"))):
        info = dict(CAFES[sku])
        sac(t, cx + dx, SOL - 120, info, largeur=120, hauteur=250, poids="100 g", echelle_etiquette=0.45)
    # Caisse.
    face = rect_arrondi(cx - 236, SOL - 230, 472, 230, 6)
    t.forme(face, KRAFT)
    fibres(t, face, (cx - 236, SOL - 230, cx + 236, SOL), KRAFT_FONCE, 80)
    t.hachures(polygone([(cx + 150, SOL - 230), (cx + 240, SOL - 230), (cx + 240, SOL), (cx + 150, SOL)]), pas=7, opacite=0.28)
    t.forme(face)
    t.forme(rect_arrondi(cx - 236, SOL - 230, 472, 26, 4), KRAFT_CLAIR)
    t.forme(rect_arrondi(cx - 120, SOL - 168, 240, 110, 4), BLANC)
    t.texte("LA FABRIQUE", "sans-700", 13, cx, SOL - 138, ENCRE, "middle", 0.24)
    t.texte("Trois cafés de 100 g", "serif-italique", 30, cx, SOL - 98, ENCRE, "middle")
    t.trait(f"M{cx - 96},{SOL - 124}H{cx + 96}", ENCRE, 1.4)
    # Ficelle.
    t.trait(f"M{cx - 236},{SOL - 60}Q{cx},{SOL - 50} {cx + 236},{SOL - 64}", "#E9DCC3", 5)
    t.trait(f"M{cx + 150},{SOL - 58}q30,-40 54,-10q-30,22 -54,10", "#E9DCC3", 4)
    grains_epars(t, [(110, 160, SOL + 40, SOL + 100), (620, 700, SOL + 40, SOL + 100)], 5, 15)
    return t.svg("Coffret découverte La Fabrique : trois cafés de 100 g")


def scenes() -> dict[str, str]:
    """Toutes les illustrations produits, indexées par UGS (sku)."""
    resultat = {sku: scene_cafe(sku) for sku in CAFES}
    resultat["dripper-gres"] = scene_dripper()
    resultat["moulin-manuel"] = scene_moulin()
    resultat["coffret-decouverte"] = scene_coffret()
    return resultat
