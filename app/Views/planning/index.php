<?php $titrePage = 'Planning des salles'; ?>
<?php require dirname(__DIR__) . '/layout/haut.php'; ?>

<?php
$jours = [];
for ($i = 0; $i < 7; $i++) {
    $jours[] = (clone $lundi)->modify("+$i days");
}

$libellesStatut = [
    'EN_ATTENTE' => 'En attente',
    'CONFIRMEE'  => 'Réservé',
    'OCCUPEE'    => 'Occupé',
    'A_REPORTER' => 'À reporter',
];
$classesStatut = [
    'EN_ATTENTE' => 'st-attente',
    'CONFIRMEE'  => 'st-reserve',
    'OCCUPEE'    => 'st-occupe',
    'A_REPORTER' => 'st-reporter',
];

/* Couverture multi-jours d'une réservation */
$couvre = static function (array $r, string $date): bool {
    $fin = $r['date_fin'] ?: $r['date_reservation'];
    return $r['date_reservation'] <= $date && $fin >= $date;
};

$aujourdhui = (new \DateTime())->format('Y-m-d');
$nbHeures = $fermeture - $ouverture;

$urlBase = static function (array $params = []) use ($decalage, $vue, $salleId) {
    $params += ['semaine' => $decalage, 'vue' => $vue];
    if ($vue === 'salle') {
        $params['salle_id'] = $salleId;
    }
    return url('/planning?' . http_build_query($params));
};

/* Données embarquées pour le JavaScript ( vues Gantt + fiche détail ) */
$planningData = [
    'urls' => [
        'jour'        => url('/planning/jour'),
        'donnees'     => url('/planning/donnees'),
        'programmer'  => url('/planning/programmer'),
        'modifier'    => url('/planning/modifier'),
        'supprimer'   => url('/planning/supprimer'),
    ],
    'csrf'     => \App\Core\CSRF::token(),
    'ouverture'=> $ouverture,
    'fermeture'=> $fermeture,
    'semaineDebut' => $lundi->format('Y-m-d'),
    'peutGerer'=> $peutGerer,
    'organisateurDefaut' => trim(($utilisateur['prenom'] ?? '') . ' ' . ($utilisateur['nom'] ?? '')),
    'salles'   => array_map(static function (array $s) {
        return ['id' => (int) $s['id'], 'nom' => $s['nom'], 'site' => $s['site'] ?: 'Cotonou',
                'capacite' => (int) $s['capacite'], 'maintenance' => (int) $s['maintenance'] === 1];
    }, $salles),
    'formateurs' => array_map(static function (array $f) {
        return ['id' => (int) $f['id'], 'nom' => $f['nom'], 'prenom' => $f['prenom']];
    }, $formateurs),
    'categories' => $categories,
    'statuts'    => $statuts,
    'libellesStatut' => $libellesStatut,
];
?>

