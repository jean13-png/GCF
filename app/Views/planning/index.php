<?php $titrePage = 'Planning des salles'; ?>
<?php require dirname(__DIR__) . '/layout/haut.php'; ?>

<?php
$jours = [];
for ($i = 0; $i < 7; $i++) {
    $jours[] = (clone $lundi)->modify("+$i days");
}

$classesStatut  = ['EN_ATTENTE' => 'st-attente', 'CONFIRMEE' => 'st-reserve', 'OCCUPEE' => 'st-occupe'];
$libellesStatut = ['EN_ATTENTE' => 'En attente', 'CONFIRMEE' => 'Réservé', 'OCCUPEE' => 'Occupé'];

/* Positionnement temporel ( minutes ) sur la plage d'ouverture */
$OUV  = $heureOuverture * 60;
$FER  = $heureFermeture * 60;
$SPAN = $FER - $OUV;

$minutesHoraire = static function (string $h): int {
    $p = explode(':', substr($h, 0, 5));
    return ((int) $p[0]) * 60 + (int) ($p[1] ?? 0);
};
$pct = static function (string $h) use ($OUV, $SPAN, $minutesHoraire): float {
    return max(0.0, min(100.0, ($minutesHoraire($h) - $OUV) / $SPAN * 100));
};

$aujourdhui = (new \DateTime())->format('Y-m-d');

$urlBase = static function (array $params = []) use ($decalage, $vue, $salleId) {
    $params += ['semaine' => $decalage, 'vue' => $vue];
    if ($vue === 'salle') {
        $params['salle_id'] = $salleId;
    }
    return url('/planning?' . http_build_query($params));
};
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
                <a class="btn btn-petit btn-bleu" href="<?= e($urlBase(['semaine' => 0])) ?>">Cette semaine</a>
            <?php endif; ?>

            <span class="pl-switch" role="group" aria-label="Mode d'affichage">
                <a class="pl-switch-btn <?= $vue === 'salle' ? 'actif' : '' ?>"
                   href="<?= e(url('/planning?' . http_build_query(['semaine' => $decalage, 'vue' => 'salle', 'salle_id' => $salleId]))) ?>">
                   <i class="fas fa-table"></i> Par salle
                </a>
                <a class="pl-switch-btn <?= $vue === 'synthese' ? 'actif' : '' ?>"
                   href="<?= e(url('/planning?' . http_build_query(['semaine' => $decalage, 'vue' => 'synthese']))) ?>">
                   <i class="fas fa-th-large"></i> Synthèse
                </a>
            </span>
        </div>
        <div class="pl-legende">
            <span class="pl-leg-item"><span class="pl-puce p-libre"></span> Libre</span>
            <span class="pl-leg-item"><span class="pl-puce p-EN_ATTENTE"></span> En attente</span>
            <span class="pl-leg-item"><span class="pl-puce p-CONFIRMEE"></span> Réservé</span>
            <span class="pl-leg-item"><span class="pl-puce p-OCCUPEE"></span> Occupé</span>
        </div>
    </div>
</div>

<?php if ($vue === 'salle' && $salle !== null): ?>

