<?php $titrePage = 'Nouvelle demande de réservation'; ?>
<?php require dirname(__DIR__) . '/layout/haut.php'; ?>

<div class="carte" style="max-width:800px;">
    <div class="carte-entete">
        <h3>Déposer une demande de réservation</h3>
        <a class="btn btn-petit btn-discret" href="<?= url('/reservations') ?>"><i class="fas fa-arrow-left"></i> Retour</a>
    </div>

    <div class="encart-info">
        La demande passe d'abord par l'état <strong>« En attente »</strong> : elle ne devient ferme qu'après validation par le gestionnaire des salles,
        qui vérifie l'absence de conflit d'horaires. Le demandeur est informé par e-mail à chaque étape.
    </div>

    <?php require __DIR__ . '/_disponibilites.php'; ?>

    <form method="post" action="<?= url('/reservations/enregistrer') ?>">
        <?= csrf_champ() ?>
        <div class="form-grille">
            <div class="entiere">
                <label for="salle_id">Salle souhaitée <span class="obligatoire">*</span></label>
                <select id="salle_id" name="salle_id" required>
                    <option value="">— Choisir une salle —</option>
                    <?php foreach ($salles as $s): ?>
                        <option value="<?= (int) $s['id'] ?>" <?= (int) old('salle_id') === (int) $s['id'] ? 'selected' : '' ?>>
                            <?= e($s['nom']) ?> · <?= (int) $s['capacite'] ?> places · <?= e($s['equipements'] ?: 'sans équipement particulier') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="aide-champ">Les salles en maintenance n'apparaissent pas.</div>
            </div>
            <div class="entiere">
                <label for="evenement">Intitulé de l'événement <span class="obligatoire">*</span></label>
                <input type="text" id="evenement" name="evenement" required maxlength="200"
                       value="<?= e(old('evenement')) ?>" placeholder="Ex. : Conférence annuelle, séminaire, réunion…">
            </div>
            <div>
                <label for="date_reservation">Date <span class="obligatoire">*</span></label>
                <input type="date" id="date_reservation" name="date_reservation" required
                       value="<?= e(old('date_reservation')) ?>" min="<?= date('Y-m-d') ?>">
            </div>
            <div>
                <label for="nb_personnes">Nombre de personnes attendues</label>
                <input type="number" id="nb_personnes" name="nb_personnes" min="0"
                       value="<?= e(old('nb_personnes')) ?>">
            </div>
            <div>
                <label for="heure_debut">Heure de début <span class="obligatoire">*</span></label>
                <input type="time" id="heure_debut" name="heure_debut" required
                       value="<?= e(old('heure_debut')) ?>" placeholder="08:00">
            </div>
            <div>
                <label for="heure_fin">Heure de fin <span class="obligatoire">*</span></label>
                <input type="time" id="heure_fin" name="heure_fin" required
                       value="<?= e(old('heure_fin')) ?>" placeholder="12:00">
            </div>
            <div>
                <label for="demandeur_nom">Nom du demandeur <span class="obligatoire">*</span></label>
                <input type="text" id="demandeur_nom" name="demandeur_nom" required maxlength="150"
                       value="<?= e(old('demandeur_nom', auth()['prenom'] . ' ' . auth()['nom'])) ?>">
            </div>
            <div>
                <label for="demandeur_organisation">Organisation (facultatif)</label>
                <input type="text" id="demandeur_organisation" name="demandeur_organisation" maxlength="150"
                       value="<?= e(old('demandeur_organisation')) ?>">
            </div>
            <div>
                <label for="demandeur_email">E-mail du demandeur <span class="obligatoire">*</span></label>
                <input type="email" id="demandeur_email" name="demandeur_email" maxlength="190"
                       value="<?= e(old('demandeur_email', auth()['email'])) ?>">
                <div class="aide-champ">Pour recevoir la confirmation et les notifications.</div>
            </div>
            <div>
                <label for="demandeur_telephone">Téléphone</label>
                <input type="text" id="demandeur_telephone" name="demandeur_telephone" maxlength="30"
                       value="<?= e(old('demandeur_telephone')) ?>" placeholder="+229 ...">
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primaire">Déposer la demande</button>
            <a href="<?= url('/reservations') ?>" class="btn btn-discret">Annuler</a>
        </div>
    </form>
</div>

<?php require dirname(__DIR__) . '/layout/bas.php'; ?>
