<?php $titrePage = 'Vérification de l\'adresse e-mail'; ?>
<?php require dirname(__DIR__) . '/layout/haut_publique.php'; ?>

<div class="conteneur page-publique">
<div class="carte-publique">
<?php if ($erreur !== null): ?>
    <h1>Oups…</h1>
    <p class="sous-titre">Un problème est survenu avec ce lien.</p>
    <div class="encart-info"><?= e($erreur) ?></div>
<?php else: ?>
    <h1>Adresse e-mail vérifiée 🎉</h1>
    <p class="sous-titre">Bienvenue <?= e($compte['prenom']) ?> ! Votre inscription est enregistrée.</p>
    <?php if ($enListeAttente): ?>
        <div class="encart-info">
            <strong>Session complète :</strong> votre inscription est placée en liste d'attente.
            Vous serez contacté dès qu'une place se libère.
        </div>
    <?php else: ?>
        <div class="encart-reussite">
            Votre inscription à <strong><?= e($session['formation_titre']) ?></strong>
            (du <?= e(format_date_fr($session['date_debut'], false)) ?> au <?= e(format_date_fr($session['date_fin'], false)) ?>)
            est <strong>confirmée</strong>.
        </div>
    <?php endif; ?>
    <p>Vos identifiants d'apprenant viennent de vous être envoyés par e-mail :</p>
    <div class="identifiants">
        Identifiant : <code><?= e($compte['email']) ?></code><br>
        Matricule : <code><?= e($compte['matricule']) ?></code><br>
        Mot de passe : envoyé par e-mail (à changer à votre première connexion)
    </div>
    <div class="form-actions">
        <a class="btn btn-primaire" href="<?= url('/login') ?>">Accéder à mon espace</a>
    </div>
<?php endif; ?>

</div>
</div>

<?php require dirname(__DIR__) . '/layout/bas_publique.php'; ?>
