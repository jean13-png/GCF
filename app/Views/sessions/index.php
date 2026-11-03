<?php $titrePage = 'Sessions de formation'; ?>
<?php require dirname(__DIR__) . '/layout/haut.php'; ?>

<div class="carte">
    <div class="carte-entete">
        <h3>Sessions programmées</h3>
        <?php if (in_array(auth()['role'], ['ADMIN', 'RESPONSABLE'], true)): ?>
            <a class="btn btn-primaire" href="<?= url('/sessions/nouvelle') ?>">+ Programmer une session</a>
        <?php endif; ?>
    </div>
    <?php if ($sessions === []): ?>
        <div class="vide">Aucune session programmée pour le moment.</div>
    <?php else: ?>
        <div class="tableau-wrap">
            <table class="tableau">
                <thead>
                    <tr><th>Formation</th><th>Période · horaire</th><th>Salle</th><th>Inscrits</th><th>Formateur</th><th>Statut</th><th></th></tr>
                </thead>
                <tbody>
                <?php
                $statutsSess = ['PLANIFIEE' => ['Planifiée', 'attente'], 'EN_COURS' => ['En cours', 'occupe'], 'TERMINEE' => ['Terminée', 'libre'], 'ANNULEE' => ['Annulée', 'annule']];
                foreach ($sessions as $s):
                    $remplissage = (int) $s['places'] > 0 ? round(100 * (int) $s['nb_inscrits'] / (int) $s['places']) : 0;
                ?>
                    <tr>
                        <td>
                            <span class="principal"><?= e($s['formation_titre']) ?></span><br>
                            <span class="secondaire"><?= e($s['formation_theme']) ?></span>
                        </td>
                        <td>
                            <span class="principal"><?= e(format_date_fr($s['date_debut'], false)) ?> → <?= e(format_date_fr($s['date_fin'], false)) ?></span><br>
                            <span class="secondaire"><?= e(format_heure($s['heure_debut'])) ?> – <?= e(format_heure($s['heure_fin'])) ?></span>
                        </td>
                        <td><?= e($s['salle_nom']) ?></td>
                        <td>
                            <?= (int) $s['nb_inscrits'] ?> / <?= (int) $s['places'] ?>
                            <br><span class="secondaire"><?= $remplissage ?> %</span>
                        </td>
                        <td><?= e($s['formateur_nom'] ?: '·') ?></td>
                        <td><span class="badge badge-<?= e($statutsSess[$s['statut']][1]) ?>"><?= e($statutsSess[$s['statut']][0]) ?></span></td>
                        <td>
                            <div class="actions-cellule">
                                <a class="btn btn-petit btn-secondaire" href="<?= url('/sessions/' . (int) $s['id'] . '/inscrits') ?>">Inscrits</a>
                                <?php if (in_array(auth()['role'], ['ADMIN', 'RESPONSABLE'], true)): ?>
                                    <a class="btn btn-petit btn-discret" href="<?= url('/sessions/editer/' . (int) $s['id']) ?>">Modifier</a>
                                    <?php foreach (['EN_COURS' => 'Lancer', 'TERMINEE' => 'Terminer', 'ANNULEE' => 'Annuler'] as $nouveau => $libelle):
                                        if ($s['statut'] === $nouveau || ($s['statut'] === 'ANNULEE')) { continue; } ?>
                                        <form method="post" action="<?= url('/sessions/statut/' . (int) $s['id']) ?>" style="display:inline;">
                                            <?= csrf_champ() ?>
                                            <input type="hidden" name="statut" value="<?= e($nouveau) ?>">
                                            <button class="btn btn-petit <?= $nouveau === 'ANNULEE' ? 'btn-discret' : 'btn-vert' ?>" type="submit"
                                                <?= $nouveau === 'ANNULEE' ? 'data-confirm="Annuler cette session ? Les inscriptions restantes seront à reprogrammer."' : '' ?>>
                                                <?= e($libelle) ?>
                                            </button>
                                        </form>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="aide-champ" style="margin-top:10px;">
            Programmer une session réserve automatiquement sa salle dans le planning pour chaque jour de la formation.
        </p>
    <?php endif; ?>
</div>

<?php require dirname(__DIR__) . '/layout/bas.php'; ?>
