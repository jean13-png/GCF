<?php $titrePage = 'Planning des salles'; ?>
<?php require dirname(__DIR__) . '/layout/haut.php'; ?>

<?php
$jours = [];
for ($i = 0; $i < 7; $i++) {
    $jour = (clone $lundi)->modify("+$i days");
    $jours[] = $jour;
}
$classesStatut = ['EN_ATTENTE' => 'evt-attente', 'CONFIRMEE' => 'evt-confirme', 'OCCUPEE' => 'evt-occupe'];
?>

<div class="carte">
    <div class="carte-entete">
        <div class="planning-nav">
            <a class="btn btn-petit btn-discret" href="<?= url('/planning?semaine=' . ($decalage - 1)) ?>"><i class="fas fa-arrow-left"></i> Semaine précédente</a>
            <span class="semaine-courante">
                Semaine du <?= e(format_date_fr($lundi->format('Y-m-d'))) ?> au <?= e(format_date_fr($dimanche->format('Y-m-d'))) ?>
            </span>
            <a class="btn btn-petit btn-discret" href="<?= url('/planning?semaine=' . ($decalage + 1)) ?>">Semaine suivante <i class="fas fa-arrow-right"></i></a>
            <?php if ($decalage !== 0): ?>
                <a class="btn btn-petit btn-bleu" href="<?= url('/planning') ?>">Cette semaine</a>
            <?php endif; ?>
        </div>
        <div style="font-size:12px;color:var(--gris-doux);">
            <span class="badge badge-attente">En attente</span>
            <span class="badge badge-reserve">Réservé</span>
            <span class="badge badge-occupe">Occupé</span>
        </div>
    </div>

    <div class="planning-wrap">
        <div class="planning">
            <div></div>
            <?php foreach ($jours as $j): ?>
                <div class="entete-jour">
                    <?= e(jour_fr((int) $j->format('w'))) ?><br>
                    <span class="num"><?= (int) $j->format('d') ?> <?= e(mois_fr((int) $j->format('n'))) ?></span>
                </div>
            <?php endforeach; ?>

            <?php foreach ($salles as $salle): ?>
                <div class="case-salle">
                    <?= e($salle['nom']) ?>
                    <span class="cap"><?= (int) $salle['capacite'] ?> places<?= (int) $salle['maintenance'] === 1 ? ' · maintenance' : '' ?></span>
                </div>
                <?php foreach ($jours as $index => $j): ?>
                    <?php
                    $eventsDuJour = $grille[$salle['id']][$index] ?? [];
                    ?>
                    <div class="case-jour <?= $eventsDuJour === [] ? 'vide' : '' ?> <?= $index >= 5 ? 'weekend' : '' ?>">
                        <?php foreach ($eventsDuJour as $evt): ?>
                            <div class="evt <?= e($classesStatut[$evt['statut']] ?? '') ?>" title="<?= e($evt['evenement']) ?> · <?= e($evt['demandeur_nom']) ?>">
                                <span class="evt-heure"><?= e(format_heure($evt['heure_debut'])) ?>–<?= e(format_heure($evt['heure_fin'])) ?></span>
                                <span class="evt-titre"><?= e($evt['evenement']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <p class="aide-champ" style="margin-top:12px;">Une case vide signifie que la salle est libre ce jour-là. Survolez un créneau pour voir le détail.</p>
</div>

<?php require dirname(__DIR__) . '/layout/bas.php'; ?>
