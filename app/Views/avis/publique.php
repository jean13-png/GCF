<?php $titrePage = 'Votre avis'; $descPage = 'Donnez votre avis sur votre réservation de salle.'; ?>
<?php require dirname(__DIR__) . '/layout/haut_publique.php'; ?>

<div class="conteneur page-publique">
    <div class="entete-page-publique">
        <h1>Votre avis sur votre réservation</h1>
        <p>Merci d'avoir utilisé notre salle. Votre retour nous aide à améliorer nos services. Cela ne prend qu'une minute.</p>
    </div>

    <div class="carte-publique-large">
        <p>
            <strong><?= e($avis['evenement']) ?></strong><br>
            Salle : <?= e($avis['salle_nom']) ?><br>
            <?= e(format_date_fr($avis['date_reservation'])) ?> ·
            <?= e(format_heure($avis['heure_debut'])) ?> – <?= e(format_heure($avis['heure_fin'])) ?>
        </p>

        <form method="post" action="<?= url('/avis/' . $token) ?>">
            <?= csrf_champ() ?>

            <h3 class="titre-groupe">Votre note (facultative)</h3>
            <div class="avis-notes" role="radiogroup" aria-label="Note sur 5">
                <?php foreach ([5 => 'Très satisfait', 4 => 'Satisfait', 3 => 'Correct', 2 => 'Peu satisfait', 1 => 'Pas satisfait'] as $n => $libelle): ?>
                    <label style="display:inline-block;margin:0 10px 8px 0;">
                        <input type="radio" name="note" value="<?= $n ?>" <?= (string) old('note') === (string) $n ? 'checked' : '' ?>>
                        <?= $n ?> · <?= e($libelle) ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <h3 class="titre-groupe">Votre retour</h3>
            <div class="form-grille">
                <div class="entiere">
                    <label for="avis_positifs">Ce qui a bien fonctionné (facultatif)</label>
                    <textarea id="avis_positifs" name="avis_positifs" rows="4" maxlength="2000"><?= e(old('avis_positifs')) ?></textarea>
                </div>
                <div class="entiere">
                    <label for="avis_negatifs">Ce qui pourrait être amélioré (facultatif)</label>
                    <textarea id="avis_negatifs" name="avis_negatifs" rows="4" maxlength="2000"><?= e(old('avis_negatifs')) ?></textarea>
                </div>
                <div class="entiere">
                    <label for="avis_general">Votre avis général <span class="obligatoire">*</span></label>
                    <textarea id="avis_general" name="avis_general" rows="4" maxlength="2000" required><?= e(old('avis_general')) ?></textarea>
                </div>
            </div>

            <div style="margin-top:18px;">
                <button class="btn btn-primaire" type="submit">Envoyer mon avis</button>
            </div>
        </form>
    </div>
</div>

<?php require dirname(__DIR__) . '/layout/bas_publique.php'; ?>
