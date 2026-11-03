<?php $titrePage = $salle ? 'Modifier la salle' : 'Nouvelle salle'; ?>
<?php require dirname(__DIR__) . '/layout/haut.php'; ?>

<div class="carte" style="max-width:760px;">
    <div class="carte-entete">
        <h3><?= $salle ? e($salle['nom']) : 'Créer une salle' ?></h3>
        <a class="btn btn-petit btn-discret" href="<?= url('/salles') ?>">← Retour</a>
    </div>

    <form method="post" action="<?= url('/salles/enregistrer') ?>">
        <?= csrf_champ() ?>
        <input type="hidden" name="id" value="<?= (int) ($salle['id'] ?? 0) ?>">
        <div class="form-grille">
            <div class="entiere">
                <label for="nom">Nom de la salle <span class="obligatoire">*</span></label>
                <input type="text" id="nom" name="nom" required maxlength="120"
                       value="<?= e($salle['nom'] ?? old('nom')) ?>" placeholder="Ex. : Laboratoire informatique 3">
            </div>
            <div>
                <label for="capacite">Capacité (personnes) <span class="obligatoire">*</span></label>
                <input type="number" id="capacite" name="capacite" required min="1" max="2000"
                       value="<?= e($salle['capacite'] ?? old('capacite', 20)) ?>">
            </div>
            <div>
                <label for="tarif_horaire">Tarif horaire (FCFA)</label>
                <input type="number" id="tarif_horaire" name="tarif_horaire" step="0.01" min="0"
                       value="<?= e($salle['tarif_horaire'] ?? old('tarif_horaire')) ?>">
            </div>
            <div>
                <label for="localisation">Localisation</label>
                <input type="text" id="localisation" name="localisation" maxlength="120"
                       value="<?= e($salle['localisation'] ?? old('localisation')) ?>" placeholder="Bâtiment, étage…">
            </div>
            <div>
                <label for="equipements">Équipements</label>
                <input type="text" id="equipements" name="equipements" maxlength="255"
                       value="<?= e($salle['equipements'] ?? old('equipements')) ?>" placeholder="Vidéoprojecteur, tableau blanc…">
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primaire"><?= $salle ? 'Enregistrer les modifications' : 'Créer la salle' ?></button>
            <a href="<?= url('/salles') ?>" class="btn btn-discret">Annuler</a>
        </div>
    </form>
</div>

<?php require dirname(__DIR__) . '/layout/bas.php'; ?>
