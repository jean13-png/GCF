<?php $titrePage = $session ? 'Modifier la session' : 'Programmer une session'; ?>
<?php require dirname(__DIR__) . '/layout/haut.php'; ?>

<div class="carte" style="max-width:820px;">
    <div class="carte-entete">
        <h3><?= $session ? 'Session · ' . e($session['formation_titre']) : 'Nouvelle session de formation' ?></h3>
        <a class="btn btn-petit btn-discret" href="<?= url('/sessions') ?>"><i class="fas fa-arrow-left"></i> Retour</a>
    </div>

    <?php if (!$session): ?>
        <div class="encart-info">
            La salle choisie sera <strong>réservée automatiquement</strong> pour chaque jour de la période,
            aux horaires indiqués, et apparaîtra comme occupée dans le planning des salles.
        </div>
    <?php endif; ?>

    <form method="post" action="<?= url('/sessions/enregistrer') ?>">
        <?= csrf_champ() ?>
        <input type="hidden" name="id" value="<?= (int) ($session['id'] ?? 0) ?>">
        <div class="form-grille">
            <div class="entiere">
                <label for="formation_id">Formation <span class="obligatoire">*</span></label>
                <select id="formation_id" name="formation_id" required>
                    <option value="">— Choisir une formation active —</option>
                    <?php foreach ($formations as $f): ?>
                        <option value="<?= (int) $f['id'] ?>" <?= (int) ($session['formation_id'] ?? old('formation_id')) === (int) $f['id'] ? 'selected' : '' ?>>
                            <?= e($f['titre']) ?> (<?= e($f['theme']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="salle_id">Salle <span class="obligatoire">*</span></label>
                <select id="salle_id" name="salle_id" required>
                    <option value="">— Choisir une salle —</option>
                    <?php foreach ($salles as $s): ?>
                        <option value="<?= (int) $s['id'] ?>" <?= (int) ($session['salle_id'] ?? old('salle_id')) === (int) $s['id'] ? 'selected' : '' ?>>
                            <?= e($s['nom']) ?> · <?= (int) $s['capacite'] ?> places
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="places">Places ouvertes <span class="obligatoire">*</span></label>
                <input type="number" id="places" name="places" required min="1" max="500"
                       value="<?= e($session['places'] ?? old('places', 20)) ?>">
            </div>
            <div>
                <label for="date_debut">Date de début <span class="obligatoire">*</span></label>
                <input type="date" id="date_debut" name="date_debut" required
                       value="<?= e($session['date_debut'] ?? old('date_debut')) ?>">
            </div>
            <div>
                <label for="date_fin">Date de fin <span class="obligatoire">*</span></label>
                <input type="date" id="date_fin" name="date_fin" required
                       value="<?= e($session['date_fin'] ?? old('date_fin')) ?>">
            </div>
            <div>
                <label for="heure_debut">Heure de début quotidienne <span class="obligatoire">*</span></label>
                <input type="time" id="heure_debut" name="heure_debut" required
                       value="<?= e($session['heure_debut'] ?? old('heure_debut', '09:00')) ?>">
            </div>
            <div>
                <label for="heure_fin">Heure de fin quotidienne <span class="obligatoire">*</span></label>
                <input type="time" id="heure_fin" name="heure_fin" required
                       value="<?= e($session['heure_fin'] ?? old('heure_fin', '13:00')) ?>">
            </div>
            <div>
                <label for="formateur_nom">Formateur pressenti</label>
                <input type="text" id="formateur_nom" name="formateur_nom" maxlength="150"
                       value="<?= e($session['formateur_nom'] ?? old('formateur_nom')) ?>" placeholder="Ex. : Mme LOKO Sabine">
            </div>
            <div>
                <label for="cout">Coût de la session (FCFA)</label>
                <input type="number" id="cout" name="cout" step="0.01" min="0"
                       value="<?= e($session['cout'] ?? old('cout')) ?>">
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primaire"><?= $session ? 'Enregistrer les modifications' : 'Programmer la session' ?></button>
            <a href="<?= url('/sessions') ?>" class="btn btn-discret">Annuler</a>
        </div>
    </form>
</div>

<?php require dirname(__DIR__) . '/layout/bas.php'; ?>
