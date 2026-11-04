<?php $titrePage = 'Connexion'; ?>
<?php require dirname(__DIR__) . '/layout/haut_publique.php'; ?>

<div class="conteneur page-publique">
    <div class="entete-page-publique">
        <h1>Connexion à votre espace</h1>
        <p>Espace du personnel du centre ou espace apprenant : e-mail ou matricule + mot de passe.</p>
    </div>

    <div class="carte-publique">
        <form method="post" action="<?= url('/login') ?>">
            <?= csrf_champ() ?>
            <div class="form-grille" style="grid-template-columns:1fr;">
                <div>
                    <label for="identifiant">E-mail ou matricule <span class="obligatoire">*</span></label>
                    <input type="text" id="identifiant" name="identifiant" required
                           value="<?= e(old('identifiant')) ?>"
                           placeholder="prenom.nom@exemple.com ou GCF-2026-0001" autocomplete="username">
                </div>
                <div>
                    <label for="password">Mot de passe <span class="obligatoire">*</span></label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primaire btn-grand">Se connecter</button>
                <a href="<?= url('/inscription') ?>">S'inscrire à une formation</a>
            </div>
        </form>
    </div>
</div>

<?php require dirname(__DIR__) . '/layout/bas_publique.php'; ?>
