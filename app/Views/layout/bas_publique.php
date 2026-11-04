    </main>

    <!-- Pied de page -->
    <footer class="footer-publique" id="contact">
        <div class="conteneur footer-colonnes">
            <div class="footer-col">
                <a class="marque" href="<?= url('/') ?>">
                    <span class="logo">GCF</span>
                    <span class="marque-texte">Centre de<br>Formation</span>
                </a>
                <p class="footer-pitch">
                    Votre plateforme de formation dans le numérique et de réservation de salles :
                    inscriptions en ligne, planning en temps réel et suivi de votre dossier.
                </p>
            </div>
            <div class="footer-col">
                <h4>La plateforme</h4>
                <ul>
                    <li><a href="<?= url('/inscription') ?>">S'inscrire à une formation</a></li>
                    <li><a href="<?= url('/reserver') ?>">Demander une salle</a></li>
                    <li><a href="<?= url('/login') ?>">Espace personnel</a></li>
                    <li><a href="<?= url('/#etapes') ?>">Comment ça marche</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Nos formations</h4>
                <ul>
                    <li><a href="<?= url('/#formations') ?>">Bureautique</a></li>
                    <li><a href="<?= url('/#formations') ?>">Développement web</a></li>
                    <li><a href="<?= url('/#formations') ?>">Cybersécurité</a></li>
                    <li><a href="<?= url('/#formations') ?>">Réseaux et maintenance</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bas">
            <div class="conteneur footer-bas-inner">
                <span>© <?= date('Y') ?> GCF · Gestion Centre de Formation. Tous droits réservés.</span>
                <a href="<?= url('/login') ?>">Connexion</a>
            </div>
        </div>
    </footer>
</div>
<?php App\Core\Flash::viderOld(); // les valeurs de formulaire ne survivent pas à un second affichage ?>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
