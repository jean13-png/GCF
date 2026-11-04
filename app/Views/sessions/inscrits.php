<?php $titrePage = 'Inscrits · ' . $session['formation_titre']; ?>
<?php require dirname(__DIR__) . '/layout/haut.php'; ?>

<div class="carte">
    <div class="carte-entete">
        <div>
            <h3><?= e($session['formation_titre']) ?></h3>
            <span class="secondaire" style="font-size:13px;color:var(--gris-doux);">
                Du <?= e(format_date_fr($session['date_debut'], false)) ?> au <?= e(format_date_fr($session['date_fin'], false)) ?>
                · <?= e(format_heure($session['heure_debut'])) ?>-<?= e(format_heure($session['heure_fin'])) ?>
                · Salle <?= e($session['salle_nom']) ?>
            </span>
        </div>
        <a class="btn btn-petit btn-discret" href="<?= url('/sessions') ?>"><i class="fas fa-arrow-left"></i> Sessions</a>
    </div>

    <?php if ($inscriptions === []): ?>
        <div class="vide">Aucune inscription pour cette session pour le moment.</div>
    <?php else: ?>
        <div class="tableau-wrap">
            <table class="tableau">
                <thead>
                    <tr><th>Apprenant</th><th>Contact</th><th>Matricule</th><th>Niveau déclaré</th><th>Inscrit le</th><th>Statut</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($inscriptions as $i): ?>
                    <tr>
                        <td class="principal"><?= e($i['prenom']) ?> <?= e($i['nom']) ?></td>
                        <td>
                            <?= e($i['email']) ?><br>
                            <span class="secondaire"><?= e($i['telephone'] ?: '') ?></span>
                        </td>
                        <td><?= e($i['matricule'] ?: '·') ?></td>
                        <td class="secondaire"><?= e($i['niveau_actuel'] ?: '·') ?></td>
                        <td class="secondaire"><?= e(format_date_fr($i['cree_le'], false)) ?></td>
                        <td><?= badge_inscription($i['statut']) ?></td>
                        <td>
                            <?php if ($i['statut'] !== 'ANNULEE'): ?>
                            <form method="post" action="<?= url('/inscriptions/annuler/' . (int) $i['id']) ?>"
                                  data-confirm="Annuler cette inscription ?">
                                <?= csrf_champ() ?>
                                <button class="btn btn-petit btn-discret" type="submit">Annuler</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require dirname(__DIR__) . '/layout/bas.php'; ?>
