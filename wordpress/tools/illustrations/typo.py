"""Texte vectorisé : convertit une chaîne en tracés SVG (aucune police requise au rendu).

La mise en forme (crénage, ligatures) est faite par HarfBuzz, les contours des glyphes
sont lus par fontTools dans les fichiers woff2 du thème (assets/fonts). Les SVG produits
sont donc autonomes : cairosvg, un navigateur ou Inkscape les affichent à l'identique.
"""

from __future__ import annotations

import functools
import io
import math
from pathlib import Path

import uharfbuzz as hb
from fontTools.pens.svgPathPen import SVGPathPen
from fontTools.pens.transformPen import TransformPen
from fontTools.ttLib import TTFont

DOSSIER_POLICES = (
    Path(__file__).resolve().parents[2] / "wp-content" / "themes" / "la-fabrique" / "assets" / "fonts"
)

POLICES = {
    "serif": "instrument-serif-latin-400-normal.woff2",
    "serif-italique": "instrument-serif-latin-400-italic.woff2",
    "sans": "instrument-sans-latin-400-normal.woff2",
    "sans-500": "instrument-sans-latin-500-normal.woff2",
    "sans-600": "instrument-sans-latin-600-normal.woff2",
    "sans-700": "instrument-sans-latin-700-normal.woff2",
}


@functools.lru_cache(maxsize=None)
def _police(nom: str):
    """Charge une police (woff2 → sfnt en mémoire) pour HarfBuzz et fontTools."""
    tt = TTFont(DOSSIER_POLICES / POLICES[nom])
    tampon = io.BytesIO()
    tt.flavor = None
    tt.save(tampon)
    face = hb.Face(tampon.getvalue())
    return tt, hb.Font(face), face.upem


def _mettre_en_forme(texte: str, nom: str):
    tt, police, upem = _police(nom)
    tampon = hb.Buffer()
    tampon.add_str(texte)
    tampon.guess_segment_properties()
    hb.shape(police, tampon, {"kern": True, "liga": True})
    return tt, upem, tampon.glyph_infos, tampon.glyph_positions


def largeur(texte: str, nom: str, taille: float, interlettrage: float = 0.0) -> float:
    """Largeur du texte en unités SVG ; l'interlettrage est exprimé en em."""
    _, upem, infos, positions = _mettre_en_forme(texte, nom)
    total = sum(p.x_advance for p in positions) * taille / upem
    return total + interlettrage * taille * max(len(infos) - 1, 0)


def chemin(texte: str, nom: str, taille: float, x: float, y: float,
           ancre: str = "start", interlettrage: float = 0.0) -> str:
    """Renvoie l'attribut « d » d'un tracé qui dessine le texte, ligne de base en y."""
    tt, upem, infos, positions = _mettre_en_forme(texte, nom)
    echelle = taille / upem
    total = largeur(texte, nom, taille, interlettrage)
    depart = {"start": x, "middle": x - total / 2, "end": x - total}[ancre]
    ordre = tt.getGlyphOrder()
    glyphes = tt.getGlyphSet()
    morceaux = []
    curseur = 0.0
    for info, pos in zip(infos, positions):
        stylo = SVGPathPen(glyphes, ntos=lambda v: f"{v:.2f}".rstrip("0").rstrip("."))
        gx = depart + curseur + pos.x_offset * echelle
        gy = y - pos.y_offset * echelle
        glyphes[ordre[info.codepoint]].draw(TransformPen(stylo, (echelle, 0, 0, -echelle, gx, gy)))
        morceaux.append(stylo.getCommands())
        curseur += pos.x_advance * echelle + interlettrage * taille
    return " ".join(m for m in morceaux if m)


def ajuster(texte: str, nom: str, largeur_max: float, taille_max: float,
            interlettrage: float = 0.0) -> float:
    """Taille de police la plus grande (≤ taille_max) qui fait tenir le texte dans largeur_max."""
    mesure = largeur(texte, nom, 100, interlettrage)
    return min(taille_max, largeur_max * 100 / mesure) if mesure else taille_max


def sur_cercle(texte: str, nom: str, taille: float, cx: float, cy: float, rayon: float,
               depart_deg: float = -90.0, interlettrage: float = 0.0) -> str:
    """Texte disposé sur un cercle (sens horaire), centré sur l'angle depart_deg.

    Repère SVG (y vers le bas) : -90° est le haut du cercle. Chaque glyphe est placé
    sur la ligne de base de rayon « rayon », son haut tourné vers l'extérieur.
    """
    tt, upem, infos, positions = _mettre_en_forme(texte, nom)
    echelle = taille / upem
    total = largeur(texte, nom, taille, interlettrage)
    angle = math.radians(depart_deg) - (total / rayon) / 2
    ordre = tt.getGlyphOrder()
    glyphes = tt.getGlyphSet()
    morceaux = []
    for info, pos in zip(infos, positions):
        avance = pos.x_advance * echelle
        milieu = angle + (avance / 2) / rayon
        sin_m, cos_m = math.sin(milieu), math.cos(milieu)
        # Tangente T = (-sin, cos) ; normale extérieure N = (cos, sin).
        ox = cx + rayon * cos_m + sin_m * avance / 2
        oy = cy + rayon * sin_m - cos_m * avance / 2
        matrice = (-sin_m * echelle, cos_m * echelle, cos_m * echelle, sin_m * echelle, ox, oy)
        stylo = SVGPathPen(glyphes, ntos=lambda v: f"{v:.2f}".rstrip("0").rstrip("."))
        glyphes[ordre[info.codepoint]].draw(TransformPen(stylo, matrice))
        morceaux.append(stylo.getCommands())
        angle += (avance + interlettrage * taille) / rayon
    return " ".join(m for m in morceaux if m)
