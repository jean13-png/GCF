<?php $titrePage = 'Catalogue des formations'; ?>
<?php require dirname(__DIR__) . '/layout/haut.php'; ?>

<div class="carte">
    <div class="carte-entete">
        <h3><?= count($formations) ?> formation<?= count($formations) > 1 ? 's' : '' ?> · uniquement le numérique</h3>
        <?php if (in_array(auth()['role'], ['ADMIN', 'RESPONSABLE'], true)): ?>
            <a class="btn btn-primaire" href="<?= url('/formations/nouvelle') ?>">+ Nouvelle formation</a>
        <?php endif; ?>
    </div>
    <?php if ($formations === []): ?>
        <div class="vide">Le catalogue est vide pour le moment.</div>
    <?php else: ?>
        <div class="tableau-wrap">
            <table class="tableau">
                <thead>
                    <tr><th>Formation</th><th>Thème</th><th>Durée</th><th>Niveau</th><th>Tarif</th><th>Visibilité</th><th></th></tr>
                </thead>
                <tbody>
                <?php
                $niveaux = ['DEBUTANT' => 'badge-libre', 'INTERMEDIAIRE' => 'badge-reserve', 'AVANCE' => 'badge-occupe'];
                $niveauLib = ['DEBUTANT' => 'Débutant', 'INTERMEDIAIRE' => 'Intermédiaire', 'AVANCE' => 'Avancé'];
                foreach ($formations as $f): ?>
                    <tr>
                        <td>
                            <span class="principal"><?= e($f['titre']) ?></span><br>
                            <span class="secondaire"><?= e($f['public_vise'] ?: '') ?></span>
                        </td>
                        <td><?= e($f['theme']) ?></td>
                        <td><?= (int) $f['duree_heures'] ?> h</td>
                        <td><span class="badge <?= e($niveaux[$f['niveau']]) ?>"><?= e($niveauLib[$f['niveau']]) ?></span></td>
                        <td><?= format_fcfa($f['tarif']) ?></td>
                        <td>
                            <?php if ((int) $f['actif'] === 1): ?>
                                <span class="badge badge-libre">Visible en ligne</span>
                            <?php else: ?>
                                <span class="badge badge-refuse">Masquée</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="actions-cellule">
                                <?php if (in_array(auth()['role'], ['ADMIN', 'RESPONSABLE'], true)): ?>
                                    <a class="btn btn-petit btn-discret" href="<?= url('/formations/editer/' . (int) $f['id']) ?>">Modifier</a>
                                    <form method="post" action="<?= url('/formations/activer/' . (int) $f['id']) ?>">
                                        <?= csrf_champ() ?>
                                        <button class="btn btn-petit <?= (int) $f['actif'] === 1 ? 'btn-discret' : 'btn-vert' ?>" type="submit">
                                            <?= (int) $f['actif'] === 1 ? 'Masquer' : 'Publier' ?>
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
