/* ═════════════════════════════════════════════════════════════════
   GCF · Planning : modals de la page Planning
   1. Modal « journée d'une salle » : timeline horaire détaillée,
      créneaux libres et programmation par l'administration.
   2. Modal « détail d'un événement » : toutes les précisions
      ( organisateur, client, effectif, coordonnées ).
   ═════════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    var voile = document.getElementById('pjVoile');
    if (!voile) { return; }

    var corps = document.getElementById('pjCorps');
    var titre = document.getElementById('pjTitre');
    var csrf = document.getElementById('pjCsrf');
    var organisateurDefaut = (document.getElementById('pjOrganisateur') || {}).value || 'Administration du centre';
    var urlJour = voile.getAttribute('data-url-jour');
    var urlProgrammer = voile.getAttribute('data-url-programmer');

    /* ── Modal « détail d'un événement » ────────────────────────── */
    var peVoile = document.getElementById('peVoile');
    var peCorps = document.getElementById('peCorps');
    var peTitre = document.getElementById('peTitre');
    var peStatut = document.getElementById('peStatut');

    var LIBELLES = {
        LIBRE: 'Libre',
        EN_ATTENTE: 'En attente',
        CONFIRMEE: 'Réservé',
        OCCUPEE: 'Occupé'
    };

    var MOIS = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin',
        'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    var JOURS = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];

    function echapper(v) {
        return String(v == null ? '' : v)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function dateFr(iso) {
        var p = String(iso).split('-');
        if (p.length !== 3) { return String(iso); }
        var d = new Date(+p[0], +p[1] - 1, +p[2]);
        return JOURS[d.getDay()] + ' ' + (+p[2]) + ' ' + MOIS[+p[1] - 1] + ' ' + p[0];
    }

    function dateCourte(iso) {
        var p = String(iso).split('-');
        if (p.length !== 3) { return String(iso); }
        var d = new Date(+p[0], +p[1] - 1, +p[2]);
        return JOURS[d.getDay()].charAt(0).toUpperCase() + JOURS[d.getDay()].slice(1) + ' ' + (+p[2]) + ' ' + MOIS[+p[1] - 1];
    }

    function heureCourte(h) {
        var p = String(h || '').split(':');
        return (p.length >= 2) ? p[0] + 'h' + p[1] : String(h || '');
    }

    function duree(debut, fin) {
        var pd = String(debut).split(':'), pf = String(fin).split(':');
        var md = (+pd[0]) * 60 + (+pd[1]);
        var mf = (+pf[0]) * 60 + (+pf[1]);
        var d = Math.max(0, mf - md);
        var h = Math.floor(d / 60), m = d % 60;
        if (h === 0) { return m + ' min'; }
        if (m === 0) { return h + ' h'; }
        return h + ' h ' + (m < 10 ? '0' + m : m);
    }

    /* ── Ouverture du modal journée ────────────────────────────── */
    function ouvrir(salleId, salleNom, date) {
        titre.textContent = salleNom + ' · ' + dateFr(date);
        corps.innerHTML = '<p class="pj-chargement">Chargement de la journée…</p>';
        voile.hidden = false;
        document.body.style.overflow = 'hidden';

        fetch(urlJour + '?salle_id=' + encodeURIComponent(salleId) + '&date=' + encodeURIComponent(date), {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.erreur) {
                    corps.innerHTML = '<p class="pj-erreur">' + echapper(d.erreur) + '</p>';
                    return;
                }
                d.salleNom = salleNom;
                rendu(d);
            })
            .catch(function () {
                corps.innerHTML = '<p class="pj-erreur">Impossible de charger la journée. Vérifiez votre connexion puis réessayez.</p>';
            });
    }

    /* ── Rendu : timeline détaillée de la journée ──────────────── */
    function rendu(d) {
        var html = '';

        /* Fiche salle */
        html += '<div class="pj-fiche-salle">';
        html += '<i class="fas fa-door-open"></i><div><strong>' + echapper(d.salleNom) + '</strong>';
        if (d.salle) {
            html += '<span>' + (d.salle.capacite || '?') + ' places' +
                (d.salle.localisation ? ' · ' + echapper(d.salle.localisation) : '') + '</span>';
        }
        html += '</div></div>';

        if (d.salle && d.salle.maintenance) {
            html += '<div class="pj-alerte"><i class="fas fa-tools"></i> Cette salle est actuellement en maintenance : ' +
                'elle n\'accepte pas de nouvelle programmation.</div>';
        }

        /* Réservations de la journée : blocs détaillés */
        if (d.occurrences && d.occurrences.length > 0) {
            html += '<h4 class="pj-section"><i class="fas fa-calendar-day"></i> Déroulé de la journée</h4>';
            html += '<div class="pj-evenements">';
            d.occurrences.forEach(function (o) {
                html += '<div class="pj-evt ' + (o.statut === 'OCCUPEE' ? 'st-occupe' : o.statut === 'CONFIRMEE' ? 'st-reserve' : 'st-attente') + '">';
                html += '<div class="pj-evt-barre"></div>';
                html += '<div class="pj-evt-corps">';
                html += '<div class="pj-evutile"><span class="pj-evt-horaires">' +
                    heureCourte(o.heure_debut) + ' – ' + heureCourte(o.heure_fin) + '</span>' +
                    '<span class="pj-evt-duree">' + duree(o.heure_debut, o.heure_fin) + '</span>' +
                    '<span class="pj-badge b-' + o.statut + '">' + (LIBELLES[o.statut] || o.statut) + '</span></div>';
                html += '<div class="pj-evt-titre">' + echapper(o.evenement) + '</div>';
                html += '<div class="pj-evt-meta">';
                if (o.demandeur_nom) { html += '<span><i class="fas fa-user"></i>' + echapper(o.demandeur_nom) + '</span>'; }
                if (o.demandeur_organisation) { html += '<span><i class="fas fa-building"></i>' + echapper(o.demandeur_organisation) + '</span>'; }
                if (o.nb_personnes) { html += '<span><i class="fas fa-users"></i>' + echapper(o.nb_personnes) + ' pers.</span>'; }
                if (o.demandeur_email) { html += '<span><i class="fas fa-envelope"></i>' + echapper(o.demandeur_email) + '</span>'; }
                if (o.demandeur_telephone) { html += '<span><i class="fas fa-phone"></i>' + echapper(o.demandeur_telephone) + '</span>'; }
                html += '</div></div></div>';
            });
            html += '</div>';
        } else {
            html += '<div class="pj-jour-libre"><i class="fas fa-check-circle"></i> Aucun événement : la salle est libre toute la journée.</div>';
        }

        /* Créneaux libres + programmation */
        if (!(d.salle && d.salle.maintenance) && d.creneaux_libres && d.creneaux_libres.length > 0) {
            html += '<h4 class="pj-section"><i class="fas fa-plus-circle"></i> Programmer sur un créneau libre</h4>';
            html += '<div class="pj-libres">';
            d.creneaux_libres.forEach(function (z) {
                var libre = true;
                (d.occurrences || []).forEach(function (o) {
                    if (o.heure_debut < z.fin && o.heure_fin > z.debut) { libre = false; }
                });
                if (!libre) { return; }
                html += '<button type="button" class="pj-libre-btn" data-debut="' + z.debut + '" data-fin="' + z.fin + '">' +
                    '<i class="fas fa-plus"></i>' + heureCourte(z.debut) + ' – ' + heureCourte(z.fin) +
                    '<span>programmer sur ce créneau</span></button>';
            });
            html += '</div>';
            html += '<p class="aide-champ" style="margin-top:10px;">La programmation devient aussitôt « Réservé » : ' +
                'plus aucune demande ne pourra chevaucher ce créneau.</p>';
        }

        corps.innerHTML = html;

        Array.prototype.forEach.call(corps.querySelectorAll('.pj-libre-btn'), function (btn) {
            btn.addEventListener('click', function () {
                formulaire(d, btn.getAttribute('data-debut'), btn.getAttribute('data-fin'));
            });
        });
    }

    /* ── Formulaire de programmation d'un créneau libre ─────────── */
    function formulaire(d, heureDebut, borneFin) {
        var zone = { debut: heureDebut, fin: borneFin || '21:00' };

        var heures = [];
        for (var h = 7; h <= 21; h++) { heures.push((h < 10 ? '0' + h : '' + h) + ':00'); }

        var types = ['Conférence', 'Réunion', 'Formation', 'Atelier', 'Séminaire', 'Examen', 'Autre'];

        var html = '';
        html += '<button type="button" class="btn btn-petit btn-discret pj-retour"><i class="fas fa-arrow-left"></i> Retour à la journée</button>';
        html += '<h4 class="pj-form-titre">Programmer un événement le ' + dateFr(d.date) + '</h4>';
        html += '<form id="pjForm" class="pj-form">';
        html += '<label>Type d\'événement <span class="obligatoire">*</span>' +
            '<select id="pjType" name="type">';
        types.forEach(function (t) { html += '<option' + (t === 'Réunion' ? ' selected' : '') + '>' + t + '</option>'; });
        html += '</select></label>';
        html += '<label>Intitulé <span class="obligatoire">*</span>' +
            '<input type="text" id="pjEvenement" name="evenement" maxlength="200" value="Réunion" required></label>';
        html += '<label>Heure de début <span class="obligatoire">*</span>' +
            '<select id="pjDebut" name="heure_debut">';
        heures.forEach(function (h) {
            var ok = h >= zone.debut && h < zone.fin;
            html += '<option value="' + h + '"' + (h === heureDebut ? ' selected' : '') + (ok ? '' : ' disabled') + '>' + heureCourte(h) + '</option>';
        });
        html += '</select></label>';
        html += '<label>Heure de fin <span class="obligatoire">*</span>' +
            '<select id="pjFin" name="heure_fin"></select></label>';
        html += '<label>Organisé par <span class="obligatoire">*</span>' +
            '<input type="text" id="pjOrganisateur" name="demandeur_nom" maxlength="150" value="' + echapper(organisateurDefaut) + '" required></label>';
        html += '<label>Nombre de personnes' +
            '<input type="number" id="pjNb" name="nb_personnes" min="0" max="2000" value="' + (d.salle ? d.salle.capacite : '') + '"></label>';
        html += '<p class="pj-erreur pj-message" id="pjMessage" hidden></p>';
        html += '<div class="pj-actions">' +
            '<button type="submit" class="btn btn-primaire"><i class="fas fa-calendar-plus"></i> Enregistrer la programmation</button>' +
            '</div>';
        html += '<p class="aide-champ">Créneau libre visé : de ' + heureCourte(zone.debut) +
            ' à ' + heureCourte(zone.fin) + ' : les heures proposées s\'arrêtent à la prochaine occupation.</p>';
        html += '</form>';

        corps.innerHTML = html;
        majFins(zone, heureDebut);

        document.getElementById('pjType').addEventListener('change', function () {
            document.getElementById('pjEvenement').value = this.value;
        });
        document.getElementById('pjDebut').addEventListener('change', function () {
            majFins(zone, this.value);
        });
        corps.querySelector('.pj-retour').addEventListener('click', function () {
            rendu(d);
        });

        document.getElementById('pjForm').addEventListener('submit', function (e) {
            e.preventDefault();
            envoyer(d);
        });
    }

    /* Heures de fin possibles : du début choisi jusqu'à la fin de la zone libre. */
    function majFins(zone, debutChoisi) {
        var selFin = document.getElementById('pjFin');
        var heures = [];
        for (var h = 7; h <= 21; h++) { heures.push((h < 10 ? '0' + h : '' + h) + ':00'); }
        var options = '';
        var premiere = null;
        heures.forEach(function (h) {
            if (h > debutChoisi && h <= zone.fin) {
                if (!premiere) { premiere = h; }
                options += '<option value="' + h + '">' + heureCourte(h) + '</option>';
            }
        });
        selFin.innerHTML = options;
        /* durée par défaut : une heure */
        if (premiere) { selFin.value = premiere; }
    }

    /* ── Envoi de la programmation ──────────────────────────────── */
    function envoyer(d) {
        var donnees = new FormData();
        donnees.append('_token', csrf.value);
        donnees.append('salle_id', d.salle.id);
        donnees.append('date_reservation', d.date);
        donnees.append('evenement', document.getElementById('pjEvenement').value.trim());
        donnees.append('demandeur_nom', document.getElementById('pjOrganisateur').value.trim());
        donnees.append('heure_debut', document.getElementById('pjDebut').value);
        donnees.append('heure_fin', document.getElementById('pjFin').value);
        donnees.append('nb_personnes', document.getElementById('pjNb').value);

        var message = document.getElementById('pjMessage');
        message.hidden = true;

        fetch(urlProgrammer, {
            method: 'POST',
            credentials: 'same-origin',
            body: new URLSearchParams(donnees),
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { return r.json(); })
            .then(function (rep) {
                if (rep.ok) {
                    fermer();
                    window.location.reload();
                    return;
                }
                message.textContent = rep.erreur || 'Enregistrement impossible.';
                message.hidden = false;
            })
            .catch(function () {
                message.textContent = 'Envoi impossible : vérifiez votre connexion puis réessayez.';
                message.hidden = false;
            });
    }

    /* ── Modal « détail d'un événement » ( données de la grille ) ── */
    function ouvrirEvenement(el) {
        var statut = el.getAttribute('data-statut') || '';
        peTitre.textContent = el.getAttribute('data-evenement') || 'Événement';
        peStatut.textContent = LIBELLES[statut] || statut;
        peStatut.className = 'pe-statut pe-s-' + statut;

        var html = '';
        html += '<div class="pe-horaires"><i class="fas fa-clock"></i>' +
            heureCourte(el.getAttribute('data-debut')) + ' – ' + heureCourte(el.getAttribute('data-fin')) +
            '<span>' + duree(el.getAttribute('data-debut'), el.getAttribute('data-fin')) + '</span></div>';
        html += '<div class="pe-grille">';
        html += '<div class="pe-ligne"><span class="pe-label"><i class="fas fa-door-open"></i> Salle</span><span>' + echapper(el.getAttribute('data-salle')) +
            ' <small>(' + echapper(el.getAttribute('data-capacite')) + ' places)</small></span></div>';
        if (el.getAttribute('data-orga')) {
            html += '<div class="pe-ligne"><span class="pe-label"><i class="fas fa-user"></i> Organisateur</span><span>' + echapper(el.getAttribute('data-orga')) + '</span></div>';
        }
        if (el.getAttribute('data-organisation')) {
            html += '<div class="pe-ligne"><span class="pe-label"><i class="fas fa-building"></i> Client / organisation</span><span>' + echapper(el.getAttribute('data-organisation')) + '</span></div>';
        }
        if (el.getAttribute('data-nb')) {
            html += '<div class="pe-ligne"><span class="pe-label"><i class="fas fa-users"></i> Effectif</span><span>' + echapper(el.getAttribute('data-nb')) + ' personnes</span></div>';
        }
        if (el.getAttribute('data-email')) {
            html += '<div class="pe-ligne"><span class="pe-label"><i class="fas fa-envelope"></i> Email</span><span>' + echapper(el.getAttribute('data-email')) + '</span></div>';
        }
        if (el.getAttribute('data-tel')) {
            html += '<div class="pe-ligne"><span class="pe-label"><i class="fas fa-phone"></i> Téléphone</span><span>' + echapper(el.getAttribute('data-tel')) + '</span></div>';
        }
        html += '</div>';
        html += '<p class="aide-champ" style="margin-top:12px;">Pour modifier ou annuler cet événement, ouvrez la journée correspondante depuis la grille.</p>';

        peCorps.innerHTML = html;
        peVoile.hidden = false;
        document.body.style.overflow = 'hidden';
    }

    /* ── Fermetures ─────────────────────────────────────────────── */
    function fermer() {
        voile.hidden = true;
        if (peVoile) { peVoile.hidden = true; }
        document.body.style.overflow = '';
    }

    document.getElementById('pjFermer').addEventListener('click', fermer);
    voile.addEventListener('click', function (e) { if (e.target === voile) { fermer(); } });
    if (peVoile) {
        document.getElementById('peFermer').addEventListener('click', fermer);
        peVoile.addEventListener('click', function (e) { if (e.target === peVoile) { fermer(); } });
    }
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && (voile.hidden === false || (peVoile && peVoile.hidden === false))) { fermer(); } });

    /* ── Clic sur les pistes / cases journée ───────────────────── */
    Array.prototype.forEach.call(document.querySelectorAll('.pl-piste[data-salle-id], .pl-syn-case[data-salle-id]'), function (c) {
        function lancer() {
            ouvrir(c.getAttribute('data-salle-id'), c.getAttribute('data-salle-nom'), c.getAttribute('data-date'));
        }
        c.addEventListener('click', lancer);
        c.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); lancer(); }
        });
    });

    /* ── Clic sur un événement : détail ( sans ouvrir la journée ) ── */
    Array.prototype.forEach.call(document.querySelectorAll('.pl-evt'), function (el) {
        function lancer(e) {
            e.stopPropagation();
            ouvrirEvenement(el);
        }
        el.addEventListener('click', lancer);
        el.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); e.stopPropagation(); ouvrirEvenement(el); }
        });
    });
})();
