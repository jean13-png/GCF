<?php $titrePage = 'Réserver une salle'; ?>
<?php require dirname(__DIR__) . '/layout/haut_publique.php'; ?>

<div class="conteneur page-publique">
    <div class="entete-page-publique">
        <h1>Demande de réservation de salle</h1>
        <p>
            Choisissez la salle, la date et le créneau souhaités. Votre demande est examinée par
            notre gestionnaire : vous recevez la réponse par e-mail, généralement sous 24 h ouvrées.
        </p>
    </div>

    <?php require __DIR__ . '/_disponibilites.php'; ?>

    <?php if ($salles === []): ?>
        <div class="encart-info">
            Aucune salle n'est disponible pour le moment (maintenance). Merci de réessayer plus tard.
        </div>
    <?php else: ?>
        <div class="carte-publique-large">
            <form method="post" action="<?= url('/reserver') ?>">
                <?= csrf_champ() ?>
                <h3 class="titre-groupe">Votre événement</h3>
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
                    </div>
                    <div class="entiere">
                        <label for="evenement">Intitulé de l'événement <span class="obligatoire">*</span></label>
                        <input type="text" id="evenement" name="evenement" required maxlength="200"
                               value="<?= e(old('evenement')) ?>" placeholder="Ex. : conférence, séminaire, réunion, atelier…">
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
                </div>

                <h3 class="titre-groupe">Vos coordonnées</h3>
                <div class="form-grille">
                    <div>
                        <label for="demandeur_nom">Nom et prénom <span class="obligatoire">*</span></label>
                        <input type="text" id="demandeur_nom" name="demandeur_nom" required maxlength="150"
                               value="<?= e(old('demandeur_nom')) ?>">
                    </div>
                    <div>
                        <label for="demandeur_organisation">Organisation (facultatif)</label>
                        <input type="text" id="demandeur_organisation" name="demandeur_organisation" maxlength="150"
                               value="<?= e(old('demandeur_organisation')) ?>">
                    </div>
                    <div>
                        <label for="demandeur_email">E-mail <span class="obligatoire">*</span></label>
                        <input type="email" id="demandeur_email" name="demandeur_email" required maxlength="190"
                               value="<?= e(old('demandeur_email')) ?>">
                        <div class="aide-champ">La réponse vous est envoyée à cette adresse.</div>
                    </div>
                    <div>
                        <label for="demandeur_telephone">Téléphone</label>
                        <input type="text" id="demandeur_telephone" name="demandeur_telephone" maxlength="30"
                               value="<?= e(old('demandeur_telephone')) ?>" placeholder="+229 ...">
                    </div>
                </div>

                <div class="encart-info" style="margin:18px 0 0;">
                    Une fois déposée, votre demande passe à l'état <strong>« En attente »</strong>.
                    Le gestionnaire vérifie le planning puis la confirme : la salle devient <strong>Réservée</strong>
                    sur votre créneau, puis <strong>Occupée</strong> le jour de l'événement.
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primaire btn-grand">Envoyer ma demande</button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php require dirname(__DIR__) . '/layout/bas_publique.php'; ?>
