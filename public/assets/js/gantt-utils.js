/* ═════════════════════════════════════════════════════════════════
   GCF · Gantt-utils : fonctions partagées par toutes les vues du
   planning. La fonction Gantt.poser() est LA fonction unique qui
   calcule la position et la largeur d'un bloc à partir de ses dates
   et de sa durée, quelle que soit l'échelle ( heures ou demi-journées ).
   ═════════════════════════════════════════════════════════════════ */
window.Gantt = (function () {
    'use strict';

    /* ── Palette ( contrastes vérifiés : texte foncé sur fonds clairs ) ── */
    var PALETTE = [
        { nom: 'cyan clair',   fond: 'rgba(96, 200, 232, .48)',  bord: '#2b8aa8', texte: '#0a4557' },
        { nom: 'rose clair',   fond: 'rgba(246, 148, 193, .42)', bord: '#c05c8f', texte: '#5c1440' },
        { nom: 'jaune pâle',   fond: 'rgba(255, 224, 118, .52)', bord: '#bd9420', texte: '#544004' },
        { nom: 'bleu',         fond: 'rgba(112, 152, 219, .45)', bord: '#3c5e9e', texte: '#1b2c52' },
        { nom: 'vert',         fond: 'rgba(126, 201, 134, .45)', bord: '#3e8a47', texte: '#14401a' },
        { nom: 'magenta',      fond: 'rgba(206, 118, 216, .42)', bord: '#8f3d99', texte: '#48104e' },
        { nom: 'rouge doux',   fond: 'rgba(235, 130, 125, .45)', bord: '#b04b45', texte: '#59150f' },
        { nom: 'vert d\'eau',  fond: 'rgba(130, 216, 196, .48)', bord: '#37927a', texte: '#0d3f32' }
    ];

    function hachage(valeur) {
        var s = String(valeur == null ? '' : valeur), h = 0;
        for (var i = 0; i < s.length; i++) {
            h = (h * 31 + s.charCodeAt(i)) & 0x7fffffff;
        }
        return h;
    }

    /* Couleur stable pour un critère ( client, catégorie, salle, formateur ) */
    function couleur(valeur) {
        return PALETTE[hachage(valeur) % PALETTE.length];
    }

    /* ── Dates ( locales explicites, aucun décalage UTC ) ────────── */
    function dateDe(iso) {
        var p = String(iso).split('-');
        return new Date(+p[0], +p[1] - 1, +p[2]);
    }

    function verrouille(date) {
        return date.getFullYear() + '-' +
            String(date.getMonth() + 1).padStart(2, '0') + '-' +
            String(date.getDate()).padStart(2, '0');
    }

    var JOURS  = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
    var MOIS   = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin',
                  'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

    function dateFR(iso, avecJour) {
        var d = dateDe(iso);
        var t = JOURS[d.getDay()] + ' ' + d.getDate() + ' ' + MOIS[d.getMonth()] + ' ' + d.getFullYear();
        return avecJour === false ? d.getDate() + ' ' + MOIS[d.getMonth()] + ' ' + d.getFullYear() : t;
    }

    function jourCourt(iso) {
        var d = dateDe(iso);
        return JOURS[d.getDay()].substring(0, 3) + ' ' + d.getDate();
    }

    /* Numéro de semaine ISO ( format « S01 - 01/2012 » ) */
    function semaineISO(iso) {
        var d = dateDe(iso);
        var cible = new Date(d.getFullYear(), d.getMonth(), d.getDate());
        var jeudi = new Date(cible);
        jeudi.setDate(cible.getDate() + 3 - ((cible.getDay() + 6) % 7));
        var premierJeudi = new Date(jeudi.getFullYear(), 0, 4);
        var pj = new Date(premierJeudi);
        pj.setDate(premierJeudi.getDate() + 3 - ((premierJeudi.getDay() + 6) % 7));
        var n = 1 + Math.round((jeudi - pj) / (7 * 24 * 3600 * 1000));
        return { numero: n, annee: jeudi.getFullYear() };
    }

    /* ── LA fonction unique de positionnement ──────────────────────
       échelle = { type:'heures', ouverture:6, fermeture:22 }
                 | { type:'demiJournees', colonnes:48 }
       début/fin = 'HH:MM' pour les heures,
                   indice de colonne ( demi-journée ) pour l'autre.
       Retour : { gauche:0-100, largeur:0-100 } en pourcentage.        */
    function poser(echelle, debut, fin) {
        var n, d, f, mini;
        if (echelle.type === 'heures') {
            /* origine = heure d'ouverture : 10h sur une plage 6-22 doit
               commencer à (10-6)/16 = 25 %, pas à 10/16                 */
            var origine = echelle.ouverture * 60;
            n = (echelle.fermeture - echelle.ouverture) * 60;
            d = enMinutes(debut) - origine;
            f = enMinutes(fin) - origine;
            mini = 0.8; /* ~8 minutes minimum, toujours visible */
        } else {
            n = echelle.colonnes;
            d = debut;
            f = fin;
            mini = n > 0 ? 100 / n * 0.9 : 1;
        }
        var gauche  = n > 0 ? (d / n) * 100 : 0;
        var droit   = n > 0 ? (f / n) * 100 : 0;
        /* bornage : un bloc au-delà de la plage s'arrête au bord */
        gauche = Math.max(0, Math.min(100, gauche));
        droit  = Math.max(0, Math.min(100, droit));
        var largeur = Math.max(droit - gauche, mini);
        return { gauche: gauche, largeur: Math.min(largeur, 100 - gauche) };
    }

    function enMinutes(h) {
        var p = String(h).split(':');
        return (+p[0]) * 60 + (+(p[1] || 0));
    }

    function heureCourte(h) {
        var p = String(h || '').split(':');
        return p.length >= 2 ? p[0] + 'h' + p[1] : String(h || '');
    }

    function dureeTexte(debut, fin) {
        var m = Math.max(0, enMinutes(fin) - enMinutes(debut));
        var h = Math.floor(m / 60), r = m % 60;
        if (h === 0) { return r + ' min'; }
        if (r === 0) { return h + ' h'; }
        return h + ' h ' + String(r).padStart(2, '0');
    }

    /* ── Demi-journées ( vue Ressources ) ────────────────────────── */
    var HEURES_DEMI = { AM: { debut: '08:00', fin: '12:00' }, PM: { debut: '13:00', fin: '18:00' } };

    /* « AM » ou « PM » selon l'heure */
    function demiDe(heure) {
        return enMinutes(heure) < 12 * 60 ? 'AM' : 'PM';
    }

    /* Indice de colonne d'une demi-journée : joursVisibles est la liste
       des dates affichées ( ISO ), chaque jour valant 2 colonnes. */
    function colonneDe(dateIso, demi, joursVisibles) {
        for (var i = 0; i < joursVisibles.length; i++) {
            if (joursVisibles[i] === dateIso) {
                return i * 2 + (demi === 'AM' ? 0 : 1);
            }
        }
        return -1;
    }

    function echapper(v) {
        return String(v == null ? '' : v)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function initiales(nom, prenom) {
        var a = (prenom || '').trim().charAt(0);
        var b = (nom || '').trim().charAt(0);
        return (a + b).toUpperCase() || '?';
    }

    return {
        palette: PALETTE,
        couleur: couleur,
        poser: poser,
        dateDe: dateDe,
        verrouille: verrouille,
        dateFR: dateFR,
        jourCourt: jourCourt,
        semaineISO: semaineISO,
        heureCourte: heureCourte,
        dureeTexte: dureeTexte,
        demiDe: demiDe,
        colonneDe: colonneDe,
        HEURES_DEMI: HEURES_DEMI,
        echapper: echapper,
        initiales: initiales
    };
})();
