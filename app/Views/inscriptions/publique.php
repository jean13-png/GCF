<?php $titrePage = 'Inscription en ligne'; $descPage = "Inscription en ligne aux formations du centre : choisissez votre session, vérifiez votre adresse e-mail et recevez vos identifiants."; ?>
<?php require dirname(__DIR__) . '/layout/haut_publique.php'; ?>

<div class="conteneur page-publique">
<div class="entete-page-publique">
    <h1>S'inscrire à une formation</h1>
    <p>
        Choisissez votre session dans la liste et laissez vos coordonnées : après vérification de votre
        adresse e-mail, vos identifiants d'apprenant (matricule et mot de passe) vous sont envoyés par e-mail.
    </p>
</div>

<?php if ($sessions === []): ?>
    <div class="encart-info">
        Aucune session n'est ouverte aux inscriptions pour le moment.
        Revenez bientôt nous voir : de nouvelles formations dans le numérique sont programmées régulièrement.
    </div>
<?php else: ?>
    <div class="carte-publique-large">
    <form method="post" action="<?= url('/inscription') ?>">
        <?= csrf_champ() ?>
        <div class="form-grille" style="grid-template-columns:1fr;">
            <div>
                <label for="session_id">Formation souhaitée <span class="obligatoire">*</span></label>
                <select id="session_id" name="session_id" required>
                    <option value="">— Choisissez une session —</option>
                    <?php foreach ($sessions as $s): ?>
                        <option value="<?= (int) $s['id'] ?>" <?= (int) old('session_id') === (int) $s['id'] ? 'selected' : '' ?>>
                            <?= e($s['formation_titre']) ?>
                            · du <?= e(format_date_fr($s['date_debut'], false)) ?> au <?= e(format_date_fr($s['date_fin'], false)) ?>
                            · <?= e(format_heure($s['heure_debut'])) ?>-<?= e(format_heure($s['heure_fin'])) ?>
                            · <?= (int) $s['places_restantes'] ?> place<?= (int) $s['places_restantes'] > 1 ? 's' : '' ?> restante<?= (int) $s['places_restantes'] > 1 ? 's' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="aide-champ">Toutes nos formations sont dans le domaine du numérique.</div>
            </div>
            <div class="form-grille">
                <div>
                    <label for="prenom">Prénom <span class="obligatoire">*</span></label>
                    <input type="text" id="prenom" name="prenom" required maxlength="100" value="<?= e(old('prenom')) ?>">
                </div>
                <div>
                    <label for="nom">Nom <span class="obligatoire">*</span></label>
                    <input type="text" id="nom" name="nom" required maxlength="100" value="<?= e(old('nom')) ?>">
                </div>
                <div>
                    <label for="email">Adresse e-mail <span class="obligatoire">*</span></label>
                    <input type="email" id="email" name="email" required maxlength="190" value="<?= e(old('email')) ?>">
                    <div class="aide-champ">Vos identifiants seront envoyés à cette adresse.</div>
                </div>
                <div>
                    <label for="telephone">Téléphone</label>
                    <input type="text" id="telephone" name="telephone" maxlength="30" value="<?= e(old('telephone')) ?>" placeholder="+229 ...">
                </div>
                <div class="entiere">
                    <label for="niveau_actuel">Votre niveau actuel en informatique</label>
                    <input type="text" id="niveau_actuel" name="niveau_actuel" maxlength="100" value="<?= e(old('niveau_actuel')) ?>" placeholder="Ex. : grand débutant, quelques bases…">
                </div>
                <div class="entiere">
                    <label for="motivation">Votre motivation (facultatif)</label>
                    <textarea id="motivation" name="motivation" maxlength="2000" placeholder="Pourquoi souhaitez-vous suivre cette formation ?"><?= e(old('motivation')) ?></textarea>
                </div>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primaire btn-grand">Envoyer ma demande d'inscription</button>
        </div>
    </form>
    </div>
<?php endif; ?>
</div>

<?php require dirname(__DIR__) . '/layout/bas_publique.php'; ?>
