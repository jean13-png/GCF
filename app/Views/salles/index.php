<?php $titrePage = 'Salles du centre'; ?>
<?php require dirname(__DIR__) . '/layout/haut.php'; ?>

<div class="carte">
    <div class="carte-entete">
        <h3><?= count($salles) ?> salle<?= count($salles) > 1 ? 's' : '' ?> · état en temps réel</h3>
        <?php if (auth()['role'] === 'ADMIN'): ?>
            <a class="btn btn-primaire" href="<?= url('/salles/nouvelle') ?>">+ Nouvelle salle</a>
        <?php endif; ?>
    </div>
    <?php if ($salles === []): ?>
        <div class="vide">Aucune salle enregistrée pour le moment.</div>
    <?php else: ?>
        <div class="tableau-wrap">
            <table class="tableau">
                <thead>
                    <tr><th>Salle</th><th>Capacité</th><th>Équipements</th><th>Localisation</th><th>Tarif horaire</th><th>État</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($salles as $s): ?>
                    <tr>
                        <td>
                            <span class="principal"><?= e($s['nom']) ?></span>
                        </td>
                        <td><?= (int) $s['capacite'] ?> personnes</td>
                        <td class="secondaire"><?= e($s['equipements'] ?: '·') ?></td>
                        <td class="secondaire"><?= e($s['localisation'] ?: '·') ?></td>
                        <td><?= format_fcfa($s['tarif_horaire']) ?></td>
                        <td>
                            <?php if ((int) $s['maintenance'] === 1): ?>
                                <span class="badge badge-refuse">Indisponible · maintenance</span>
                            <?php else: ?>
                                <span class="badge badge-libre">Disponible</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="actions-cellule">
                                <?php if (auth()['role'] === 'ADMIN'): ?>
                                    <a class="btn btn-petit btn-discret" href="<?= url('/salles/editer/' . (int) $s['id']) ?>">Modifier</a>
                                <?php endif; ?>
                                <?php if (in_array(auth()['role'], ['ADMIN', 'GESTIONNAIRE'], true)): ?>
                                    <form method="post" action="<?= url('/salles/maintenance/' . (int) $s['id']) ?>">
                                        <?= csrf_champ() ?>
                                        <button class="btn btn-petit <?= (int) $s['maintenance'] === 1 ? 'btn-vert' : 'btn-discret' ?>" type="submit"
                                            <?= (int) $s['maintenance'] === 1 ? '' : 'data-confirm="Marquer cette salle en maintenance ? Elle ne sera plus réservable."' ?>>
                                            <?= (int) $s['maintenance'] === 1 ? 'Remettre en service' : 'Maintenance' ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require dirname(__DIR__) . '/layout/bas.php'; ?>
