<?php $titrePage = $formation ? 'Modifier la formation' : 'Nouvelle formation'; ?>
<?php require dirname(__DIR__) . '/layout/haut.php'; ?>

<div class="carte" style="max-width:800px;">
    <div class="carte-entete">
        <h3><?= $formation ? e($formation['titre']) : 'Ajouter une formation au catalogue' ?></h3>
        <a class="btn btn-petit btn-discret" href="<?= url('/formations') ?>">← Retour</a>
    </div>

    <form method="post" action="<?= url('/formations/enregistrer') ?>">
        <?= csrf_champ() ?>
        <input type="hidden" name="id" value="<?= (int) ($formation['id'] ?? 0) ?>">
        <div class="form-grille">
            <div class="entiere">
                <label for="titre">Titre de la formation <span class="obligatoire">*</span></label>
                <input type="text" id="titre" name="titre" required maxlength="200"
                       value="<?= e($formation['titre'] ?? old('titre')) ?>" placeholder="Ex. : Développement web : HTML, CSS et JavaScript">
            </div>
            <div>
                <label for="theme">Thème <span class="obligatoire">*</span></label>
                <input type="text" id="theme" name="theme" required maxlength="120"
                       value="<?= e($formation['theme'] ?? old('theme')) ?>" placeholder="Bureautique, Réseaux…">
            </div>
            <div>
                <label for="niveau">Niveau <span class="obligatoire">*</span></label>
                <select id="niveau" name="niveau" required>
                    <?php foreach ($niveaux as $n): ?>
                        <option value="<?= e($n) ?>" <?= ($formation['niveau'] ?? old('niveau')) === $n ? 'selected' : '' ?>>
                            <?= e(['DEBUTANT' => 'Débutant', 'INTERMEDIAIRE' => 'Intermédiaire', 'AVANCE' => 'Avancé'][$n]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="duree_heures">Durée totale (heures) <span class="obligatoire">*</span></label>
                <input type="number" id="duree_heures" name="duree_heures" required min="1" max="2000"
                       value="<?= e($formation['duree_heures'] ?? old('duree_heures', 20)) ?>">
            </div>
            <div>
                <label for="tarif">Tarif (FCFA)</label>
                <input type="number" id="tarif" name="tarif" step="0.01" min="0"
                       value="<?= e($formation['tarif'] ?? old('tarif')) ?>">
            </div>
            <div class="entiere">
                <label for="public_vise">Public visé</label>
                <input type="text" id="public_vise" name="public_vise" maxlength="255"
                       value="<?= e($formation['public_vise'] ?? old('public_vise')) ?>" placeholder="Ex. : employés de bureau, jeunes diplômés…">
            </div>
            <div class="entiere">
                <label for="description">Description</label>
                <textarea id="description" name="description" maxlength="3000"><?= e($formation['description'] ?? old('description')) ?></textarea>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primaire"><?= $formation ? 'Enregistrer les modifications' : 'Ajouter au catalogue' ?></button>
            <a href="<?= url('/formations') ?>" class="btn btn-discret">Annuler</a>
        </div>
    </form>
</div>

<?php require dirname(__DIR__) . '/layout/bas.php'; ?>
