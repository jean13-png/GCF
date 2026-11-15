/* ═════════════════════════════════════════════════════════════════
   GCF · Vue « Ressources » : planning Gantt multi-ressources sur
   plusieurs semaines. Colonne de gauche et échelle figées, groupes
   repliables, blocs demi-journées, glisser-déposer, redimensionnement
   et vérification des conflits côté serveur.
   ═════════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    var conteneur = document.getElementById('gaConteneur');
    if (!conteneur || !window.Gantt || !window.PLANNING_DATA) { return; }

    var G = window.Gantt;
    var D = window.PLANNING_DATA;
    var csrf = document.getElementById('pjCsrf');

    var selAffichage = document.getElementById('gaAffichage');
    var selCouleur = document.getElementById('gaCouleur');
    var cocherCondenser = document.getElementById('gaCondenser');
    var cocherWeekends = document.getElementById('gaWeekends');
    var cocherAlertes = document.getElementById('gaAlertes');
    var btnActualiser = document.getElementById('gaActualiser');

    var HAUT_LIGNE = 45;
    var debut = conteneur.getAttribute('data-debut') || D.semaineDebut;
    var semaines = 8; /* semaines initialement chargées ( max 26 au scroll ) */
    var donnees = null;
    var groupesReplie = {};
    var chargementEnCours = false;

    /* ═══════════ Données ═══════════ */

    function charger(callback) {
        if (chargementEnCours) { return; }
        chargementEnCours = true;
        if (!conteneur.querySelector('.ga2-scroll')) {
            conteneur.innerHTML = '<p class="pj-chargement">Chargement du planning des ressources…</p>';
        }
        fetch(D.urls.donnees + '?debut=' + encodeURIComponent(debut) + '&semaines=' + semaines, {
            credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { return r.json(); })
            .then(function (rep) {
                donnees = rep;
                chargementEnCours = false;
                if (callback) { callback(); }
            })
            .catch(function () {
                chargementEnCours = false;
                conteneur.innerHTML = '<p class="pj-erreur">Impossible de charger le planning des ressources. ' +
                    '<button type="button" class="btn btn-petit btn-discret" onclick="window.location.reload()">Réessayer</button></p>';
            });
    }

    /* ═══════════ Structure des lignes selon le mode « Affichage » ═══════════ */

    function cleClient(r) { return r.client || 'Sans client'; }

    function construireStructure() {
        var mode = selAffichage ? selAffichage.value : 'site-salle';
        var salles = donnees.salles || [];
        var formateurs = (donnees.formateurs || []).map(function (f) {
            return { cle: 'F' + f.id, libelle: f.prenom + ' ' + f.nom, initiales: G.initiales(f.nom, f.prenom), formateur: f };
        });
        var nonAffecte = { cle: 'F0', libelle: 'Non affecté', initiales: '—' };

        var clients = {}, groupes = {}, lignes = [];

        (donnees.reservations || []).forEach(function (r) { clients[cleClient(r)] = true; });
        var listeClients = Object.keys(clients).sort();
        if (!listeClients.length) { listeClients = ['Sans client']; }

        function siteDe(r) {
            for (var i = 0; i < salles.length; i++) {
                if (String(salles[i].id) === String(r.salle_id)) { return salles[i].site; }
            }
            return 'Cotonou';
        }

        var structure = { mode: mode, groupes: [] };

        function ajouterGroupe(nom, cle, lignesGroupe) {
            var g = { nom: nom, cle: cle, lignes: lignesGroupe };
            structure.groupes.push(g);
        }

        if (mode === 'site-salle' || mode === 'defaut') {
            var parSite = {};
            salles.forEach(function (s) {
                (parSite[s.site] = parSite[s.site] || []).push(s);
            });
            Object.keys(parSite).sort().forEach(function (site) {
                var lg = parSite[site].map(function (s) {
                    return { cle: 'S' + s.id, libelle: s.nom, salle: s };
                });
                if (mode === 'defaut') {
                    lg.forEach(function (l) { lignes.push(l); });
                    structure.groupes.push({ nom: null, lignes: lg });
                } else {
                    ajouterGroupe(site, 'site-' + site, lg);
                }
            });
            structure.affectation = function (r) { return 'S' + r.salle_id; };
        } else if (mode === 'site-formateur') {
            var sites = {};
            salles.forEach(function (s) { sites[s.site] = true; });
            Object.keys(sites).sort().forEach(function (site) {
                ajouterGroupe(site, 'site-' + site, formateurs.concat([nonAffecte]));
            });
            structure.affectation = function (r) { return r.formateur_id ? 'F' + r.formateur_id : 'F0'; };
        } else if (mode === 'client-formateur') {
            listeClients.forEach(function (c) {
                ajouterGroupe(c, 'cli-' + c, formateurs.concat([nonAffecte]));
            });
            structure.affectation = function (r) { return r.formateur_id ? 'F' + r.formateur_id : 'F0'; };
        } else { /* formateur-client */
            formateurs.concat([nonAffecte]).forEach(function (f) {
                var lg = listeClients.map(function (c) {
                    return { cle: 'C' + c, libelle: c, client: c };
                });
                ajouterGroupe(f.libelle, f.cle, lg);
            });
            structure.affectation = function (r) { return 'C' + cleClient(r); };
        }

        return structure;
    }

    /* Lignes en surbrillance : salles en maintenance */
    function ligneIndisponible(ligne) {
        return !!(ligne.salle && ligne.salle.maintenance);
    }

    /* ═══════════ Échelle de temps ═══════════ */

    function joursVisibles() {
        var avecWeekends = cocherWeekends && cocherWeekends.checked;
        var out = [];
        var d = G.dateDe(debut);
        var total = semaines * 7;
        for (var i = 0; i < total; i++) {
            var jour = new Date(d.getFullYear(), d.getMonth(), d.getDate() + i);
            var dow = jour.getDay();
            if (avecWeekends || (dow >= 1 && dow <= 5)) {
                out.push(G.verrouille(jour));
            }
        }
        return out;
    }

    function largeurDemi() {
        return (cocherCondenser && cocherCondenser.checked) ? 34 : 56;
    }

    /* ═══════════ Colonnes d'une réservation ═══════════ */

    function colonnesResa(r, jours) {
        var n = jours.length * 2;
        var cd = G.colonneDe(r.date_debut, G.demiDe(r.heure_debut), jours);
        var cf = G.colonneDe(r.date_fin, demiFinDe(r.heure_fin), jours);
        if (cd < 0) { cd = 0; }
        if (cf < 0 || cf >= n) { cf = n - 1; }
        if (cf < cd) { cf = cd; }
        return { debut: cd, fin: cf };
    }

    /* une fin à 12h pile termine la demi-journée du matin */
    function demiFinDe(h) {
        var p = String(h || '12:00').split(':');
        return (+p[0]) * 60 + (+(p[1] || 0)) <= 720 ? 'AM' : 'PM';
    }

    /* ═══════════ Rendu ═══════════ */

    function critereCourant() { return selCouleur ? selCouleur.value : 'categorie'; }

    function valeurCritere(r) {
        var c = critereCourant();
        if (c === 'client') { return r.client || 'Sans client'; }
        if (c === 'salle') { return r.salle_nom || ''; }
        if (c === 'statut') { return r.statut; }
        return r.categorie || 'Non classé';
    }

    function rendre() {
        if (!donnees) { return; }
        var jours = joursVisibles();
        var colonnes = jours.length * 2;
        var colw = largeurDemi();
        var structure = construireStructure();

        var scroll = document.createElement('div');
        scroll.className = 'ga2-scroll';
        scroll.style.setProperty('--demi', colw + 'px');

        var table = document.createElement('div');
        table.className = 'ga2-table';
        table.style.width = (215 + colonnes * colw) + 'px';

        /* ── Échelle sticky : semaines + numéros de jours ── */
        var head = document.createElement('div');
        head.className = 'ga2-head';
        var coin = document.createElement('div');
        coin.className = 'ga2-coin';
        coin.innerHTML = '<span>Ressources</span>';
        head.appendChild(coin);

        var rangSemaines = document.createElement('div');
        rangSemaines.className = 'ga2-rang-semaines';
        var rangJours = document.createElement('div');
        rangJours.className = 'ga2-rang-jours';

        var semainesVues = {};
        jours.forEach(function (iso) {
            var s = G.semaineISO(iso);
            var cle = s.annee + '-S' + s.numero;
            if (!semainesVues[cle]) {
                semainesVues[cle] = { label: 'S' + String(s.numero).padStart(2, '0') + ' - ' +
                    String(s.numero).padStart(2, '0') + '/' + String(s.annee).slice(-2), jours: [] };
            }
            semainesVues[cle].jours.push(iso);
        });
        Object.keys(semainesVues).forEach(function (cle) {
            var sv = semainesVues[cle];
            var c = document.createElement('div');
            c.className = 'ga2-semaine';
            c.style.width = (sv.jours.length * 2 * colw) + 'px';
            c.textContent = sv.label;
            c.title = 'Semaine du ' + G.dateFR(sv.jours[0]);
            rangSemaines.appendChild(c);
        });
        jours.forEach(function (iso) {
            var c = document.createElement('div');
            c.className = 'ga2-jour' + (estAujourdhui(iso) ? ' aujourdhui' : '');
            c.style.width = (2 * colw) + 'px';
            c.textContent = String(G.dateDe(iso).getDate()).padStart(2, '0');
            c.title = G.dateFR(iso);
            rangJours.appendChild(c);
        });

        var zoneSemaines = document.createElement('div');
        zoneSemaines.className = 'ga2-head-droite';
        zoneSemaines.appendChild(rangSemaines);
        var zoneJours = document.createElement('div');
        zoneJours.className = 'ga2-head-droite';
        zoneJours.appendChild(rangJours);
        head.appendChild(zoneSemaines);
        head.appendChild(zoneJours);
        table.appendChild(head);

        /* ── Corps : gauche ( ressources ) + pistes ── */
        var corps = document.createElement('div');
        corps.className = 'ga2-corps';

        var gauche = document.createElement('div');
        gauche.className = 'ga2-gauche';

        var pistes = document.createElement('div');
        pistes.className = 'ga2-pistes';
        pistes.style.width = (colonnes * colw) + 'px';

        /* traits verticaux de la grille ( demi / jour / semaine ) */
        var fond = document.createElement('div');
        fond.className = 'ga2-fond';
        for (var i = 0; i <= colonnes; i++) {
            var trait = document.createElement('div');
            trait.className = 'ga2-trait';
            trait.style.left = (i / colonnes * 100) + '%';
            pistes.appendChild(trait);
        }

        var resaParLigne = {};
        (donnees.reservations || []).forEach(function (r) {
            var cle = structure.affectation(r);
            (resaParLigne[cle] = resaParLigne[cle] || []).push(r);
        });

        var valeursLegende = {};

        structure.groupes.forEach(function (groupe) {
            if (groupe.nom !== null) {
                var tete = document.createElement('div');
                tete.className = 'ga2-groupe-tete' + (groupesReplie[groupe.cle] ? ' replie' : '');
                tete.innerHTML = '<button type="button" class="ga2-replier" aria-label="Replier le groupe">' +
                    '<i class="fas ' + (groupesReplie[groupe.cle] ? 'fa-plus' : 'fa-minus') + '"></i></button>' +
                    '<span>' + G.echapper(groupe.nom) + '</span>';
                gauche.appendChild(tete);
                var espacePiste = document.createElement('div');
                espacePiste.className = 'ga2-groupe-espace';
                pistes.appendChild(espacePiste);
                tete.querySelector('.ga2-replier').addEventListener('click', function () {
                    groupesReplie[groupe.cle] = !groupesReplie[groupe.cle];
                    rendre();
                });
            }

            groupe.lignes.forEach(function (ligne) {
                var masque = groupesReplie[groupe.cle];
                var cellule = document.createElement('div');
                cellule.className = 'ga2-ressource' + (ligneIndisponible(ligne) ? ' indisponible' : '') + (masque ? ' masque' : '');
                cellule.innerHTML = '<span class="ga2-ressource-nom">' + G.echapper(ligne.libelle) + '</span>' +
                    '<span class="ga2-ressource-init">[' + G.echapper(ligne.initiales || '—') + ']</span>';
                gauche.appendChild(cellule);

                var piste = document.createElement('div');
                piste.className = 'ga2-ligne' + (ligneIndisponible(ligne) ? ' indisponible' : '') + (masque ? ' masque' : '');
                piste.style.width = (colonnes * colw) + 'px';
                piste.dataset.cleLigne = ligne.cle;

                var resas = resaParLigne[ligne.cle] || [];
                var blocs = resas.map(function (r) {
                    var c = colonnesResa(r, jours);
                    return { r: r, c: c };
                });

                /* empilement : sous-couleurs pour les chevauchements */
                var sousCouche = [];
                blocs.forEach(function (b) {
                    var k = 0;
                    while (sousCouche[k] !== undefined && sousCouche[k] > b.c.debut) { k++; }
                    b.couche = k;
                    sousCouche[k] = b.c.fin;
                });
                var nbCouches = Math.max(1, sousCouche.length);
                piste.style.height = (HAUT_LIGNE * nbCouches) + 'px';
                gaucheLast(cellule).style.height = (HAUT_LIGNE * nbCouches) + 'px';

                blocs.forEach(function (b) {
                    var r = b.r;
                    var p = G.poser({ type: 'demiJournees', colonnes: colonnes }, b.c.debut, b.c.fin + 1);
                    var bloc = document.createElement('div');
                    var reporter = r.statut === 'A_REPORTER';
                    bloc.className = 'ga2-bloc' + (reporter ? ' a-reporter' : '');
                    bloc.style.left = p.gauche + '%';
                    bloc.style.width = p.largeur + '%';
                    bloc.style.top = (b.couche * HAUT_LIGNE + 4) + 'px';
                    bloc.style.height = (HAUT_LIGNE - 9) + 'px';
                    if (!reporter) {
                        var cle = valeurCritere(r);
                        /* code couleur des statuts : attente orange, réservé bleu,
                           occupé rouge ( libre = fond vert des lignes ) */
                        var coul = (critereCourant() === 'statut')
                            ? G.couleurStatut(r.statut)
                            : G.couleur(cle);
                        bloc.style.setProperty('--ga-fond', coul.fond);
                        bloc.style.setProperty('--ga-fond-haut', couleurSoutenue(coul.fond));
                        bloc.style.setProperty('--ga-bord', coul.bord);
                        bloc.style.setProperty('--ga-texte', coul.texte);
                        valeursLegende[cle] = true;
                    } else {
                        valeursLegende['A_REPORTER'] = true;
                    }
                    bloc.innerHTML = '<span class="ga2-bloc-titre">' + G.echapper(r.evenement) + '</span>' +
                        '<span class="ga2-bloc-salle">' + G.echapper(r.salle_nom) + '</span>' +
                        '<span class="ga2-bloc-client">' + G.echapper(String(r.client || '').toUpperCase()) + '</span>';
                    bloc.dataset.id = r.id;
                    attacherBloc(bloc, r, piste, ligne, structure, jours, colonnes);
                    piste.appendChild(bloc);
                });

                /* créneaux libres : encadrés de vert, étiquette « Libre » */
                if (!ligneIndisponible(ligne)) {
                    var occupees = new Array(colonnes);
                    blocs.forEach(function (b) {
                        for (var ic = Math.max(0, b.c.debut); ic <= Math.min(colonnes - 1, b.c.fin); ic++) {
                            occupees[ic] = true;
                        }
                    });
                    var debutLibre = -1;
                    for (var jc = 0; jc <= colonnes; jc++) {
                        var estLibre = jc < colonnes && !occupees[jc];
                        if (estLibre && debutLibre < 0) { debutLibre = jc; }
                        if (!estLibre && debutLibre >= 0) {
                            var pl = G.poser({ type: 'demiJournees', colonnes: colonnes }, debutLibre, jc);
                            var segLibre = document.createElement('div');
                            segLibre.className = 'ga2-libre';
                            segLibre.style.left = pl.gauche + '%';
                            segLibre.style.width = pl.largeur + '%';
                            segLibre.style.top = '4px';
                            segLibre.style.height = (HAUT_LIGNE * nbCouches - 9) + 'px';
                            segLibre.innerHTML = '<span>Libre</span>';
                            piste.appendChild(segLibre);
                            debutLibre = -1;
                        }
                    }
                }

                pistes.appendChild(piste);
            });
        });

        corps.appendChild(gauche);
        corps.appendChild(pistes);
        table.appendChild(corps);
        scroll.appendChild(table);
        conteneur.innerHTML = '';
        conteneur.appendChild(scroll);

        majLegendeLocale(Object.keys(valeursLegende));

        scroll.addEventListener('scroll', function () {
            if (scroll.scrollLeft + scroll.clientWidth > scroll.scrollWidth - 500 && semaines < 26 && !chargementEnCours) {
                var sl = scroll.scrollLeft;
                semaines = Math.min(26, semaines + 4);
                charger(function () {
                    rendre();
                    var nouveau = conteneur.querySelector('.ga2-scroll');
                    if (nouveau) { nouveau.scrollLeft = sl; }
                });
            }
        });
    }

    function gaucheLast(cellule) { return cellule; }

    function estAujourdhui(iso) {
        return iso === G.verrouille(new Date());
    }

    /* Variante un peu plus soutenue du fond ( dégradé vertical du bloc ) */
    function couleurSoutenue(rgba) {
        return rgba.replace(/, ?\.([0-9]+)\)$/, function (m, a) {
            return ', 0.' + Math.min(85, Math.round(parseFloat('0.' + a) * 100) + 18) + ')';
        });
    }

    function majLegendeLocale(valeurs) {
        var zone = document.getElementById('gaLegende');
        if (!zone) { return; }
        var parStatut = critereCourant() === 'statut';
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
                G.echapper(String(libelle).length > 22 ? String(libelle).substring(0, 21) + '…' : libelle) + '</span>';
        });
        zone.innerHTML = html;
    }

    /* ═══════════ Interactions d'un bloc ═══════════ */

    var tooltip = document.getElementById('gaTooltip');

    function attacherBloc(bloc, r, piste, ligne, structure, jours, colonnes) {
        var st = (D.libellesStatut || {})[r.statut] || r.statut;

        bloc.addEventListener('mouseenter', function (e) { montrer(e); });
        bloc.addEventListener('mousemove', function (e) { montrer(e); });
        bloc.addEventListener('mouseleave', function () { if (tooltip) { tooltip.hidden = true; } });

        function montrer(e) {
            if (!tooltip || glisse) { return; }
            tooltip.innerHTML = '<b>' + G.echapper(r.evenement) + '</b>' +
                '<span>' + heure(r.heure_debut) + ' – ' + heure(r.heure_fin) + '</span>' +
                '<span>Du ' + G.dateFR(r.date_debut) + (r.date_fin !== r.date_debut ? ' au ' + G.dateFR(r.date_fin) : '') + '</span>' +
                '<span>Salle : ' + G.echapper(r.salle_nom) + '</span>' +
                (r.client ? '<span>Client : ' + G.echapper(r.client) + '</span>' : '') +
                (r.formateur_nom ? '<span>Formateur : ' + G.echapper(r.formateur_nom) + '</span>' : '') +
                '<span>Statut : ' + G.echapper(st) + '</span>';
            tooltip.hidden = false;
            tooltip.style.left = Math.min(e.clientX + 14, window.innerWidth - tooltip.offsetWidth - 12) + 'px';
            tooltip.style.top = Math.min(e.clientY + 16, window.innerHeight - tooltip.offsetHeight - 12) + 'px';
        }

        function heure(h) { return G.heureCourte(h); }

        /* clic simple : fiche détail */
        bloc.addEventListener('click', function (e) {
            if (glisse || deplace) { return; }
            /* le clic qui suit immédiatement un glissement n'ouvre pas la fiche */
            if (Date.now() - glisseFinie < 500) { return; }
            if (window.PlanningFiche) { window.PlanningFiche.ouvrir(r); }
        });

        /* glisser-déposer et redimensionnement */
        var glisse = false, deplace = null, glisseFinie = 0;

        bloc.addEventListener('pointerdown', function (e) {
            if (e.button !== 0 || !D.peutGerer) { return; }
            var rect = bloc.getBoundingClientRect();
            var bord = e.clientX - rect.left < 10 ? 'debut' : (rect.right - e.clientX < 10 ? 'fin' : null);
            deplace = { bord: bord, x0: e.clientX, y0: e.clientY, r: r, piste: piste, ligne: ligne,
                        structure: structure, jours: jours, colonnes: colonnes };
            bloc.setPointerCapture(e.pointerId);
            e.preventDefault();
        });

        bloc.addEventListener('pointermove', function (e) {
            if (!deplace) { return; }
            if (!glisse && Math.abs(e.clientX - deplace.x0) + Math.abs(e.clientY - deplace.y0) > 6) {
                glisse = true;
                bloc.classList.add('ga2-en-glissee');
            }
            if (glisse) { deplacerFantome(e, deplace); }
        });

        bloc.addEventListener('pointerup', function (e) {
            if (!deplace) { return; }
            var info = deplace;
            deplace = null;
            bloc.classList.remove('ga2-en-glissee');
            if (!glisse) {
                /* simple clic : aucun fantôme à normalement nettoyer */
                retirerFantome();
                return;
            }
            glisse = false;
            glisseFinie = Date.now();
            /* validerGlisse lit la position du fantôme puis le retire */
            validerGlisse(info, e);
        });

        bloc.addEventListener('pointercancel', function () {
            deplace = null; glisse = false;
            bloc.classList.remove('ga2-en-glissee');
            retirerFantome();
        });
    }

    /* fantôme de prévisualisation pendant le glissement */
    var fantome = null;

    function retirerFantome() {
        if (fantome) { fantome.remove(); fantome = null; }
    }

    function pisteSousPoint(x, y) {
        var pistes = conteneur.querySelectorAll('.ga2-ligne:not(.masque)');
        for (var i = 0; i < pistes.length; i++) {
            var rect = pistes[i].getBoundingClientRect();
            if (y >= rect.top && y <= rect.bottom && x >= rect.left && x <= rect.right) {
                return pistes[i];
            }
        }
        return null;
    }

    function colonneSousPoint(piste, x) {
        var rect = piste.getBoundingClientRect();
        var t = Math.max(0, Math.min(1, (x - rect.left) / rect.width));
        return Math.floor(t * (deplaceCol() * 2));
    }

    function deplaceCol() {
        return joursVisibles().length;
    }

    function deplacerFantome(e, info) {
        var piste = pisteSousPoint(e.clientX, e.clientY);
        retirerFantome();
        if (!piste) { return; }
        var col = colonneSousPoint(piste, e.clientX);
        fantome = document.createElement('div');
        fantome.className = 'ga2-fantome';
        var n = deplaceCol() * 2;
        var g = 100 / n;
        /* largeur réelle du bloc glissé ( en demi-journées ) */
        var span = 1;
        if (info && info.r) {
            var origine = colonnesResa(info.r, info.jours);
            span = origine.fin - origine.debut + 1;
        }
        fantome.style.left = (col * g) + '%';
        fantome.style.width = (span * g) + '%';
        piste.appendChild(fantome);
        fantome.dataset.col = col;
        fantome.dataset.cleLigne = piste.dataset.cleLigne;
    }

    /* validation d'un glissement : reconstruction des dates/heures */
    function validerGlisse(info, e) {
        var piste = pisteSousPoint(e.clientX, e.clientY);
        if (!piste || !fantome) { return; }
        var col = parseInt(fantome.dataset.col, 10);
        var cleLigne = fantome.dataset.cleLigne;
        retirerFantome();

        var jours = info.jours;
        var r = info.r;
        var origine = colonnesResa(r, jours);
        var delta = col - (info.bord === 'fin' ? origine.fin : origine.debut);
        if (delta === 0 && cleLigne === info.ligne.cle && !info.bord) { return; }
        if (info.bord === 'debut' && col > origine.fin) { return; }
        if (info.bord === 'fin' && col < origine.debut) { return; }

        var nColonnes = jours.length * 2;

        function dateCol(c) {
            return jours[Math.floor(c / 2)];
        }
        function demiCol(c) {
            return c % 2 === 0 ? 'AM' : 'PM';
        }

        var nouveau = {
            id: r.id,
            evenement: r.evenement,
            categorie: r.categorie || '',
            salle_id: r.salle_id,
            date_reservation: r.date_debut,
            date_fin: r.date_fin !== r.date_debut ? r.date_fin : '',
            heure_debut: r.heure_debut,
            heure_fin: r.heure_fin,
            statut: r.statut,
            demandeur_nom: r.demandeur_nom,
            organisation: r.client && r.client !== r.demandeur_nom ? r.client : '',
            formateur_id: r.formateur_id || '',
            nb_personnes: r.nb_personnes || '',
            email: r.email || '',
            telephone: r.telephone || ''
        };

        var HD = G.HEURES_DEMI;

        /* changement de ressource selon le type de ligne */
        var ligneCible = ligneParCle(cleLigne, info.structure);
        if (ligneCible) {
            if (ligneCible.salle) { nouveau.salle_id = ligneCible.salle.id; }
            if (ligneCible.formateur) { nouveau.formateur_id = ligneCible.formateur.id; }
            if (ligneCible.client && ligneCible.client !== 'Sans client') { nouveau.organisation = ligneCible.client; }
        }

        /* ── Colonnes résultantes ( fin incluse ) ──
           Une réservation répète les mêmes heures chaque jour : le motif
           « débute l'après-midi, finit le lendemain matin » n'est pas
           représentable. Un déplacement conserve donc la parité ( AM/PM )
           du départ ; un redimensionnement recolle au motif valide.     */
        var cd, cf;
        if (info.bord === 'fin') {
            cd = origine.debut;
            cf = Math.max(origine.debut, Math.min(nColonnes - 1, origine.fin + delta));
            if (demiCol(cd) === 'PM' && demiCol(cf) === 'AM') { cf = Math.max(cd, cf - 1); }
        } else if (info.bord === 'debut') {
            cf = origine.fin;
            cd = Math.max(0, Math.min(cf, col));
            if (demiCol(cd) === 'PM' && demiCol(cf) === 'AM') { cd = Math.min(cf, cd + 1); }
        } else {
            cd = col - ((col % 2) !== (origine.debut % 2) ? 1 : 0);
            cd = Math.max(0, Math.min(cd, nColonnes - 1));
            cf = Math.min(nColonnes - 1, cd + (origine.fin - origine.debut));
        }

        if (!info.bord && cd === origine.debut && cf === origine.fin && cleLigne === info.ligne.cle) {
            return; /* retombé à l'identique après le recalage */
        }

        var jourDebut = dateCol(cd);
        var jourFin = dateCol(cf);

        nouveau.date_reservation = jourDebut;
        nouveau.date_fin = jourFin > jourDebut ? jourFin : '';
        /* les heures d'origine sont conservées quand elles tombent dans
           les demi-journées d'arrivée, sinon recalées sur leurs bornes */
        nouveau.heure_debut = heureDansDemi(r.heure_debut, demiCol(cd), 'debut');
        nouveau.heure_fin = heureDansDemi(r.heure_fin, demiCol(cf), 'fin');

        envoyerModification(nouveau, 'Déplacement appliqué.');
    }

    function heureDansDemi(heure, demi, bord) {
        var ref = demi === 'AM'
            ? (bord === 'debut' ? G.HEURES_DEMI.AM.debut : G.HEURES_DEMI.AM.fin)
            : (bord === 'debut' ? G.HEURES_DEMI.PM.debut : G.HEURES_DEMI.PM.fin);
        var min = (+heure.split(':')[0]) * 60 + (+heure.split(':')[1]);
        var bas = demi === 'AM' ? 0 : 12 * 60;
        var haut = demi === 'AM' ? 12 * 60 : 24 * 60;
        if (min >= bas && min < haut) { return heure; }
        return ref;
    }

    function ligneParCle(cle, structure) {
        var trouve = null;
        structure.groupes.forEach(function (g) {
            g.lignes.forEach(function (l) {
                if (l.cle === cle) { trouve = l; }
            });
        });
        return trouve;
    }

    function envoyerModification(nouveau, messageSucces) {
        var donnees = new FormData();
        donnees.append('_token', csrf.value);
        Object.keys(nouveau).forEach(function (k) {
            if (nouveau[k] !== null && nouveau[k] !== undefined) { donnees.append(k, nouveau[k]); }
        });

        fetch(D.urls.modifier, {
            method: 'POST', credentials: 'same-origin',
            body: new URLSearchParams(donnees),
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (rep) { return rep.json(); })
            .then(function (rep) {
                if (rep.ok) {
                    window.location.reload();
                    return;
                }
                window.alert(rep.erreur || 'Modification impossible.');
            })
            .catch(function () { window.alert('Envoi impossible : vérifiez votre connexion.'); });
    }

    /* ═══════════ Création par clic sur une zone vide ═══════════ */

    conteneur.addEventListener('click', function (e) {
        if (e.target.closest('.ga2-bloc') || !window.PlanningFiche || !D.peutGerer) { return; }
        var piste = e.target.closest('.ga2-ligne');
        if (!piste) { return; }
        var rect = piste.getBoundingClientRect();
        var t = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
        var col = Math.floor(t * deplaceCol() * 2);
        var jours = joursVisibles();
        if (col >= jours.length * 2) { return; }
        var date = jours[Math.floor(col / 2)];
        var demi = col % 2 === 0 ? 'AM' : 'PM';
        var HD = G.HEURES_DEMI;

        var ligne = ligneParCle(piste.dataset.cleLigne, construireStructure());
        var prefill = {
            date: date,
            heure_debut: HD[demi].debut,
            heure_fin: HD[demi].fin,
            salle_id: ligne && ligne.salle ? ligne.salle.id : null,
            formateur_id: ligne && ligne.formateur ? ligne.formateur.id : null,
            organisation: ligne && ligne.client && ligne.client !== 'Sans client' ? ligne.client : null
        };
        window.PlanningFiche.creer(prefill);
    });

    /* ═══════════ Options ═══════════ */

    if (selAffichage) { selAffichage.addEventListener('change', rendre); }
    if (cocherCondenser) { cocherCondenser.addEventListener('change', rendre); }
    if (cocherWeekends) { cocherWeekends.addEventListener('change', rendre); }
    if (btnActualiser) {
        btnActualiser.addEventListener('click', function () {
            charger(rendre);
        });
    }
    if (cocherAlertes) {
        cocherAlertes.addEventListener('change', function () {
            if (!this.checked) { return; }
            window.dispatchEvent(new CustomEvent('ga:alertes', { detail: donnees ? donnees.alertes : [] }));
        });
    }
    window.addEventListener('ga:couleur', rendre);

    /* ═══════════ Démarrage ═══════════ */

    charger(rendre);
})();
