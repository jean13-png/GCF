/* ═════════════════════════════════════════════════════════════════
   GCF · Planning : modal de la journée d'une salle
   Clic sur une case du planning → détail des créneaux horaires
   (libre / en attente / réservé / occupé) et programmation
   intelligente des heures libres par l'administration.
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

    function heureCourte(h) {
        var p = String(h || '').split(':');
        return (p.length >= 2) ? p[0] + 'h' + p[1] : String(h || '');
    }

    /* ── Ouverture du modal ─────────────────────────────────────── */
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

    /* ── Rendu de la liste des créneaux ─────────────────────────── */
    function rendu(d) {
        var html = '';

        if (d.salle && d.salle.maintenance) {
            html += '<div class="pj-alerte"><i class="fas fa-tools"></i> Cette salle est actuellement en maintenance : ' +
                'elle n\'accepte pas de nouvelle programmation.</div>';
        }

        html += '<div class="pj-legende">' +
            '<span class="pj-legende-item"><span class="pj-puce p-LIBRE"></span>Libre</span>' +
            '<span class="pj-legende-item"><span class="pj-puce p-EN_ATTENTE"></span>En attente</span>' +
            '<span class="pj-legende-item"><span class="pj-puce p-CONFIRMEE"></span>Réservé</span>' +
            '<span class="pj-legende-item"><span class="pj-puce p-OCCUPEE"></span>Occupé</span>' +
            '</div>';

        html += '<div class="pj-liste">';
        d.creneaux.forEach(function (c) {
            var st = c.statut;
            html += '<div class="pj-creneau cren-' + st + '">';
            html += '<div class="pj-horaire">' + heureCourte(c.debut) + ' – ' + heureCourte(c.fin) + '</div>';
            html += '<div class="pj-etat">';
            html += '<span class="pj-badge b-' + st + '">' + LIBELLES[st] + '</span>';
            if (st !== 'LIBRE' && c.evenement) {
                html += '<span class="pj-evenement"><strong>' + echapper(c.evenement) + '</strong>' +
                    ' · ' + heureCourte(c.resa_debut) + '–' + heureCourte(c.resa_fin) +
                    (c.demandeur ? ' · ' + echapper(c.demandeur) : '') + '</span>';
            }
            html += '</div>';
            if (st === 'LIBRE' && !(d.salle && d.salle.maintenance)) {
                html += '<button type="button" class="btn btn-petit btn-vert pj-btn-add" data-debut="' + c.debut + '">' +
                    '<i class="fas fa-plus"></i> Programmer</button>';
            }
            html += '</div>';
        });
        html += '</div>';
        html += '<p class="aide-champ" style="margin-top:10px;">Cliquez sur « Programmer » pour occuper un créneau libre : ' +
            'il devient aussitôt réservé et plus aucune demande ne pourra le chevaucher.</p>';

        corps.innerHTML = html;

        Array.prototype.forEach.call(corps.querySelectorAll('.pj-btn-add'), function (btn) {
            btn.addEventListener('click', function () {
                formulaire(d, btn.getAttribute('data-debut'));
            });
        });
    }

    /* ── Zones libres contiguës (pour la sélection intelligente) ── */
    function zonesLibres(creneaux) {
        var zones = [];
        var courante = null;
        creneaux.forEach(function (c) {
            if (c.statut === 'LIBRE') {
                if (courante && courante.fin === c.debut) {
                    courante.fin = c.fin;
                } else {
                    courante = { debut: c.debut, fin: c.fin };
                    zones.push(courante);
                }
            } else {
                courante = null;
            }
        });
        return zones;
    }

    /* ── Formulaire de programmation d'un créneau libre ─────────── */
    function formulaire(d, heureDebut) {
        var zones = zonesLibres(d.creneaux);
        var zone = null;
        zones.forEach(function (z) { if (z.debut <= heureDebut && heureDebut < z.fin) { zone = z; } });
        if (!zone) { zone = zones.length ? zones[0] : { debut: '07:00', fin: '21:00' }; }
        if (heureDebut < zone.debut || heureDebut >= zone.fin) { heureDebut = zone.debut; }

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
        html += '<p class="aide-champ">La salle ' + echapper(d.salleNom) + ' est libre de ' + heureCourte(zone.debut) +
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

    /* ── Fermeture ──────────────────────────────────────────────── */
    function fermer() {
        voile.hidden = true;
        document.body.style.overflow = '';
    }

    document.getElementById('pjFermer').addEventListener('click', fermer);
    voile.addEventListener('click', function (e) { if (e.target === voile) { fermer(); } });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !voile.hidden) { fermer(); } });

    /* ── Clic sur les cases du planning ─────────────────────────── */
    Array.prototype.forEach.call(document.querySelectorAll('.case-jour[data-salle-id]'), function (c) {
        function lancer() {
            ouvrir(c.getAttribute('data-salle-id'), c.getAttribute('data-salle-nom'), c.getAttribute('data-date'));
        }
        c.addEventListener('click', lancer);
        c.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); lancer(); }
        });
    });
})();
