/* ═════════════════════════════════════════════════════════════════
   GCF · Planning : vue Gantt d'une salle, barre d'options partagée,
   modal journée + programmation, fiche détail ( modification et
   suppression ) et tooltip.
   ═════════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    var D = window.PLANNING_DATA || {};
    var G = window.Gantt;
    var vueRessourcesPresente = !!document.getElementById('gaConteneur');

    if (!G) { return; }

    /* ═══════════ Outils communs ═══════════ */

    function echapper(v) { return G.echapper(v); }

    function heuresDemi() {
        var out = [];
        for (var h = D.ouverture; h <= D.fermeture; h++) {
            out.push((h < 10 ? '0' + h : '' + h) + ':00');
            if (h < D.fermeture) { out.push((h < 10 ? '0' + h : '' + h) + ':30'); }
        }
        return out;
    }

    function heureCourte(h) { return G.heureCourte(h); }

    function optionsHeures(debut, fin, inclus) {
        var html = '';
        heuresDemi().forEach(function (h) {
            var ok = h >= debut && h <= fin && inclus.indexOf(h) >= 0;
            html += '<option value="' + h + '">' + heureCourte(h) + '</option>';
        });
        return html;
    }

    /* ═══════════ Tooltip partagé ═══════════ */

    var tooltip = document.getElementById('gaTooltip');

    function montrerTooltip(x, y, html) {
        if (!tooltip) { return; }
        tooltip.innerHTML = html;
        tooltip.hidden = false;
        var l = Math.min(x + 14, window.innerWidth - tooltip.offsetWidth - 12);
        var t = Math.min(y + 16, window.innerHeight - tooltip.offsetHeight - 12);
        tooltip.style.left = Math.max(8, l) + 'px';
        tooltip.style.top = Math.max(8, t) + 'px';
    }

    function cacherTooltip() { if (tooltip) { tooltip.hidden = true; } }

    function tooltipResa(r) {
        var st = (D.libellesStatut && D.libellesStatut[r.statut]) || r.statut;
        return '<b>' + echapper(r.evenement) + '</b>' +
            '<span>' + heureCourte(r.heure_debut) + ' – ' + heureCourte(r.heure_fin) + ' · ' + G.dureeTexte(r.heure_debut, r.heure_fin) + '</span>' +
            '<span>Salle : ' + echapper(r.salle_nom || r.salle) + '</span>' +
            (r.client ? '<span>Client : ' + echapper(r.client) + '</span>' : '') +
            (r.demandeur_nom ? '<span>Organisateur : ' + echapper(r.demandeur_nom) + '</span>' : '') +
            (r.formateur_nom ? '<span>Formateur : ' + echapper(r.formateur_nom) + '</span>' : '') +
            '<span>Statut : ' + echapper(st) + '</span>';
    }

    /* ═══════════ Barre d'options : couleur par + légende ═══════════ */

    var selCouleur = document.getElementById('gaCouleur');
    var zoneLegende = document.getElementById('gaLegende');

    function valeurCritere(r) {
        var c = selCouleur ? selCouleur.value : 'categorie';
        if (c === 'client') { return r.client || r.organisation || r.demandeur_nom || 'Sans client'; }
        if (c === 'salle') { return r.salle_nom || r.salle || ''; }
        if (c === 'statut') { return r.statut; }
        return r.categorie || 'Non classé';
    }

    function majLegende(valeurs) {
        if (!zoneLegende) { return; }
        var parStatut = selCouleur && selCouleur.value === 'statut';
        var ordre = ['EN_ATTENTE', 'CONFIRMEE', 'OCCUPEE', 'A_REPORTER'];
        if (parStatut) {
            valeurs = valeurs.slice().sort(function (a, b) {
                return ordre.indexOf(a) - ordre.indexOf(b);
            });
        }
        var html = '';
        if (parStatut) {
            html += '<span class="ga-leg-item"><span class="ga-leg-puce" style="background:' +
                G.COULEUR_LIBRE.fond + ';border-color:' + G.COULEUR_LIBRE.bord + '"></span>Libre</span>';
        }
        valeurs.forEach(function (v) {
            var c = parStatut ? G.couleurStatut(v) : G.couleur(v);
            var libelle = parStatut ? ((D.libellesStatut || {})[v] || v) : v;
            html += '<span class="ga-leg-item"><span class="ga-leg-puce" style="background:' + c.fond + ';border-color:' + c.bord + '"></span>' +
                echapper(String(libelle).length > 22 ? String(libelle).substring(0, 21) + '…' : libelle) + '</span>';
        });
        zoneLegende.innerHTML = html;
    }

    if (selCouleur) {
        selCouleur.addEventListener('change', function () {
            positionnerEtColorer();
            window.dispatchEvent(new CustomEvent('ga:couleur', { detail: selCouleur.value }));
        });
    }

    /* ═══════════ Vue 1 ( Gantt salle ) : positionnement + couleurs ═══════════ */

    var echelleHeures = { type: 'heures', ouverture: D.ouverture, fermeture: D.fermeture };

    function blocVersResa(bloc) {
        return {
            id: bloc.getAttribute('data-id'),
            evenement: bloc.getAttribute('data-evenement'),
            categorie: bloc.getAttribute('data-categorie'),
            statut: bloc.getAttribute('data-statut'),
            salle_id: bloc.getAttribute('data-salle-id'),
            salle_nom: bloc.getAttribute('data-salle'),
            client: bloc.getAttribute('data-organisation'),
            organisation: bloc.getAttribute('data-organisation'),
            demandeur_nom: bloc.getAttribute('data-orga'),
            email: bloc.getAttribute('data-email'),
            telephone: bloc.getAttribute('data-tel'),
            nb_personnes: bloc.getAttribute('data-nb'),
            formateur_id: bloc.getAttribute('data-formateur-id') || null,
            date_debut: bloc.getAttribute('data-date-debut'),
            date_fin: bloc.getAttribute('data-date-fin'),
            heure_debut: bloc.getAttribute('data-debut'),
            heure_fin: bloc.getAttribute('data-fin')
        };
    }

    function positionnerEtColorer() {
        var grilles = document.querySelectorAll('.g1-grille');
        if (!grilles.length) { return; }

        var valeursVues = {};
        Array.prototype.forEach.call(grilles, function (grille) {
            Array.prototype.forEach.call(grille.querySelectorAll('.g1-bloc'), function (bloc) {
                var r = blocVersResa(bloc);
                /* LA fonction unique de positionnement ( partagée avec la vue 2 ) */
                var p = G.poser(echelleHeures, r.heure_debut, r.heure_fin);
                bloc.style.left = p.gauche + '%';
                bloc.style.width = p.largeur + '%';

                var reporter = r.statut === 'A_REPORTER';
                if (!reporter) {
                    var cle = valeurCritere(r);
                    /* code couleur des statuts : libre vert, attente orange,
                       réservé bleu, occupé rouge, à reporter jaune */
                    var c = (selCouleur && selCouleur.value === 'statut')
                        ? G.couleurStatut(r.statut)
                        : G.couleur(cle);
                    bloc.style.background = c.fond;
                    bloc.style.borderColor = c.bord;
                    bloc.style.color = c.texte;
                    valeursVues[cle] = true;
                } else {
                    valeursVues['A_REPORTER'] = true;
                }
            });
        });
        majLegende(Object.keys(valeursVues));
    }

    /* ═══════════ Vue 1 : clics, survol, sélection glissée ═══════════ */

    Array.prototype.forEach.call(document.querySelectorAll('.g1-grille'), function (grille) {
        /* clic sur un bloc : fiche détail */
        Array.prototype.forEach.call(grille.querySelectorAll('.g1-bloc'), function (bloc) {
            bloc.addEventListener('click', function (e) { e.stopPropagation(); PlanningFiche.ouvrir(blocVersResa(bloc)); });
            bloc.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); e.stopPropagation(); PlanningFiche.ouvrir(blocVersResa(bloc)); }
            });
            bloc.addEventListener('mouseenter', function (e) { montrerTooltip(e.clientX, e.clientY, tooltipResa(blocVersResa(bloc))); });
            bloc.addEventListener('mousemove', function (e) { montrerTooltip(e.clientX, e.clientY, tooltipResa(blocVersResa(bloc))); });
            bloc.addEventListener('mouseleave', cacherTooltip);
        });

        /* clic sur une zone vide : journée + programmation à l'heure cliquée */
        grille.addEventListener('click', function (e) {
            if (e.target.closest('.g1-bloc')) { return; }
            var rect = grille.getBoundingClientRect();
            var pct = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
            var minutes = D.ouverture * 60 + pct * (D.fermeture - D.ouverture) * 60;
            var h = Math.floor(minutes / 60), m = minutes % 60 < 30 ? '00' : '30';
            ouvrirJournee(grille.getAttribute('data-salle-id'), grille.getAttribute('data-salle-nom'),
                grille.getAttribute('data-date'), (h < 10 ? '0' + h : '' + h) + ':' + m);
        });

        /* glisser sur une zone vide : plage horaire présélectionnée */
        var selection = null, departX = 0;

        function annulerSelection() {
            if (selection) { selection.remove(); selection = null; }
        }

        grille.addEventListener('pointerdown', function (e) {
            if (e.target.closest('.g1-bloc') || e.button !== 0) { return; }
            e.preventDefault();
            departX = e.clientX;
            selection = document.createElement('div');
            selection.className = 'g1-selection';
            grille.appendChild(selection);
            grille.setPointerCapture(e.pointerId);
        });

        grille.addEventListener('pointermove', function (e) {
            if (!selection) { return; }
            var rect = grille.getBoundingClientRect();
            var a = Math.max(0, Math.min(rect.width, departX - rect.left));
            var b = Math.max(0, Math.min(rect.width, e.clientX - rect.left));
            selection.style.left = a + 'px';
            selection.style.width = (b - a) + 'px';
        });

        grille.addEventListener('pointerup', function (e) {
            if (!selection) { return; }
            var rect = grille.getBoundingClientRect();
            var a = (departX - rect.left) / rect.width;
            var b = (e.clientX - rect.left) / rect.width;
            annulerSelection();
            if (Math.abs(b - a) < 0.015) { return; } /* trop court : simple clic */

            function enHeure(t) {
                var min = Math.round((D.ouverture + t * (D.fermeture - D.ouverture)) * 2) / 2;
                min = Math.max(D.ouverture, Math.min(D.fermeture, min));
                var h = Math.floor(min), m = (min % 1) ? '30' : '00';
                return (h < 10 ? '0' + h : '' + h) + ':' + m;
            }
            var debut = enHeure(Math.min(a, b));
            var fin = enHeure(Math.max(a, b));
            if (fin <= debut) { return; }
            ouvrirJournee(grille.getAttribute('data-salle-id'), grille.getAttribute('data-salle-nom'),
                grille.getAttribute('data-date'), debut, fin);
        });

        grille.addEventListener('pointercancel', annulerSelection);
    });

    /* ═══════════ Vue 1 : alertes + actualiser ( vue ressources absente ) ═══════════ */

    var cocherAlertes = document.getElementById('gaAlertes');
    var zoneAlertes = document.getElementById('gaAlertesZone');
    var btnActualiser = document.getElementById('gaActualiser');

    function afficherAlertes(alertes) {
        if (!zoneAlertes) { return; }
        if (!alertes || !alertes.length) {
            zoneAlertes.innerHTML = '<i class="fas fa-check-circle"></i> Aucun message d\'alerte sur la période.';
            zoneAlertes.classList.add('vide');
        } else {
            zoneAlertes.classList.remove('vide');
            var html = '';
            alertes.forEach(function (a) {
                var ic = a.type === 'conflit' ? 'fa-exclamation-triangle' : a.type === 'double' ? 'fa-copy' : 'fa-tools';
                html += '<div class="ga-alerte t-' + a.type + '"><i class="fas ' + ic + '"></i>' + echapper(a.message) + '</div>';
            });
            zoneAlertes.innerHTML = html;
        }
        zoneAlertes.hidden = false;
    }

    function chargerAlertes() {
        if (!D.urls || !D.urls.donnees) { return; }
        fetch(D.urls.donnees + '?debut=' + encodeURIComponent(D.semaineDebut) + '&semaines=1', {
            credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { return r.json(); })
            .then(function (rep) { afficherAlertes(rep.alertes); })
            .catch(function () { afficherAlertes([{ type: 'conflit', message: 'Impossible de charger les alertes.' }]); });
    }

    if (cocherAlertes && !vueRessourcesPresente) {
        cocherAlertes.addEventListener('change', function () {
            if (this.checked) { chargerAlertes(); } else { zoneAlertes.hidden = true; }
        });
    }
    if (btnActualiser && !vueRessourcesPresente) {
        btnActualiser.addEventListener('click', function () { window.location.reload(); });
    }
    window.addEventListener('ga:alertes', function (e) { afficherAlertes(e.detail); });

    /* ═══════════ Modal journée d'une salle ═══════════ */

    var voile = document.getElementById('pjVoile');
    var corps = voile ? document.getElementById('pjCorps') : null;
    var titre = voile ? document.getElementById('pjTitre') : null;
    var csrf = document.getElementById('pjCsrf');
    var organisateurDefaut = (document.getElementById('pjOrganisateur') || {}).value || 'Administration du centre';

    function ouvrirVoile() {
        if (!voile) { return; }
        voile.hidden = false;
        document.body.style.overflow = 'hidden';
    }

    function ouvrirJournee(salleId, salleNom, date, heureClic, heureClicFin) {
        if (!voile) { return; }
        titre.textContent = salleNom + ' · ' + G.dateFR(date);
        corps.innerHTML = '<p class="pj-chargement">Chargement de la journée…</p>';
        ouvrirVoile();

        fetch((voile.getAttribute('data-url-jour') || D.urls.jour) + '?salle_id=' + encodeURIComponent(salleId) + '&date=' + encodeURIComponent(date), {
            credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.erreur) { corps.innerHTML = '<p class="pj-erreur">' + echapper(d.erreur) + '</p>'; return; }
                d.salleNom = salleNom;
                renduJournee(d, heureClic, heureClicFin);
            })
            .catch(function () {
                corps.innerHTML = '<p class="pj-erreur">Impossible de charger la journée. Vérifiez votre connexion puis réessayez.</p>';
            });
    }

    function renduJournee(d, heureClic, heureClicFin) {
        var html = '';

        html += '<div class="pj-fiche-salle"><i class="fas fa-door-open"></i><div><strong>' + echapper(d.salleNom) + '</strong>';
        if (d.salle) {
            html += '<span>' + (d.salle.capacite || '?') + ' places' +
                (d.salle.localisation ? ' · ' + echapper(d.salle.localisation) : '') + '</span>';
        }
        html += '</div></div>';

        if (d.salle && d.salle.maintenance) {
            html += '<div class="pj-alerte"><i class="fas fa-tools"></i> Cette salle est actuellement en maintenance : ' +
                'elle n\'accepte pas de nouvelle programmation.</div>';
        }

        if (d.occurrences && d.occurrences.length > 0) {
            html += '<h4 class="pj-section"><i class="fas fa-calendar-day"></i> Déroulé de la journée</h4>';
            html += '<div class="pj-evenements">';
            d.occurrences.forEach(function (o) {
                var cl = o.statut === 'OCCUPEE' ? 'st-occupe' : o.statut === 'CONFIRMEE' ? 'st-reserve' : o.statut === 'A_REPORTER' ? 'st-reporter' : 'st-attente';
                html += '<div class="pj-evt ' + cl + '"><div class="pj-evt-barre"></div><div class="pj-evt-corps">';
                html += '<div class="pj-evutile"><span class="pj-evt-horaires">' +
                    heureCourte(o.heure_debut) + ' – ' + heureCourte(o.heure_fin) + '</span>' +
                    '<span class="pj-evt-duree">' + G.dureeTexte(o.heure_debut, o.heure_fin) + '</span>' +
                    '<span class="pj-badge b-' + o.statut + '">' + ((D.libellesStatut || {})[o.statut] || o.statut) + '</span></div>';
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

        if (!(d.salle && d.salle.maintenance) && d.creneaux_libres && d.creneaux_libres.length > 0 && D.peutGerer) {
            html += '<h4 class="pj-section"><i class="fas fa-plus-circle"></i> Programmer sur un créneau libre</h4>';
            html += '<div class="pj-libres">';
            d.creneaux_libres.forEach(function (z) {
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
                formulaireProgrammation(d, btn.getAttribute('data-debut'), btn.getAttribute('data-fin'));
            });
        });

        /* heure cliquée ou plage glissée : ouverture directe du formulaire */
        if (heureClic && D.peutGerer && !(d.salle && d.salle.maintenance)) {
            var zone = null;
            (d.creneaux_libres || []).forEach(function (z) {
                if (z.debut <= heureClic && heureClic < z.fin) { zone = z; }
            });
            if (zone) {
                var finVoulue = heureClicFin && heureClicFin > heureClic && heureClicFin <= zone.fin ? heureClicFin : null;
                formulaireProgrammation(d, heureClic, zone.fin, finVoulue);
            }
        }
    }

    /* ═══════════ Formulaire de programmation ═══════════ */

    function formulaireProgrammation(d, heureDebut, borneFin, finVoulue, libre) {
        var zone = libre
            ? { debut: (D.ouverture < 10 ? '0' + D.ouverture : '' + D.ouverture) + ':00', fin: D.fermeture + ':00' }
            : { debut: heureDebut, fin: borneFin || D.fermeture + ':00' };
        var types = D.categories || ['Conférence', 'Réunion', 'Formation', 'Atelier', 'Séminaire', 'Examen', 'Autre'];

        var html = '';
        html += '<button type="button" class="btn btn-petit btn-discret pj-retour"><i class="fas fa-arrow-left"></i> Retour à la journée</button>';
        html += '<h4 class="pj-form-titre">Programmer un événement le ' + G.dateFR(d.date) + '</h4>';
        html += '<form id="pjForm" class="pj-form">';
        html += '<label>Type d\'événement <span class="obligatoire">*</span><select id="pjType">';
        types.forEach(function (t) { html += '<option' + (t === 'Réunion' ? ' selected' : '') + '>' + t + '</option>'; });
        html += '</select></label>';
        html += '<label>Intitulé <span class="obligatoire">*</span><input type="text" id="pjEvenement" maxlength="200" value="Réunion" required></label>';
        html += '<label>Client / organisation<input type="text" id="pjOrganisation" maxlength="150"></label>';
        html += '<label>Du <span class="obligatoire">*</span><input type="date" id="pjDateDebut" value="' + d.date + '" required></label>';
        html += '<label>Au ( multi-jours )<input type="date" id="pjDateFin" min="' + d.date + '"></label>';
        html += '<label>Heure de début <span class="obligatoire">*</span><select id="pjDebut">';
        heuresDemi().forEach(function (h) {
            var ok = h >= zone.debut && h < zone.fin;
            html += '<option value="' + h + '"' + (h === heureDebut ? ' selected' : '') + (ok ? '' : ' disabled') + '>' + heureCourte(h) + '</option>';
        });
        html += '</select></label>';
        html += '<label>Heure de fin <span class="obligatoire">*</span><select id="pjFin"></select></label>';
        html += '<label>Organisé par <span class="obligatoire">*</span><input type="text" id="pjOrganisateur" maxlength="150" value="' + echapper(organisateurDefaut) + '" required></label>';
        html += '<label>Formateur<select id="pjFormateur"><option value="">Non affecté</option>';
        (D.formateurs || []).forEach(function (f) {
            html += '<option value="' + f.id + '">' + echapper(f.prenom + ' ' + f.nom) + '</option>';
        });
        html += '</select></label>';
        html += '<label>Nombre de personnes<input type="number" id="pjNb" min="0" max="2000" value="' + (d.salle ? d.salle.capacite : '') + '"></label>';
        html += '<label>Email de contact<input type="email" id="pjEmail"></label>';
        html += '<label>Téléphone<input type="tel" id="pjTel"></label>';
        html += '<p class="pj-erreur pj-message" id="pjMessage" hidden></p>';
        html += '<div class="pj-actions"><button type="submit" class="btn btn-primaire"><i class="fas fa-calendar-plus"></i> Enregistrer la programmation</button></div>';
        html += '<p class="aide-champ">Créneau libre visé : de ' + heureCourte(zone.debut) + ' à ' + heureCourte(zone.fin) +
            ' : les heures proposées s\'arrêtent à la prochaine occupation.</p>';
        html += '</form>';

        corps.innerHTML = html;
        majFins(zone, heureDebut, finVoulue);

        var typePrecedent = document.getElementById('pjType').value;
        document.getElementById('pjType').addEventListener('change', function () {
            var champ = document.getElementById('pjEvenement');
            /* pré-remplit l'intitulé avec le type, sans écraser une saisie personnalisée */
            if (!champ.value.trim() || champ.value.trim() === typePrecedent) {
                champ.value = this.value;
            }
            typePrecedent = this.value;
        });
        document.getElementById('pjDateFin').addEventListener('change', function () {
            this.min = document.getElementById('pjDateDebut').value;
            if (this.value && this.value < this.min) { this.value = ''; }
        });
        document.getElementById('pjDebut').addEventListener('change', function () { majFins(zone, this.value, null); });
        corps.querySelector('.pj-retour').addEventListener('click', function () { renduJournee(d, null, null); });
        document.getElementById('pjForm').addEventListener('submit', function (e) {
            e.preventDefault();
            envoyerProgrammation(d);
        });
    }

    function majFins(zone, debutChoisi, preselection) {
        var selFin = document.getElementById('pjFin');
        var options = '', premiere = null;
        heuresDemi().forEach(function (h) {
            if (h > debutChoisi && h <= zone.fin) {
                if (!premiere) { premiere = h; }
                options += '<option value="' + h + '">' + heureCourte(h) + '</option>';
            }
        });
        selFin.innerHTML = options;
        if (preselection && preselection > debutChoisi && preselection <= zone.fin) {
            selFin.value = preselection;
        } else if (premiere) {
            selFin.value = premiere;
        }
    }

    function envoyerProgrammation(d) {
        var donnees = new FormData();
        donnees.append('_token', csrf.value);
        donnees.append('salle_id', d.salle.id);
        donnees.append('evenement', document.getElementById('pjEvenement').value.trim());
        donnees.append('categorie', document.getElementById('pjType').value);
        donnees.append('organisation', document.getElementById('pjOrganisation').value.trim());
        donnees.append('date_reservation', document.getElementById('pjDateDebut').value);
        donnees.append('date_fin', document.getElementById('pjDateFin').value);
        donnees.append('heure_debut', document.getElementById('pjDebut').value);
        donnees.append('heure_fin', document.getElementById('pjFin').value);
        donnees.append('demandeur_nom', document.getElementById('pjOrganisateur').value.trim());
        donnees.append('formateur_id', document.getElementById('pjFormateur').value);
        donnees.append('nb_personnes', document.getElementById('pjNb').value);
        donnees.append('email', document.getElementById('pjEmail').value.trim());
        donnees.append('telephone', document.getElementById('pjTel').value.trim());

        envoyerEtGerer(donnees, D.urls.programmer);
    }

    function envoyerEtGerer(donnees, urlCible) {
        var message = document.getElementById('pjMessage') || document.getElementById('peMessage');
        if (message) { message.hidden = true; }

        fetch(urlCible, {
            method: 'POST',
            credentials: 'same-origin',
            body: new URLSearchParams(donnees),
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { return r.json(); })
            .then(function (rep) {
                if (rep.ok) {
                    fermerTout();
                    window.location.reload();
                    return;
                }
                if (message) { message.textContent = rep.erreur || 'Enregistrement impossible.'; message.hidden = false; }
            })
            .catch(function () {
                if (message) { message.textContent = 'Envoi impossible : vérifiez votre connexion puis réessayez.'; message.hidden = false; }
            });
    }

    /* ═══════════ Fiche détail : consultation + modification + suppression ═══════════ */

    var peVoile = document.getElementById('peVoile');
    var peCorps = document.getElementById('peCorps');
    var peTitre = document.getElementById('peTitre');
    var peStatut = document.getElementById('peStatut');
    var ficheActive = null;

    var PlanningFiche = {

        ouvrir: function (r) {
            ficheActive = r;
            peTitre.textContent = r.evenement || 'Réservation';
            var st = (D.libellesStatut || {})[r.statut] || r.statut;
            peStatut.textContent = st;
            peStatut.className = 'pe-statut pe-s-' + r.statut;
            this.consultation(r);
            peVoile.hidden = false;
            document.body.style.overflow = 'hidden';
        },

        consultation: function (r) {
            var html = '';
            html += '<div class="pe-horaires"><i class="fas fa-clock"></i>' +
                heureCourte(r.heure_debut) + ' – ' + heureCourte(r.heure_fin) +
                '<span>' + G.dureeTexte(r.heure_debut, r.heure_fin) + '</span></div>';
            html += '<div class="pe-grille">';
            html += '<div class="pe-ligne"><span class="pe-label"><i class="fas fa-calendar"></i> Dates</span><span>Du ' + G.dateFR(r.date_debut) +
                (r.date_fin && r.date_fin !== r.date_debut ? ' au ' + G.dateFR(r.date_fin) : '') + '</span></div>';
            html += '<div class="pe-ligne"><span class="pe-label"><i class="fas fa-door-open"></i> Salle</span><span>' + echapper(r.salle_nom || r.salle) + '</span></div>';
            if (r.categorie) {
                html += '<div class="pe-ligne"><span class="pe-label"><i class="fas fa-tag"></i> Catégorie</span><span>' + echapper(r.categorie) + '</span></div>';
            }
            if (r.demandeur_nom) {
                html += '<div class="pe-ligne"><span class="pe-label"><i class="fas fa-user"></i> Organisateur</span><span>' + echapper(r.demandeur_nom) + '</span></div>';
            }
            if (r.organisation) {
                html += '<div class="pe-ligne"><span class="pe-label"><i class="fas fa-building"></i> Client / organisation</span><span>' + echapper(r.organisation) + '</span></div>';
            }
            if (r.formateur_nom || r.formateur_id) {
                var nomF = r.formateur_nom || nomFormateur(r.formateur_id);
                if (nomF) { html += '<div class="pe-ligne"><span class="pe-label"><i class="fas fa-chalkboard-teacher"></i> Formateur</span><span>' + echapper(nomF) + '</span></div>'; }
            }
            if (r.nb_personnes) {
                html += '<div class="pe-ligne"><span class="pe-label"><i class="fas fa-users"></i> Effectif</span><span>' + echapper(r.nb_personnes) + ' personnes</span></div>';
            }
            if (r.email) {
                html += '<div class="pe-ligne"><span class="pe-label"><i class="fas fa-envelope"></i> Email</span><span>' + echapper(r.email) + '</span></div>';
            }
            if (r.telephone) {
                html += '<div class="pe-ligne"><span class="pe-label"><i class="fas fa-phone"></i> Téléphone</span><span>' + echapper(r.telephone) + '</span></div>';
            }
            html += '</div>';

            if (D.peutGerer) {
                html += '<div class="pe-boutons">' +
                    '<button type="button" class="btn btn-primaire" id="peBtnModifier"><i class="fas fa-edit"></i> Modifier</button>' +
                    '<button type="button" class="btn btn-rouge" id="peBtnSupprimer"><i class="fas fa-trash"></i> Supprimer</button>' +
                    '</div>';
            }

            peCorps.innerHTML = html;

            if (D.peutGerer) {
                document.getElementById('peBtnModifier').addEventListener('click', function () {
                    PlanningFiche.formulaire(r);
                });
                document.getElementById('peBtnSupprimer').addEventListener('click', function () {
                    if (!window.confirm('Supprimer définitivement « ' + r.evenement + ' » ?')) { return; }
                    var donnees = new FormData();
                    donnees.append('_token', csrf.value);
                    donnees.append('id', r.id);
                    fetch(D.urls.supprimer, {
                        method: 'POST', credentials: 'same-origin',
                        body: new URLSearchParams(donnees),
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                        .then(function (rep) { return rep.json(); })
                        .then(function (rep) {
                            if (rep.ok) { fermerTout(); window.location.reload(); return; }
                            window.alert(rep.erreur || 'Suppression impossible.');
                        })
                        .catch(function () { window.alert('Envoi impossible : vérifiez votre connexion.'); });
                });
            }
        },

        formulaire: function (r) {
            var types = D.categories || [];
            var statuts = D.statuts || ['EN_ATTENTE', 'CONFIRMEE', 'OCCUPEE', 'A_REPORTER'];
            var salleCourante = r.salle_id;

            var html = '';
            html += '<button type="button" class="btn btn-petit btn-discret pj-retour" id="peRetour"><i class="fas fa-arrow-left"></i> Retour à la fiche</button>';
            html += '<form id="peForm" class="pj-form">';
            html += '<label>Intitulé <span class="obligatoire">*</span><input type="text" id="peEvenement" maxlength="200" value="' + echapper(r.evenement) + '" required></label>';
            html += '<label>Catégorie<select id="peCategorie">';
            types.forEach(function (t) {
                html += '<option' + (t === r.categorie ? ' selected' : '') + '>' + t + '</option>';
            });
            if (!r.categorie) { html = html.replace('<option>', '<option selected>'); }
            html += '</select></label>';
            html += '<label>Salle <span class="obligatoire">*</span><select id="peSalle">';
            (D.salles || []).forEach(function (s) {
                html += '<option value="' + s.id + '"' + (String(s.id) === String(salleCourante) ? ' selected' : '') + '>' + echapper(s.nom) + '</option>';
            });
            html += '</select></label>';
            html += '<label>Du <span class="obligatoire">*</span><input type="date" id="peDateDebut" value="' + r.date_debut + '" required></label>';
            html += '<label>Au ( multi-jours )<input type="date" id="peDateFin" value="' + (r.date_fin && r.date_fin !== r.date_debut ? r.date_fin : '') + '"></label>';
            html += '<label>Heure de début <span class="obligatoire">*</span><select id="peDebut">';
            heuresDemi().forEach(function (h) {
                html += '<option value="' + h + '"' + (h === r.heure_debut ? ' selected' : '') + '>' + heureCourte(h) + '</option>';
            });
            html += '</select></label>';
            html += '<label>Heure de fin <span class="obligatoire">*</span><select id="peFin">';
            heuresDemi().forEach(function (h) {
                html += '<option value="' + h + '"' + (h === r.heure_fin ? ' selected' : '') + '>' + heureCourte(h) + '</option>';
            });
            html += '</select></label>';
            html += '<label>Statut<select id="peStatutSel">';
            statuts.forEach(function (s) {
                html += '<option value="' + s + '"' + (s === r.statut ? ' selected' : '') + '>' + ((D.libellesStatut || {})[s] || s) + '</option>';
            });
            html += '</select></label>';
            html += '<label>Organisateur <span class="obligatoire">*</span><input type="text" id="peOrganisateur" maxlength="150" value="' + echapper(r.demandeur_nom || '') + '" required></label>';
            html += '<label>Client / organisation<input type="text" id="peOrganisation" maxlength="150" value="' + echapper(r.organisation || '') + '"></label>';
            html += '<label>Formateur<select id="peFormateur"><option value="">Non affecté</option>';
            (D.formateurs || []).forEach(function (f) {
                html += '<option value="' + f.id + '"' + (String(f.id) === String(r.formateur_id) ? ' selected' : '') + '>' + echapper(f.prenom + ' ' + f.nom) + '</option>';
            });
            html += '</select></label>';
            html += '<label>Nombre de personnes<input type="number" id="peNb" min="0" max="2000" value="' + echapper(r.nb_personnes || '') + '"></label>';
            html += '<label>Email<input type="email" id="peEmail" value="' + echapper(r.email || '') + '"></label>';
            html += '<label>Téléphone<input type="tel" id="peTel" value="' + echapper(r.telephone || '') + '"></label>';
            html += '<p class="pj-erreur pj-message" id="peMessage" hidden></p>';
            html += '<div class="pj-actions"><button type="submit" class="btn btn-primaire"><i class="fas fa-save"></i> Enregistrer les modifications</button></div>';
            html += '</form>';

            peCorps.innerHTML = html;

            document.getElementById('peRetour').addEventListener('click', function () { PlanningFiche.consultation(r); });
            document.getElementById('peForm').addEventListener('submit', function (e) {
                e.preventDefault();
                var donnees = new FormData();
                donnees.append('_token', csrf.value);
                donnees.append('id', r.id);
                donnees.append('evenement', document.getElementById('peEvenement').value.trim());
                donnees.append('categorie', document.getElementById('peCategorie').value);
                donnees.append('salle_id', document.getElementById('peSalle').value);
                donnees.append('date_reservation', document.getElementById('peDateDebut').value);
                donnees.append('date_fin', document.getElementById('peDateFin').value);
                donnees.append('heure_debut', document.getElementById('peDebut').value);
                donnees.append('heure_fin', document.getElementById('peFin').value);
                donnees.append('statut', document.getElementById('peStatutSel').value);
                donnees.append('demandeur_nom', document.getElementById('peOrganisateur').value.trim());
                donnees.append('organisation', document.getElementById('peOrganisation').value.trim());
                donnees.append('formateur_id', document.getElementById('peFormateur').value);
                donnees.append('nb_personnes', document.getElementById('peNb').value);
                donnees.append('email', document.getElementById('peEmail').value.trim());
                donnees.append('telephone', document.getElementById('peTel').value.trim());
                envoyerEtGerer(donnees, D.urls.modifier);
            });
        }
    };

    function nomFormateur(id) {
        var f = (D.formateurs || []).filter(function (x) { return String(x.id) === String(id); })[0];
        return f ? f.prenom + ' ' + f.nom : null;
    }

    /* Création directe ( zone vide de la vue Ressources ) : formulaire
       de programmation prérempli avec la ressource, la date et l'horaire. */
    PlanningFiche.creer = function (prefill) {
        if (peVoile && !peVoile.hidden) { peVoile.hidden = true; }
        if (!voile) { return; }
        var d = {
            salle: prefill.salle_id ? { id: prefill.salle_id } : null,
            date: prefill.date,
            salleNom: prefill.salle_nom || 'Création'
        };
        if (!d.salle && D.salles && D.salles.length) {
            d.salle = { id: D.salles[0].id };
        }
        titre.textContent = 'Nouvelle réservation · ' + G.dateFR(prefill.date);
        ouvrirVoile();
        formulaireProgrammation(d, prefill.heure_debut, null, prefill.heure_fin, true);

        /* préremplissages supplémentaires hors créneau */
        if (prefill.formateur_id) {
            var selF = document.getElementById('pjFormateur');
            if (selF) { selF.value = String(prefill.formateur_id); }
        }
        if (prefill.organisation) {
            var champO = document.getElementById('pjOrganisation');
            if (champO) { champO.value = prefill.organisation; }
        }
        if (prefill.date_fin) {
            var champF = document.getElementById('pjDateFin');
            if (champF) { champF.value = prefill.date_fin; }
        }
    };

    window.PlanningFiche = PlanningFiche;

    /* ═══════════ Fermetures ═══════════ */

    function fermerTout() {
        if (voile) { voile.hidden = true; }
        if (peVoile) { peVoile.hidden = true; }
        cacherTooltip();
        document.body.style.overflow = '';
    }

    if (voile) {
        document.getElementById('pjFermer').addEventListener('click', fermerTout);
        voile.addEventListener('click', function (e) { if (e.target === voile) { fermerTout(); } });
    }
    if (peVoile) {
        document.getElementById('peFermer').addEventListener('click', fermerTout);
        peVoile.addEventListener('click', function (e) { if (e.target === peVoile) { fermerTout(); } });
    }
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && ((voile && !voile.hidden) || (peVoile && !peVoile.hidden))) { fermerTout(); }
    });

    /* Synthèse : clic sur une case → journée */
    Array.prototype.forEach.call(document.querySelectorAll('.pl-syn-case[data-salle-id]'), function (c) {
        function lancer() {
            ouvrirJournee(c.getAttribute('data-salle-id'), c.getAttribute('data-salle-nom'), c.getAttribute('data-date'));
        }
        c.addEventListener('click', lancer);
        c.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); lancer(); }
        });
    });

    /* Premier rendu de la vue 1 */
    positionnerEtColorer();
})();