<?php
/* Événements de la salle affichée, triés puis positionnés sur la piste du jour */
$eventsJour = static function (int $jour) use ($parJour, $classesStatut, $pct, $salle) {
    $html = '';
    foreach ($parJour[$jour] ?? [] as $evt) {
        $gauche = $pct($evt['heure_debut']);
        $largeur = max(2.5, $pct($evt['heure_fin']) - $gauche);
        $court = ($largeur < 9) ? ' pl-evt-court' : '';
        $maintenance = '';
        $orga = trim(($evt['demandeur_nom'] ?? '') . (($evt['demandeur_organisation'] ?? '') !== '' ? ' · ' . $evt['demandeur_organisation'] : ''));
        $html .= '<div class="pl-evt ' . e($classesStatut[$evt['statut']] ?? '') . $court . '" role="button" tabindex="0"'
            . ' style="left:' . number_format($gauche, 2, '.', '') . '%;width:' . number_format($largeur, 2, '.', '') . '%"'
            . ' data-evenement="' . e($evt['evenement']) . '"'
            . ' data-statut="' . e($evt['statut']) . '"'
            . ' data-debut="' . e(substr($evt['heure_debut'], 0, 5)) . '"'
            . ' data-fin="' . e(substr($evt['heure_fin'], 0, 5)) . '"'
            . ' data-orga="' . e($evt['demandeur_nom'] ?? '') . '"'
            . ' data-organisation="' . e($evt['demandeur_organisation'] ?? '') . '"'
            . ' data-email="' . e($evt['demandeur_email'] ?? '') . '"'
            . ' data-tel="' . e($evt['demandeur_telephone'] ?? '') . '"'
            . ' data-nb="' . e($evt['nb_personnes'] ?? '') . '"'
            . ' data-salle="' . e($salle['nom']) . '"'
            . ' data-capacite="' . (int) $salle['capacite'] . '"'
            . ' title="' . e($evt['evenement']) . '">'
            . '<span class="pl-evt-heures">' . e(format_heure(substr($evt['heure_debut'], 0, 5))) . ' – ' . e(format_heure(substr($evt['heure_fin'], 0, 5))) . '</span>'
            . '<span class="pl-evt-titre">' . e($evt['evenement']) . '</span>'
            . ($orga !== '' ? '<span class="pl-evt-meta">' . e($orga) . '</span>' : '')
            . '</div>';
    }
    return $html;
};
?>

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

    <!-- En-tête salle + statistiques de la semaine -->
    <div class="pl-entete-salle">
        <div class="pl-salle-id">
            <div class="pl-salle-icone"><i class="fas fa-door-open"></i></div>
            <div>
                <h2><?= e($salle['nom']) ?><?= (int) $salle['maintenance'] === 1 ? ' <span class="pl-badge-maint"><i class="fas fa-tools"></i> maintenance</span>' : '' ?></h2>
                <p>
                    <?= (int) $salle['capacite'] ?> places
                    <?= $salle['localisation'] !== null && $salle['localisation'] !== '' ? ' · ' . e($salle['localisation']) : '' ?>
                    <?= $salle['equipements'] !== null && $salle['equipements'] !== '' ? ' · ' . e($salle['equipements']) : '' ?>
                </p>
            </div>
        </div>
        <div class="pl-stats">
            <div class="pl-stat">
                <b><?= $nbEvenements ?></b>
                <span>événement<?= $nbEvenements > 1 ? 's' : '' ?> cette semaine</span>
            </div>
            <div class="pl-stat">
                <b><?= number_format((float) $heuresReservees, 1, ',', ' ') ?> h</b>
                <span>réservées</span>
            </div>
            <div class="pl-stat">
                <b><?= $tauxOccupation ?> %</b>
                <span>d'occupation</span>
                <span class="pl-jauge"><span class="pl-jauge-fill" style="width:<?= min(100, $tauxOccupation) ?>%"></span></span>
            </div>
        </div>
    </div>

    <!-- Grille semaine : jours en lignes, heures en colonnes -->
    <div class="pl-wrap">
        <div class="pl-grille">
            <div class="pl-coin"></div>
            <div class="pl-heures">
                <?php for ($h = $heureOuverture; $h < $heureFermeture; $h++): ?>
                    <div class="pl-heure"><?= $h ?><span>h</span></div>
                <?php endfor; ?>
            </div>

            <?php foreach ($jours as $index => $j): ?>
                <?php $estAujourdhui = $j->format('Y-m-d') === $aujourdhui; ?>
                <div class="pl-cellule-jour <?= $estAujourdhui ? 'aujourdhui' : '' ?> <?= $index >= 5 ? 'weekend' : '' ?>">
                    <span class="pl-jour-nom"><?= e(jour_fr((int) $j->format('w'))) ?></span>
                    <span class="pl-jour-date"><?= (int) $j->format('d') ?> <?= e(mois_fr((int) $j->format('n'))) ?> <?= $j->format('Y') ?></span>
                    <?php if ($estAujourdhui): ?><span class="pl-badge-jour">Aujourd'hui</span><?php endif; ?>
                </div>
                <div class="pl-piste <?= $index >= 5 ? 'weekend' : '' ?> <?= $estAujourdhui ? 'aujourdhui' : '' ?>"
                     role="button" tabindex="0"
                     data-salle-id="<?= (int) $salle['id'] ?>"
                     data-salle-nom="<?= e($salle['nom']) ?>"
                     data-date="<?= e($j->format('Y-m-d')) ?>"
                     title="Voir la journée du <?= e(format_date_fr($j->format('Y-m-d'))) ?> et programmer un créneau libre">
                    <?= $eventsJour($index) ?>
                    <?php if (($parJour[$index] ?? []) === []): ?>
                        <span class="pl-libre">Toute la journée libre — cliquez pour programmer</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <p class="aide-champ" style="margin-top:12px;">
        Cliquez sur un événement pour afficher son détail complet ( organisateur, effectif, coordonnées ).
        Cliquez sur une zone libre de la journée pour ouvrir le planning détaillé et programmer un créneau.
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
                    <?php $eventsDuJour = $grille[$salle['id']][$index] ?? []; ?>
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
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <p class="aide-champ" style="margin-top:12px;">
        Vue d'ensemble des salles pour la semaine. Cliquez sur le nom d'une salle pour afficher sa grille horaire
        détaillée, ou sur une case pour ouvrir la journée.
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

<!-- ═══════════ Modal : détail d'un événement ═══════════ -->
<div class="pj-voile" id="peVoile" hidden>
    <div class="pe-modal" role="dialog" aria-modal="true" aria-labelledby="peTitre">
        <div class="pe-entete" id="peEnteteBarre">
            <div>
                <span class="pe-statut" id="peStatut">Réservé</span>
                <h3 id="peTitre">Événement</h3>
            </div>
            <button type="button" class="pj-fermer" id="peFermer" aria-label="Fermer">&times;</button>
        </div>
        <div class="pj-corps" id="peCorps"></div>
    </div>
</div>

<input type="hidden" id="pjCsrf" value="<?= e(\App\Core\CSRF::token()) ?>">
<input type="hidden" id="pjOrganisateur" value="<?= e(trim(($utilisateur['prenom'] ?? '') . ' ' . ($utilisateur['nom'] ?? ''))) ?>">

<script src="<?= asset('js/planning-jour.js') ?>"></script>

<?php require dirname(__DIR__) . '/layout/bas.php'; ?>
