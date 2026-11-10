<?php $titrePage = 'Contact'; $descPage = "Contacter le centre de formation : adresse à Cotonou (quartier Akpakpa), téléphone et formulaire en ligne pour toute question."; ?>
<?php require dirname(__DIR__) . '/layout/haut_publique.php'; ?>

<div class="conteneur page-publique contact-grille">

    <!-- ══ Coordonnées et informations ══ -->
    <div class="contact-infos">
        <div class="entete-page-publique">
            <h1>Contact</h1>
            <p>
                Une question sur une formation, une demande de salle, une précision
                avant de vous inscrire ? Écrivez-nous : nous répondons dans les meilleurs délais.
            </p>
        </div>

        <ul class="contact-fiches">
            <li class="contact-fiche">
                <span class="contact-fiche-ic"><i class="fas fa-phone" aria-hidden="true"></i></span>
                <div>
                    <strong>Téléphone</strong>
                    <a href="tel:+2290146466656">+229 01 46 46 66 56</a>
                </div>
            </li>
            <li class="contact-fiche">
                <span class="contact-fiche-ic"><i class="fas fa-location-dot" aria-hidden="true"></i></span>
                <div>
                    <strong>Adresse</strong>
                    <span>Cotonou, quartier Akpakpa<br>à proximité de la zone industrielle</span>
                </div>
            </li>
            <li class="contact-fiche">
                <span class="contact-fiche-ic"><i class="fas fa-clock" aria-hidden="true"></i></span>
                <div>
                    <strong>Horaires</strong>
                    <span>Lundi au samedi<br>07h à 21h</span>
                </div>
            </li>
            <li class="contact-fiche">
                <span class="contact-fiche-ic"><i class="fas fa-reply" aria-hidden="true"></i></span>
                <div>
                    <strong>Délai de réponse</strong>
                    <span>Messages traités sous 24 h ouvrées</span>
                </div>
            </li>
        </ul>

        <div class="encart-info">
            <i class="fas fa-circle-info" aria-hidden="true"></i>
            Pour demander une salle, le plus rapide reste le
            <a href="<?= url('/reserver') ?>">formulaire de réservation en ligne</a> :
            il vérifie tout seul les disponibilités à l'heure près.
        </div>
    </div>

    <!-- ══ Formulaire ══ -->
    <div class="carte-publique carte-contact">
        <h2>Envoyer un message</h2>
        <p class="sous-titre">Tous les champs marqués d'un astérisque sont obligatoires.</p>

        <form method="post" action="<?= url('/contact') ?>" class="form-grille">
            <?= \App\Core\CSRF::champ() ?>

            <!-- Piège anti-robots : champ invisible, ne pas remplir -->
            <div class="champ" style="position:absolute;left:-9999px;" aria-hidden="true">
                <label for="site_web">Site web</label>
                <input type="text" id="site_web" name="site_web" tabindex="-1" autocomplete="off">
            </div>

            <div class="champ">
                <label for="nom">Nom complet <span class="obligatoire">*</span></label>
                <input type="text" id="nom" name="nom" maxlength="120" required autocomplete="name">
            </div>

            <div class="champ">
                <label for="email">Adresse e-mail <span class="obligatoire">*</span></label>
                <input type="email" id="email" name="email" maxlength="190" required autocomplete="email">
            </div>

            <div class="champ">
                <label for="telephone">Téléphone</label>
                <input type="tel" id="telephone" name="telephone" maxlength="30" autocomplete="tel" placeholder="Ex. : 01 46 46 66 56">
            </div>

            <div class="champ">
                <label for="sujet">Sujet <span class="obligatoire">*</span></label>
                <input type="text" id="sujet" name="sujet" maxlength="190" required>
            </div>

            <div class="champ entiere">
                <label for="message">Votre message <span class="obligatoire">*</span></label>
                <textarea id="message" name="message" rows="7" minlength="10" required></textarea>
            </div>

            <div class="form-actions entiere">
                <button type="submit" class="btn btn-primaire btn-grand">
                    <i class="fas fa-paper-plane" aria-hidden="true"></i>&nbsp; Envoyer le message
                </button>
            </div>
        </form>
    </div>
</div>

<?php require dirname(__DIR__) . '/layout/bas_publique.php'; ?>
