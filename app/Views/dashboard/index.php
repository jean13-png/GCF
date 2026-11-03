<?php $titrePage = 'Tableau de bord'; ?>
<?php require dirname(__DIR__) . '/layout/haut.php'; ?>

<div class="grille-stats">
    <div class="carte-stat orange">
        <div class="valeur"><?= $statutsResa ?></div>
        <div class="libelle">Réservations en attente de validation</div>
    </div>
    <div class="carte-stat">
        <div class="valeur"><?= $resasConfirmees ?></div>
        <div class="libelle">Salles réservées (confirmées)</div>
    </div>
    <div class="carte-stat rouge">
        <div class="valeur"><?= $resasOccupees ?></div>
        <div class="libelle">Salles occupées maintenant</div>
    </div>
    <div class="carte-stat vert">
        <div class="valeur"><?= $nbSalles ?></div>
        <div class="libelle">Salles gérées dans le centre</div>
    </div>
</div>

<div class="grille-stats">
    <div class="carte-stat">
        <div class="valeur"><?= $sessionsPlanifiees ?></div>
        <div class="libelle">Sessions de formation planifiées</div>
    </div>
    <div class="carte-stat vert">
        <div class="valeur"><?= $sessionsEnCours ?></div>
        <div class="libelle">Sessions en cours</div>
    </div>
    <div class="carte-stat vert">
        <div class="valeur"><?= $inscritsConfirmes ?></div>
        <div class="libelle">Apprenants inscrits (confirmés)</div>
    </div>
    <div class="carte-stat orange">
        <div class="valeur"><?= $inscriptionsAttente ?></div>
        <div class="libelle">Inscriptions à vérifier (e-mail)</div>
    </div>
</div>

<div class="carte">
    <div class="carte-entete">
        <h3>Prochaines réservations de salles</h3>
        <a class="btn btn-petit btn-secondaire" href="<?= url('/reservations') ?>">Tout voir</a>
    </div>
    <?php if ($resasAVenir === []): ?>
        <div class="vide">Aucune réservation à venir.</div>
    <?php else: ?>
        <div class="tableau-wrap">
            <table class="tableau">
                <thead>
                    <tr><th>Date</th><th>Créneau</th><th>Salle</th><th>Événement</th><th>Demandeur</th><th>Statut</th></tr>
                </thead>
                <tbody>
                <?php foreach ($resasAVenir as $r): ?>
                    <tr>
                        <td class="principal"><?= e(format_date_fr($r['date_reservation'])) ?></td>
                        <td><?= e(format_heure($r['heure_debut'])) ?> – <?= e(format_heure($r['heure_fin'])) ?></td>
                        <td><?= e($r['salle_nom']) ?></td>
                        <td class="principal"><?= e($r['evenement']) ?></td>
                        <td><?= e($r['demandeur_nom']) ?></td>
                        <td><?= badge_statut($r['statut']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="carte">
    <div class="carte-entete">
        <h3>Dernières inscriptions reçues</h3>
        <a class="btn btn-petit btn-secondaire" href="<?= url('/inscriptions') ?>">Gérer les inscriptions</a>
    </div>
    <?php if ($dernieresInscriptions === []): ?>
        <div class="vide">Aucune inscription pour le moment.</div>
    <?php else: ?>
        <ul class="liste-simple">
            <?php foreach ($dernieresInscriptions as $i): ?>
                <li>
                    <div>
                        <span class="principal"><?= e($i['prenom']) ?> <?= e($i['nom']) ?></span>
                        <span class="secondaire"> · <?= e($i['formation_titre']) ?></span>
                    </div>
                    <?= badge_inscription($i['statut']) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?php require dirname(__DIR__) . '/layout/bas.php'; ?>
