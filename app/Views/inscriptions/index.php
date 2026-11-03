<?php $titrePage = 'Inscriptions reçues'; ?>
<?php require dirname(__DIR__) . '/layout/haut.php'; ?>

<div class="carte">
    <div class="carte-entete">
        <h3>Suivi des inscriptions en ligne</h3>
    </div>

    <form method="get" action="<?= url('/inscriptions') ?>">
        <div class="filtres" style="margin-bottom:16px;">
            <div class="champ">
                <label for="f-statut">Statut</label>
                <select id="f-statut" name="statut">
                    <option value="">Tous</option>
                    <?php foreach (statuts_inscription() as $cle => [$libelle]): ?>
                        <option value="<?= e($cle) ?>" <?= ($_GET['statut'] ?? '') === $cle ? 'selected' : '' ?>><?= e($libelle) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-bleu">Filtrer</button>
            <a class="btn btn-discret" href="<?= url('/inscriptions') ?>">Réinitialiser</a>
        </div>
    </form>

    <div class="encart-info" style="margin-bottom:16px;">
        Le compte apprenant (matricule + mot de passe) est créé <strong>automatiquement</strong> dès que l'intéressé
        clique sur le lien de vérification reçu par e-mail. Pour programmer une nouvelle session, rendez-vous dans
        <a href="<?= url('/sessions/nouvelle') ?>">Sessions</a>.
    </div>

    <?php if ($inscriptions === []): ?>
        <div class="vide">Aucune inscription ne correspond à ces critères.</div>
    <?php else: ?>
        <div class="tableau-wrap">
            <table class="tableau">
                <thead>
                    <tr><th>Candidat</th><th>Formation · session</th><th>Niveau</th><th>Déposée le</th><th>Statut</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($inscriptions as $i): ?>
                    <tr>
                        <td>
                            <span class="principal"><?= e($i['prenom']) ?> <?= e($i['nom']) ?></span><br>
                            <span class="secondaire"><?= e($i['email']) ?><?= $i['matricule'] ? ' · ' . e($i['matricule']) : '' ?></span>
                        </td>
                        <td>
                            <?= e($i['formation_titre']) ?><br>
                            <span class="secondaire"><?= e(format_date_fr($i['date_debut'], false)) ?> → <?= e(format_date_fr($i['date_fin'], false)) ?></span>
                        </td>
                        <td class="secondaire"><?= e($i['niveau_actuel'] ?: '·') ?></td>
                        <td class="secondaire"><?= e(format_date_fr($i['cree_le'], false)) ?></td>
                        <td><?= badge_inscription($i['statut']) ?></td>
                        <td>
                            <?php if ($i['statut'] !== 'ANNULEE'): ?>
                            <form method="post" action="<?= url('/inscriptions/annuler/' . (int) $i['id']) ?>" data-confirm="Annuler cette inscription ?">
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
