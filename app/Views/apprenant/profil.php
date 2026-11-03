<?php $titrePage = 'Mon profil'; ?>
<?php require dirname(__DIR__) . '/layout/haut.php'; ?>

<div class="carte" style="max-width:720px;">
    <div class="carte-entete">
        <h3>Mes informations</h3>
    </div>
    <form method="post" action="<?= url('/mon-profil') ?>">
        <?= csrf_champ() ?>
        <div class="form-grille">
            <div>
                <label>Prénom</label>
                <input type="text" value="<?= e($user['prenom']) ?>" disabled>
            </div>
            <div>
                <label>Nom</label>
                <input type="text" value="<?= e($user['nom']) ?>" disabled>
            </div>
            <div>
                <label>Matricule</label>
                <input type="text" value="<?= e($user['matricule'] ?: '·') ?>" disabled>
            </div>
            <div>
                <label>E-mail</label>
                <input type="email" value="<?= e($user['email']) ?>" disabled>
            </div>
            <div>
                <label for="telephone">Téléphone</label>
                <input type="text" id="telephone" name="telephone" maxlength="30"
                       value="<?= e($user['telephone'] ?? '') ?>" placeholder="+229 ...">
            </div>
        </div>

        <div style="margin-top:26px;padding-top:20px;border-top:1px solid var(--gris-bordure);">
            <h3 style="font-size:14.5px;color:var(--bleu);margin-bottom:14px;">Changer mon mot de passe</h3>
            <div class="form-grille">
                <div class="entiere">
                    <label for="password_actuel">Mot de passe actuel</label>
                    <input type="password" id="password_actuel" name="password_actuel" autocomplete="current-password">
                </div>
                <div>
                    <label for="password_nouveau">Nouveau mot de passe</label>
                    <input type="password" id="password_nouveau" name="password_nouveau" minlength="8" autocomplete="new-password">
                    <div class="aide-champ">8 caractères minimum. Laissez vide pour ne pas le changer.</div>
                </div>
                <div>
                    <label for="password_confirmation">Confirmation</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" minlength="8" autocomplete="new-password">
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primaire">Enregistrer</button>
        </div>
    </form>
</div>

<?php require dirname(__DIR__) . '/layout/bas.php'; ?>
