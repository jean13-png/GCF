<?php $titrePage = 'Réservations de salles'; ?>
<?php require dirname(__DIR__) . '/layout/haut.php'; ?>

<div class="carte">
    <div class="carte-entete">
        <h3>Demandes et réservations</h3>
        <a class="btn btn-primaire" href="<?= url('/reservations/nouvelle') ?>">+ Nouvelle demande</a>
    </div>

    <form method="get" action="<?= url('/reservations') ?>">
        <div class="filtres" style="margin-bottom:16px;">
            <div class="champ">
                <label for="f-statut">Statut</label>
                <select id="f-statut" name="statut">
                    <option value="">Tous</option>
                    <?php foreach (statuts_reservation() as $cle => [$libelle]): ?>
                        <option value="<?= e($cle) ?>" <?= ($_GET['statut'] ?? '') === $cle ? 'selected' : '' ?>><?= e($libelle) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="champ">
                <label for="f-salle">Salle</label>
                <select id="f-salle" name="salle_id">
                    <option value="">Toutes</option>
                    <?php foreach ($salles as $s): ?>
                        <option value="<?= (int) $s['id'] ?>" <?= (int) ($_GET['salle_id'] ?? 0) === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="champ">
                <label for="f-du">À partir du</label>
                <input type="date" id="f-du" name="du" value="<?= e($_GET['du'] ?? '') ?>">
            </div>
            <button type="submit" class="btn btn-bleu">Filtrer</button>
            <a class="btn btn-discret" href="<?= url('/reservations') ?>">Réinitialiser</a>
            <a class="btn btn-secondaire" href="<?= url('/planning') ?>">Voir le planning</a>
        </div>
    </form>

    <?php if ($reservations === []): ?>
        <div class="vide">Aucune réservation ne correspond à ces critères.</div>
    <?php else: ?>
        <div class="tableau-wrap">
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Date · créneau</th><th>Salle</th><th>Événement</th><th>Demandeur</th><th>Statut</th>
                        <?php if (in_array(auth()['role'], ['ADMIN', 'GESTIONNAIRE'], true)): ?><th>Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($reservations as $r): ?>
                    <tr>
                        <td>
                            <span class="principal"><?= e(format_date_fr($r['date_reservation'])) ?></span><br>
                            <span class="secondaire"><?= e(format_heure($r['heure_debut'])) ?> – <?= e(format_heure($r['heure_fin'])) ?></span>
                        </td>
                        <td>
                            <?= e($r['salle_nom']) ?><br>
                            <span class="secondaire"><?= $r['nb_personnes'] !== null ? (int) $r['nb_personnes'] . ' pers.' : '' ?></span>
                        </td>
                        <td class="principal"><?= e($r['evenement']) ?></td>
                        <td>
                            <?= e($r['demandeur_nom']) ?><br>
                            <span class="secondaire"><?= e(trim(($r['demandeur_organisation'] ?? '') . ' ' . ($r['demandeur_telephone'] ?? ''))) ?></span>
                        </td>
                        <td><?= badge_statut($r['statut']) ?></td>
                        <?php if (in_array(auth()['role'], ['ADMIN', 'GESTIONNAIRE'], true)): ?>
                        <td>
                            <?php
                            $actions = [
                                'valider' => ['btn-vert', 'Valider (Réservé)'],
                                'occuper' => ['btn-primaire', 'Occuper'],
                                'liberer' => ['btn-bleu', 'Libérer'],
                                'refuser' => ['btn-discret', 'Refuser'],
                                'annuler' => ['btn-discret', 'Annuler'],
                            ];
                            foreach ($actions as $action => [$classe, $libelle]):
                                $deja = ($action === 'valider' && $r['statut'] === 'CONFIRMEE')
                                    || ($action === 'occuper' && $r['statut'] === 'OCCUPEE')
                                    || ($action === 'liberer' && $r['statut'] === 'TERMINEE')
                                    || (in_array($r['statut'], ['REFUSEE', 'ANNULEE', 'TERMINEE'], true));
                                if ($deja) { continue; }
                                $confirm = in_array($action, ['refuser', 'annuler'], true) ? 'data-confirm="Confirmer cette action ? Le demandeur sera notifié par e-mail."' : '';
                            ?>
                                <form style="display:inline;" method="post" action="<?= url('/reservations/statut/' . (int) $r['id']) ?>">
                                    <?= csrf_champ() ?>
                                    <input type="hidden" name="action" value="<?= e($action) ?>">
                                    <input type="hidden" name="commentaire" value="">
                                    <button class="btn btn-petit <?= $classe ?>" type="submit" style="margin-bottom:4px;" <?= $confirm ?>><?= e($libelle) ?></button>
                                </form>
                            <?php endforeach; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="aide-champ" style="margin-top:10px;">
            Cycle de vie d'une réservation : <strong>En attente</strong> (demande déposée) → <strong>Réservé</strong> (validée par le gestionnaire) →
            <strong>Occupé</strong> (l'événement a commencé) → <strong>Libéré</strong> (la salle redevient libre). Chaque étape notifie le demandeur par e-mail.
        </p>
    <?php endif; ?>
</div>

<?php require dirname(__DIR__) . '/layout/bas.php'; ?>
