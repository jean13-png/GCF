<?php $titrePage = 'Mon espace apprenant'; ?>
<?php require dirname(__DIR__) . '/layout/haut.php'; ?>

<div class="carte" style="border-left:4px solid var(--rouge);">
    <div class="carte-entete">
        <h3>Bonjour <?= e(auth()['prenom']) ?> 👋</h3>
    </div>
    <p style="font-size:14px;">
        Matricule : <strong style="color:var(--bleu);"><?= e(auth()['matricule']) ?></strong>
        &nbsp;·&nbsp; Connecté avec <?= e(auth()['email']) ?>
    </p>
    <p class="aide-champ" style="margin-top:6px;">
        Votre matricule et votre e-mail sont les deux identifiants acceptés à la connexion.
    </p>
</div>

<div class="carte">
    <div class="carte-entete"><h3><i class="fas fa-map-pin"></i> Mes formations à venir</h3></div>
    <?php if ($aVenir === [] && $enCours === []): ?>
        <div class="vide">
            Aucune formation programmée pour le moment.<br>
            <a href="<?= url('/') ?>">Voir les formations ouvertes aux inscriptions</a>
        </div>
    <?php else: ?>
        <ul class="liste-simple">
            <?php foreach (array_merge($enCours, $aVenir) as $s): ?>
                <li>
                    <div>
                        <span class="principal"><?= e($s['formation_titre']) ?></span>
                        <span class="secondaire"> · <?= e(format_date_fr($s['date_debut'], false)) ?> → <?= e(format_date_fr($s['date_fin'], false)) ?>
                        · <?= e(format_heure($s['heure_debut'])) ?>-<?= e(format_heure($s['heure_fin'])) ?></span><br>
                        <span class="secondaire">Salle <?= e($s['salle_nom']) ?> · Formateur : <?= e($s['formateur_nom'] ?: 'à désigner') ?></span>
                    </div>
                    <div style="text-align:right;">
                        <?php if ($s['inscription_statut'] === 'LISTE_ATTENTE'): ?>
                            <span class="badge badge-reserve">Liste d'attente</span>
                        <?php elseif ($s['statut'] === 'EN_COURS'): ?>
                            <span class="badge badge-occupe">En cours</span>
                        <?php else: ?>
                            <span class="badge badge-libre">Confirmée</span>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<div class="carte">
    <div class="carte-entete"><h3><i class="fas fa-graduation-cap"></i> Mes formations terminées</h3></div>
    <?php if ($terminees === []): ?>
        <div class="vide">Vos formations terminées apparaîtront ici, avec vos résultats.</div>
    <?php else: ?>
        <ul class="liste-simple">
            <?php foreach ($terminees as $s): ?>
                <li>
                    <div>
                        <span class="principal"><?= e($s['formation_titre']) ?></span>
                        <span class="secondaire"> · terminée le <?= e(format_date_fr($s['date_fin'], false)) ?></span>
                    </div>
                    <span class="badge badge-termine">Terminée</span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<div class="carte">
    <div class="carte-entete"><h3><i class="fas fa-rocket"></i> Nouveautés : sessions ouvertes aux inscriptions</h3></div>
    <?php if ($sessionsOuvertes === []): ?>
        <div class="vide">Aucune nouvelle session ouverte pour l'instant.</div>
    <?php else: ?>
        <ul class="liste-simple">
            <?php foreach (array_slice($sessionsOuvertes, 0, 4) as $s): ?>
                <li>
                    <div>
                        <span class="principal"><?= e($s['formation_titre']) ?></span>
                        <span class="secondaire"> · <?= e(format_date_fr($s['date_debut'], false)) ?> → <?= e(format_date_fr($s['date_fin'], false)) ?></span>
                    </div>
                    <a class="btn btn-petit btn-primaire" href="<?= url('/') ?>">S'inscrire</a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?php require dirname(__DIR__) . '/layout/bas.php'; ?>