<div class="carte">
    <div class="carte-entete">
        <div class="planning-nav">
            <a class="btn btn-petit btn-discret" href="<?= e(url('/planning?' . http_build_query(['semaine' => $decalage - 1, 'vue' => $vue] + ($vue === 'salle' ? ['salle_id' => $salleId] : [])))) ?>"><i class="fas fa-chevron-left"></i> Semaine précédente</a>
            <span class="semaine-courante">
                Semaine du <?= e(format_date_fr($lundi->format('Y-m-d'))) ?> au <?= e(format_date_fr($dimanche->format('Y-m-d'))) ?>
            </span>
            <a class="btn btn-petit btn-discret" href="<?= e(url('/planning?' . http_build_query(['semaine' => $decalage + 1, 'vue' => $vue] + ($vue === 'salle' ? ['salle_id' => $salleId] : [])))) ?>">Semaine suivante <i class="fas fa-chevron-right"></i></a>
            <?php if ($decalage !== 0): ?>
                <a class="btn btn-petit btn-bleu" href="<?= e($urlBase(['semaine' => 0])) ?>"><i class="fas fa-calendar-day"></i> Aujourd'hui</a>
            <?php endif; ?>

            <form method="get" action="<?= e(url('/planning/export')) ?>" class="pl-export" style="display:inline-flex;gap:8px;align-items:center;margin:0">
                <input type="hidden" name="semaine" value="<?= (int) $decalage ?>">
                <select name="salle_id" aria-label="Salle à exporter" class="btn btn-petit btn-discret" style="padding:6px 8px">
                    <option value="0">Tous</option>
                    <?php foreach ($salles as $s): ?>
                        <option value="<?= (int) $s['id'] ?>"><?= e($s['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-petit btn-bleu"><i class="fas fa-file-pdf"></i> Exporter en PDF</button>
            </form>
            <span class="pl-switch" role="group" aria-label="Mode d'affichage">
                <a class="pl-switch-btn <?= $vue === 'salle' ? 'actif' : '' ?>"
                   href="<?= e(url('/planning?' . http_build_query(['semaine' => $decalage, 'vue' => 'salle', 'salle_id' => $salleId]))) ?>">
                   <i class="fas fa-table"></i> Par salle
                </a>
                <a class="pl-switch-btn <?= $vue === 'synthese' ? 'actif' : '' ?>"
                   href="<?= e(url('/planning?' . http_build_query(['semaine' => $decalage, 'vue' => 'synthese']))) ?>">
                   <i class="fas fa-th-large"></i> Synthèse
                </a>
                <a class="pl-switch-btn <?= $vue === 'ressources' ? 'actif' : '' ?>"
                   href="<?= e(url('/planning?' . http_build_query(['semaine' => $decalage, 'vue' => 'ressources']))) ?>">
                   <i class="fas fa-stream"></i> Ressources
                </a>
            </span>
        </div>
    </div>
</div>

<?php if ($vue === 'salle' && $salle !== null): ?>

<div class="carte">
    <!-- Sélecteur de salle -->
    <div class="pl-salles" role="tablist" aria-label="Choisir une salle">
        <?php foreach ($salles as $s): ?>
            <a class="pl-salle-pill <?= (int) $s['id'] === $salleId ? 'active' : '' ?>"
               href="<?= e(url('/planning?' . http_build_query(['semaine' => $decalage, 'vue' => 'salle', 'salle_id' => (int) $s['id']]))) ?>">
               <?= e($s['nom']) ?>
               <span class="pl-salle-cap"><?= (int) $s['capacite'] ?> pl.</span>
               <?php if ((int) $s['maintenance'] === 1): ?><i class="fas fa-tools" title="En maintenance"></i><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Barre d'options commune -->
    <div class="ga-options">
        <label class="ga-opt ga-cacher-salle">Affichage
            <select id="gaAffichage">
                <option value="site-salle">Par site / Salle</option>
                <option value="site-formateur">Par site / Formateur</option>
                <option value="client-formateur">Par client / Formateur</option>
                <option value="formateur-client">Par formateur / Client</option>
                <option value="defaut">Par défaut</option>
            </select>
        </label>
        <label class="ga-opt">Couleur par
            <select id="gaCouleur">
                <option value="statut" selected>Statut</option>
                <option value="categorie">Catégorie</option>
                <option value="client">Client</option>
                <option value="salle">Salle</option>
            </select>
        </label>
        <div class="ga-legende" id="gaLegende" aria-live="polite"></div>
        <label class="ga-opt-check ga-cacher-salle"><input type="checkbox" id="gaCondenser"> Condenser l'échelle des temps</label>
        <label class="ga-opt-check ga-cacher-salle"><input type="checkbox" id="gaWeekends"> Afficher les week-ends</label>
        <label class="ga-opt-check"><input type="checkbox" id="gaAlertes"> Afficher les messages d'alerte</label>
        <button type="button" class="btn btn-petit btn-discret" id="gaActualiser"><i class="fas fa-sync-alt"></i> Actualiser</button>
    </div>
    <div class="ga-alertes" id="gaAlertesZone" hidden></div>

    <!-- En-tête salle : icône plan + nom en MAJUSCULES + statistiques -->
    <div class="g1-entete">
        <div class="g1-titre">
            <span class="g1-icone"><i class="fas fa-th-large"></i></span>
            <h2><?= e(mb_strtoupper($salle['nom'])) ?></h2>
            <span class="pl-badge-maint"><?= (int) $salle['capacite'] ?> places<?= (int) $salle['maintenance'] === 1 ? ' · maintenance' : '' ?></span>
        </div>
        <div class="pl-stats">
            <div class="pl-stat"><b><?= $nbEvenements ?></b><span>événement<?= $nbEvenements > 1 ? 's' : '' ?></span></div>
            <div class="pl-stat"><b><?= number_format((float) $heuresReservees, 1, ',', ' ') ?> h</b><span>réservées</span></div>
            <div class="pl-stat"><b><?= $tauxOccupation ?> %</b><span>d'occupation</span>
                <span class="pl-jauge"><span class="pl-jauge-fill" style="width:<?= min(100, $tauxOccupation) ?>%"></span></span>
            </div>
        </div>
    </div>

    <!-- Règle horaire : heures en bleu centrées sur les traits -->
    <div class="g1-regle" style="--nb-heures:<?= $nbHeures ?>">
        <?php for ($h = $ouverture; $h <= $fermeture; $h++): ?>
            <?php $pos = ($h - $ouverture) / $nbHeures * 100; ?>
            <?php if ($h < $fermeture): ?>
                <span class="g1-regle-heure" style="left:<?= $pos ?>%"><?= $h ?></span>
            <?php else: ?>
                <span class="g1-regle-heure g1-regle-bord" style="left:<?= $pos ?>%"><?= $h ?></span>
            <?php endif; ?>
        <?php endfor; ?>
    </div>

    <!-- 7 bandes jour : bandeau d'en-tête + zone de grille 50px + séparateur -->
    <?php foreach ($jours as $index => $j): ?>
        <?php
        $dateIso = $j->format('Y-m-d');
        $estAujourdhui = $dateIso === $aujourdhui;
        $evenementsJour = array_values(array_filter($resasSalle, static function (array $r) use ($couvre, $dateIso) {
            return $couvre($r, $dateIso);
        }));
        ?>
        <section class="g1-bande <?= $estAujourdhui ? 'aujourdhui' : '' ?>">
            <div class="g1-bandeau <?= $index >= 5 ? 'weekend' : '' ?>" role="button" tabindex="0"
                 title="Voir les heures de la journée">
                <span class="g1-bandeau-date"><?= e(ucfirst(jour_fr((int) $j->format('w')))) ?> <?= (int) $j->format('d') ?> <?= e(mois_fr((int) $j->format('n'))) ?> <?= $j->format('Y') ?></span>
                <?php if ($estAujourdhui): ?><span class="g1-badge-jour">Aujourd'hui</span><?php endif; ?>
            </div>
            <div class="g1-grille <?= $index >= 5 ? 'weekend' : '' ?>"
                 style="--nb-heures:<?= $nbHeures ?>"
                 role="button" tabindex="0"
                 data-salle-id="<?= (int) $salle['id'] ?>"
                 data-salle-nom="<?= e($salle['nom']) ?>"
                 data-date="<?= e($dateIso) ?>"
                 title="Voir la journée et programmer un créneau libre">
                <?php foreach ($evenementsJour as $evt): ?>
                    <?php
                    $client = $evt['demandeur_organisation'] ?: $evt['demandeur_nom'];
                    ?>
                    <div class="g1-bloc <?= $evt['statut'] === 'CONFIRMEE' ? 'confirme' : '' ?> <?= $classesStatut[$evt['statut']] ?? '' ?>"
                         role="button" tabindex="0"
                         data-id="<?= (int) $evt['id'] ?>"
                         data-debut="<?= e(substr($evt['heure_debut'], 0, 5)) ?>"
                         data-fin="<?= e(substr($evt['heure_fin'], 0, 5)) ?>"
                         data-evenement="<?= e($evt['evenement']) ?>"
                         data-categorie="<?= e($evt['categorie'] ?? '') ?>"
                         data-statut="<?= e($evt['statut']) ?>"
                         data-salle="<?= e($salle['nom']) ?>"
                         data-salle-id="<?= (int) $salle['id'] ?>"
                         data-capacite="<?= (int) $salle['capacite'] ?>"
                         data-orga="<?= e($evt['demandeur_nom'] ?? '') ?>"
                         data-organisation="<?= e($evt['demandeur_organisation'] ?? '') ?>"
                         data-email="<?= e($evt['demandeur_email'] ?? '') ?>"
                         data-tel="<?= e($evt['demandeur_telephone'] ?? '') ?>"
                         data-nb="<?= e($evt['nb_personnes'] ?? '') ?>"
                         data-formateur-id="<?= e($evt['formateur_id'] ?? '') ?>"
                         data-date-debut="<?= e($evt['date_reservation']) ?>"
                         data-date-fin="<?= e($evt['date_fin'] ?: $evt['date_reservation']) ?>"
                         title="<?= e($evt['evenement']) ?>">
                         <span class="g1-bloc-titre"><?= e($evt['evenement']) ?></span>
                    </div>
                <?php endforeach; ?>
                <?php if ($evenementsJour === []): ?>
                    <span class="g1-libre" hidden>libre</span>
                <?php endif; ?>
            </div>
            <div class="g1-separateur"></div>
        </section>
    <?php endforeach; ?>

    <p class="aide-champ" style="margin-top:12px;">
        Cliquez sur un bloc pour ouvrir sa fiche ( modification, suppression ).
        Cliquez sur une zone libre d'une journée pour programmer un créneau.
    </p>
</div>

<?php elseif ($vue === 'ressources'): ?>

<div class="carte">
    <!-- Barre d'options commune -->
    <div class="ga-options">
        <label class="ga-opt">Affichage
            <select id="gaAffichage">
                <option value="site-salle">Par site / Salle</option>
                <option value="site-formateur">Par site / Formateur</option>
                <option value="client-formateur">Par client / Formateur</option>
                <option value="formateur-client">Par formateur / Client</option>
                <option value="defaut">Par défaut</option>
            </select>
        </label>
        <label class="ga-opt">Couleur par
            <select id="gaCouleur">
                <option value="statut" selected>Statut</option>
                <option value="categorie">Catégorie</option>
                <option value="client">Client</option>
                <option value="salle">Salle</option>
            </select>
        </label>
        <div class="ga-legende" id="gaLegende" aria-live="polite"></div>
        <label class="ga-opt-check"><input type="checkbox" id="gaCondenser"> Condenser l'échelle des temps</label>
        <label class="ga-opt-check"><input type="checkbox" id="gaWeekends"> Afficher les week-ends</label>
        <label class="ga-opt-check"><input type="checkbox" id="gaAlertes"> Afficher les messages d'alerte</label>
        <button type="button" class="btn btn-petit btn-discret" id="gaActualiser"><i class="fas fa-sync-alt"></i> Actualiser</button>
    </div>
    <div class="ga-alertes" id="gaAlertesZone" hidden></div>

    <div id="gaConteneur" class="ga2-conteneur" data-debut="<?= e($lundi->format('Y-m-d')) ?>">
        <p class="pj-chargement">Chargement du planning des ressources…</p>
    </div>
    <p class="aide-champ" style="margin-top:12px;">
        Glissez un bloc pour changer son jour ou sa ressource, tirez ses bords pour changer sa durée :
        les conflits sont vérifiés avant validation. Cliquez sur une zone vide pour créer une réservation.
    </p>
</div>

<?php else: ?>

<!-- ═══════════ Vue synthèse : toutes les salles ═══════════ -->
<div class="carte">
    <div class="pl-wrap">
        <div class="pl-synthese">
            <div></div>
            <?php foreach ($jours as $j): ?>
                <div class="pl-syn-jour <?= $j->format('Y-m-d') === $aujourdhui ? 'aujourdhui' : '' ?>">
                    <?= e(jour_fr((int) $j->format('w'))) ?>
                    <span class="num"><?= (int) $j->format('d') ?> <?= e(mois_fr((int) $j->format('n'))) ?></span>
                </div>
            <?php endforeach; ?>

            <?php foreach ($salles as $salle): ?>
                <div class="pl-syn-salle">
                    <a href="<?= e(url('/planning?' . http_build_query(['semaine' => $decalage, 'vue' => 'salle', 'salle_id' => (int) $salle['id']]))) ?>"><?= e($salle['nom']) ?></a>
                    <span class="cap"><?= (int) $salle['capacite'] ?> places<?= (int) $salle['maintenance'] === 1 ? ' · maintenance' : '' ?></span>
                </div>
                <?php foreach ($jours as $index => $j): ?>
                    <?php
                    $eventsDuJour = $grille[$salle['id']][$index] ?? [];
                    /* créneaux libres de la journée ( partials ) pour l'encadré vert */
                    $triJour = $eventsDuJour;
                    usort($triJour, static function (array $a, array $b): int { return strcmp(substr($a['heure_debut'], 0, 5), substr($b['heure_debut'], 0, 5)); });
                    $fmtHM = static function (string $hm): string {
                        [$h, $m] = explode(':', $hm);
                        return (int) $h . 'h' . ((int) $m !== 0 ? $m : '');
                    };
                    $curseur = sprintf('%02d:00', $ouverture);
                    $plageFin = sprintf('%02d:00', $fermeture);
                    $libresJour = [];
                    foreach ($triJour as $evt) {
                        $debutEvt = substr($evt['heure_debut'], 0, 5);
                        if ($debutEvt > $curseur) { $libresJour[] = $fmtHM($curseur) . '–' . $fmtHM($debutEvt); }
                        if (substr($evt['heure_fin'], 0, 5) > $curseur) { $curseur = substr($evt['heure_fin'], 0, 5); }
                    }
                    if ($curseur < $plageFin) { $libresJour[] = $fmtHM($curseur) . '–' . $fmtHM($plageFin); }
                    ?>
                    <div class="pl-syn-case <?= $eventsDuJour === [] ? 'vide' : '' ?> <?= $index >= 5 ? 'weekend' : '' ?> <?= $j->format('Y-m-d') === $aujourdhui ? 'aujourdhui' : '' ?>"
                         role="button" tabindex="0"
                         data-salle-id="<?= (int) $salle['id'] ?>"
                         data-salle-nom="<?= e($salle['nom']) ?>"
                         data-date="<?= e($j->format('Y-m-d')) ?>">
                        <?php foreach ($eventsDuJour as $evt): ?>
                            <div class="pl-syn-evt <?= e($classesStatut[$evt['statut']] ?? '') ?>">
                                <span class="pl-syn-heures"><?= e(format_heure(substr($evt['heure_debut'], 0, 5))) ?>–<?= e(format_heure(substr($evt['heure_fin'], 0, 5))) ?></span>
                                <span class="pl-syn-titre"><?= e($evt['evenement']) ?></span>
                                <?php if (!empty($evt['demandeur_nom'])): ?>
                                    <span class="pl-syn-orga"><?= e($evt['demandeur_nom']) ?><?= !empty($evt['demandeur_organisation']) ? ' · ' . e($evt['demandeur_organisation']) : '' ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if ($eventsDuJour !== [] && $libresJour !== [] && (int) $salle['maintenance'] !== 1): ?>
                            <?php foreach (array_slice($libresJour, 0, 3) as $libre): ?>
                                <div class="pl-syn-libre">Libre <?= e($libre) ?></div>
                            <?php endforeach; ?>
                            <?php if (count($libresJour) > 3): ?><div class="pl-syn-libre">…</div><?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <p class="aide-champ" style="margin-top:12px;">
        Vue d'ensemble des salles pour la semaine. Cliquez sur le nom d'une salle pour afficher sa grille
        Gantt détaillée, ou sur une case pour ouvrir la journée.
    </p>
</div>

<?php endif; ?>

<!-- ═══════════ Modal : journée d'une salle ═══════════ -->
<div class="pj-voile" id="pjVoile" hidden
     data-url-jour="<?= url('/planning/jour') ?>"
     data-url-programmer="<?= url('/planning/programmer') ?>">
    <div class="pj-modal" role="dialog" aria-modal="true" aria-labelledby="pjTitre">
        <div class="pj-entete">
            <h3 id="pjTitre">Journée</h3>
            <button type="button" class="pj-fermer" id="pjFermer" aria-label="Fermer">&times;</button>
        </div>
        <div class="pj-corps" id="pjCorps">
            <p class="pj-chargement">Chargement de la journée…</p>
        </div>
    </div>
</div>

<!-- ═══════════ Modal : fiche d'une réservation ( détail + modification + suppression ) ═══════════ -->
<div class="pj-voile" id="peVoile" hidden>
    <div class="pe-modal" role="dialog" aria-modal="true" aria-labelledby="peTitre">
        <div class="pe-entete" id="peEnteteBarre">
            <div>
                <span class="pe-statut" id="peStatut">Réservé</span>
                <h3 id="peTitre">Réservation</h3>
            </div>
            <button type="button" class="pj-fermer" id="peFermer" aria-label="Fermer">&times;</button>
        </div>
        <div class="pj-corps" id="peCorps"></div>
    </div>
</div>

<!-- Tooltip partagé ( survol des blocs ) -->
<div class="ga-tooltip" id="gaTooltip" hidden></div>

<input type="hidden" id="pjCsrf" value="<?= e(\App\Core\CSRF::token()) ?>">
<input type="hidden" id="pjOrganisateur" value="<?= e(trim(($utilisateur['prenom'] ?? '') . ' ' . ($utilisateur['nom'] ?? ''))) ?>">

<script>window.PLANNING_DATA = <?= json_encode($planningData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<script src="<?= asset('js/gantt-utils.js') ?>"></script>
<script src="<?= asset('js/planning-jour.js') ?>"></script>
<script src="<?= asset('js/planning-ressources.js') ?>"></script>

<?php require dirname(__DIR__) . '/layout/bas.php'; ?>
