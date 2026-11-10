<?php $titrePage = 'Comment ça marche'; $descPage = "Comment ça marche : s'inscrire à une formation ou demander une salle du centre, étape par étape, avec une notification par e-mail à chaque étape."; ?>
<?php require dirname(__DIR__) . '/layout/haut_publique.php'; ?>

<div class="conteneur page-publique page-publique-large">

    <div class="entete-page-publique">
        <h1>Comment ça marche ?</h1>
        <p>
            La plateforme accompagne deux démarches : s'inscrire à une formation,
            et demander la réservation d'une salle pour un événement.
            Dans les deux cas, tout se fait en ligne, avec un suivi par e-mail.
        </p>
    </div>

    <div class="guide-grille">

        <!-- ══ Parcours 1 : s'inscrire à une formation ══ -->
        <section class="guide-parcours reveal">
            <div class="guide-parcours-entete guide-parcours-formation">
                <span class="guide-parcours-ic"><i class="fas fa-graduation-cap" aria-hidden="true"></i></span>
                <div>
                    <span class="guide-parcours-etiquette">Vous êtes apprenant</span>
                    <h2>S'inscrire à une formation</h2>
                </div>
            </div>
            <ol class="etapes etapes-verticales">
                <li class="etape">
                    <div class="etape-num">1</div>
                    <h3>Choisissez votre formation</h3>
                    <p>Parcourez le catalogue et sélectionnez la session qui vous convient parmi les sessions ouvertes, avec leurs dates, horaires et salle.</p>
                </li>
                <li class="etape">
                    <div class="etape-num">2</div>
                    <h3>Remplissez le formulaire d'inscription</h3>
                    <p>Nom, prénoms, e-mail, téléphone : quelques informations suffisent, aucun compte à créer au départ.</p>
                </li>
                <li class="etape">
                    <div class="etape-num">3</div>
                    <h3>Confirmez votre e-mail</h3>
                    <p>Un clic sur le lien reçu dans votre boîte de réception valide votre adresse. Pensez à vérifier vos courriers indésirables.</p>
                </li>
                <li class="etape">
                    <div class="etape-num">4</div>
                    <h3>Recevez vos identifiants</h3>
                    <p>Votre matricule d'apprenant et votre mot de passe vous sont envoyés par e-mail.</p>
                </li>
                <li class="etape">
                    <div class="etape-num">5</div>
                    <h3>Suivez votre formation</h3>
                    <p>Dates, horaires, salle et formateur : tout est visible dans votre espace personnel, accessible avec votre e-mail ou votre matricule.</p>
                </li>
            </ol>
            <a class="btn btn-primaire" href="<?= url('/inscription') ?>">S'inscrire à une formation</a>
        </section>

        <!-- ══ Parcours 2 : demander une salle ══ -->
        <section class="guide-parcours reveal">
            <div class="guide-parcours-entete guide-parcours-salle">
                <span class="guide-parcours-ic"><i class="fas fa-door-open" aria-hidden="true"></i></span>
                <div>
                    <span class="guide-parcours-etiquette">Vous organisez un événement</span>
                    <h2>Demander une salle</h2>
                </div>
            </div>
            <ol class="etapes etapes-verticales">
                <li class="etape">
                    <div class="etape-num">1</div>
                    <h3>Choisissez votre salle</h3>
                    <p><?= (int) $nbSalles ?> salles équipées : capacité, vidéoprojecteur, sonorisation, climatisation, wifi. Consultez la page Nos salles pour choisir.</p>
                </li>
                <li class="etape">
                    <div class="etape-num">2</div>
                    <h3>Déposez votre demande en ligne</h3>
                    <p>Date, heure de début, heure de fin : la demande se pose à l'heure près, de 07h à 21h, avec l'objet de votre événement.</p>
                </li>
                <li class="etape">
                    <div class="etape-num">3</div>
                    <h3>Le planning vérifie tout seul</h3>
                    <p>Aucun chevauchement n'est possible : si le créneau est déjà pris, la plateforme vous propose immédiatement les créneaux libres du jour et les prochaines dates.</p>
                </li>
                <li class="etape">
                    <div class="etape-num">4</div>
                    <h3>Le gestionnaire confirme</h3>
                    <p>Sous 24 h ouvrées, votre demande passe d'« en attente » à « réservé », ou est refusée avec les disponibilités réelles.</p>
                </li>
                <li class="etape">
                    <div class="etape-num">5</div>
                    <h3>Suivez votre réservation</h3>
                    <p>Chaque étape vous est notifiée par e-mail : enregistrement, confirmation, et éventuel refus motivé avec les créneaux proposés.</p>
                </li>
            </ol>
            <a class="btn btn-primaire" href="<?= url('/reserver') ?>">Demander une réservation</a>
        </section>
    </div>

    <div class="encart-reussite" style="margin-top:34px;">
        <strong>Bon à savoir :</strong> la connexion à l'espace personnel se fait avec votre e-mail
        <em>ou</em> votre matricule, accompagné de votre mot de passe. En cas d'oubli, contactez le centre au
        +229 01 46 46 66 56.
    </div>

</div>

<?php require dirname(__DIR__) . '/layout/bas_publique.php'; ?>
