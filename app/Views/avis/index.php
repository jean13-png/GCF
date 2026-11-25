<?php $titrePage = 'Avis des demandeurs'; ?>
<?php require dirname(__DIR__) . '/layout/haut.php'; ?>

<div class="carte">
    <div class="carte-entete">
        <h3>Avis après réservation</h3>
    </div>

    <p class="aide-champ" style="margin-bottom:14px;">
        <strong><?= (int) $stats['envoyes'] ?></strong> lien(s) envoyé(s) ·
        <strong><?= (int) $stats['soumis'] ?></strong> avis reçu(s)
        <?php if ($stats['moyenne'] !== null): ?>
            · note moyenne <strong><?= e($stats['moyenne']) ?> / 5</strong>
        <?php endif; ?>
    </p>

    <form method="get" action="<?= url('/avis') ?>">
        <div class="filtres" style="margin-bottom:16px;">
            <div class="champ">
                <label for="f-salle">Salle</label>
                <select id="f-salle" name="salle_id">
                    <option value="">Toutes</option>
                    <?php foreach ($salles as $s): ?>
                        <option value="<?= (int) $s['id'] ?>" <?= (int) $filtres['salle_id'] === (int) $s['id'] && $filtres['salle_id'] !== '' ? 'selected' : '' ?>><?= e($s['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="champ">
                <label for="f-du">Réservations à partir du</label>
                <input type="date" id="f-du" name="du" value="<?= e($filtres['du']) ?>">
            </div>
            <div class="champ">
                <label for="f-etat">État</label>
                <select id="f-etat" name="etat">
                    <option value="">Tous</option>
                    <option value="soumis" <?= $filtres['etat'] === 'soumis' ? 'selected' : '' ?>>Avis reçu</option>
                    <option value="attente" <?= $filtres['etat'] === 'attente' ? 'selected' : '' ?>>En attente de réponse</option>
                </select>
            </div>
            <div class="champ">
                <label for="f-note">Note</label>
                <select id="f-note" name="note">
                    <option value="">Toutes</option>
                    <?php for ($n = 5; $n >= 1; $n--): ?>
                        <option value="<?= $n ?>" <?= (string) $filtres['note'] === (string) $n ? 'selected' : '' ?>><?= $n ?> / 5</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="champ" style="align-self:flex-end;">
                <button class="btn btn-primaire" type="submit">Filtrer</button>
            </div>
        </div>
    </form>

    <?php if ($avis === []): ?>
        <div class="vide">Aucun avis ne correspond à ces critères.</div>
    <?php else: ?>
        <div class="tableau-wrap">
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Date · créneau</th><th>Salle</th><th>Événement</th><th>Demandeur</th><th>Note</th><th>État</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($avis as $a): ?>
                    <tr>
                        <td>
                            <span class="principal"><?= e(format_date_fr($a['date_reservation'])) ?></span><br>
                            <span class="secondaire"><?= e(format_heure($a['heure_debut'])) ?> – <?= e(format_heure($a['heure_fin'])) ?></span>
                        </td>
                        <td><?= e($a['salle_nom']) ?></td>
                        <td class="principal"><?= e($a['evenement']) ?></td>
                        <td>
                            <?= e($a['demandeur_nom']) ?><br>
                            <span class="secondaire"><?= e(trim(($a['demandeur_organisation'] ?? '') . ' ' . $a['demandeur_email'])) ?></span>
                        </td>
                        <td><?= $a['note'] !== null ? (int) $a['note'] . ' / 5' : '<span class="secondaire">—</span>' ?></td>
                        <td>
                            <?php if ($a['soumis_le'] !== null): ?>
                                <strong>Avis reçu</strong><br>
                                <span class="secondaire"><?= e(date('d/m/Y H:i', strtotime($a['soumis_le']))) ?></span>
                            <?php elseif ($a['token_expire_le'] < \App\Models\Avis::maintenant()): ?>
                                <span class="secondaire">Lien expiré sans réponse</span>
                            <?php else: ?>
                                <span class="secondaire">En attente de réponse</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if ($a['soumis_le'] !== null): ?>
                        <tr>
                            <td colspan="6">
                                <details>
                                    <summary>Voir le détail de l'avis</summary>
                                    <?php if (!empty($a['points_positifs'])): ?>
                                        <p><strong>Ce qui a bien fonctionné :</strong><br><?= nl2br(e($a['points_positifs'])) ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($a['points_negatifs'])): ?>
                                        <p><strong>Ce qui pourrait être amélioré :</strong><br><?= nl2br(e($a['points_negatifs'])) ?></p>
                                    <?php endif; ?>
                                    <p><strong>Avis général :</strong><br><?= nl2br(e($a['avis_general'])) ?></p>
                                </details>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require dirname(__DIR__) . '/layout/bas.php'; ?>
