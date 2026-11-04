<?php $titrePage = 'Accueil'; ?>
<?php require dirname(__DIR__) . '/layout/haut_publique.php'; ?>

<!-- ═══════════ Hero Carousel ═══════════ -->
<section class="hero-carousel">
    <div class="carousel-slides">
        <!-- Slide 1: Formations -->
        <div class="slide slide-formations" style="background-image:url('<?= asset('img/hero.jpg') ?>');">
            <div class="hero-overlay"></div>
            <div class="conteneur hero-contenu">
                <span class="hero-badge"><i class="fas fa-book"></i> Formations 100 % numérique · Inscription en ligne</span>
                <h1>Montez en compétences<br>avec nos formations.</h1>
                <p class="hero-texte">
                    Formez-vous avec des professionnels, à votre rythme : bureautique, développement web,
                    cybersécurité, réseaux informatiques. Inscriptions en ligne, identifiants reçus par e-mail.
                </p>
                <div class="hero-cta">
                    <a class="btn btn-primaire btn-grand" href="<?= url('/inscription') ?>">S'inscrire à une formation</a>
                </div>
                <p class="hero-reassurance">
                    <i class="fas fa-check"></i> Inscription en quelques clics · <i class="fas fa-envelope"></i> Identifiants reçus par e-mail
                </p>
            </div>
        </div>

        <!-- Slide 2: Réservation de salles -->
        <div class="slide slide-salles" style="background-image:url('<?= asset('img/salles.jpg') ?>');">
            <div class="hero-overlay"></div>
            <div class="conteneur hero-contenu">
                <span class="hero-badge"><i class="fas fa-door-open"></i> Salles équipées · Réservation en ligne</span>
                <h1>Réservez nos salles<br>pour vos événements.</h1>
                <p class="hero-texte">
                    Conférences, séminaires, réunions ou formations : déposez votre demande en ligne à l'heure précise.
                    Nos salles sont équipées de vidéoprojecteur, sonorisation et wifi. Confirmation du gestionnaire sous 24h.
                </p>
                <div class="hero-cta">
                    <a class="btn btn-primaire btn-grand" href="<?= url('/reserver') ?>">Demander une réservation</a>
                </div>
                <p class="hero-reassurance">
                    <i class="fas fa-check"></i> Réservation à l'heure précise · <i class="fas fa-bell"></i> Confirmé par e-mail
                </p>
            </div>
        </div>
    </div>

    <!-- Contrôles du carousel -->
    <button class="carousel-btn carousel-prev" type="button" aria-label="Slide précédente">
        <i class="fas fa-chevron-left"></i>
    </button>
    <button class="carousel-btn carousel-next" type="button" aria-label="Slide suivante">
        <i class="fas fa-chevron-right"></i>
    </button>

    <!-- Indicateurs -->
    <div class="carousel-indicators">
        <button class="indicator active" data-slide="0" aria-label="Formations"></button>
        <button class="indicator" data-slide="1" aria-label="Réservation de salles"></button>
    </div>
</section>

<!-- ═══════════ Nos formations ═══════════ -->
<section class="section" id="formations">
    <div class="conteneur">
        <div class="section-entete">
            <div>
                <span class="section-etiquette">Catalogue</span>
                <h2 class="section-titre">Nos formations dans le numérique</h2>
                <p class="section-sous-titre">
                    Des parcours complets, du grand débutant au niveau avancé, animés par des formateurs professionnels.
                </p>
            </div>
            <a class="btn btn-bleu" href="<?= url('/inscription') ?>">Voir les sessions et s'inscrire</a>
        </div>

        <div class="cartes-formations">
            <?php
            $niveauLib = ['DEBUTANT' => 'Débutant', 'INTERMEDIAIRE' => 'Intermédiaire', 'AVANCE' => 'Avancé'];
            $niveauCls = ['DEBUTANT' => 'badge-libre', 'INTERMEDIAIRE' => 'badge-reserve', 'AVANCE' => 'badge-occupe'];
            foreach ($formations as $f):
                $image = str_contains($f['theme'], 'Bureautique') || str_contains($f['theme'], 'Culture')
                    ? 'formation-bureautique.jpg'
                    : (str_contains($f['theme'], 'Développement') ? 'formation-web.jpg'
                    : (str_contains($f['theme'], 'Sécurité') ? 'formation-securite.jpg' : 'formation-reseaux.jpg'));
            ?>
                <article class="carte-formation">
                    <div class="carte-formation-img" style="background-image:url('<?= asset('img/' . $image) ?>');">
                        <span class="badge <?= e($niveauCls[$f['niveau']]) ?>"><?= e($niveauLib[$f['niveau']]) ?></span>
                    </div>
                    <div class="carte-formation-corps">
                        <span class="carte-formation-theme"><?= e($f['theme']) ?></span>
                        <h3><?= e($f['titre']) ?></h3>
                        <p><?= e(mb_substr($f['description'] ?? '', 0, 110)) ?><?= ($f['description'] && mb_strlen($f['description']) > 110) ? '…' : '' ?></p>
                        <div class="carte-formation-meta">
                            <span><i class="fas fa-clock"></i> <?= (int) $f['duree_heures'] ?> heures</span>
                            <span class="carte-formation-prix"><?= format_fcfa($f['tarif']) ?></span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if ($sessionsOuvertes !== []): ?>
            <div class="prochaines-sessions">
                <h3><i class="fas fa-map-pin"></i> Prochaines sessions ouvertes</h3>
                <ul>
                    <?php foreach ($sessionsOuvertes as $s): ?>
                        <li>
                            <div>
                                <strong><?= e($s['formation_titre']) ?></strong><br>
                                <span class="secondaire">
                                    Du <?= e(format_date_fr($s['date_debut'], false)) ?> au <?= e(format_date_fr($s['date_fin'], false)) ?>
                                    · <?= e(format_heure($s['heure_debut'])) ?> à <?= e(format_heure($s['heure_fin'])) ?>
                                    · <?= e($s['salle_nom']) ?>
                                </span>
                            </div>
                            <div class="prochaine-session-droite">
                                <span class="badge badge-libre"><?= (int) $s['places_restantes'] ?> place<?= (int) $s['places_restantes'] > 1 ? 's' : '' ?></span>
                                <a class="btn btn-petit btn-primaire" href="<?= url('/inscription') ?>">S'inscrire</a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ═══════════ Nos salles ═══════════ -->
<section class="section section-teinte" id="salles">
    <div class="conteneur split-salles">
        <div class="split-image" style="background-image:url('<?= asset('img/salles.jpg') ?>');"></div>
        <div class="split-contenu">
            <span class="section-etiquette">Vos événements</span>
            <h2 class="section-titre">Des salles équipées, prêtes pour vos événements</h2>
            <p class="section-sous-titre">
                Conférences, séminaires, réunions ou formations : déposez votre demande en ligne
                à l'heure près, et notre gestionnaire vous confirme après vérification du planning.
            </p>
            <ul class="atouts">
                <li><span class="atout-ic"><i class="fas fa-check"></i></span> Réservation à l'heure précise, sans chevauchement possible</li>
                <li><span class="atout-ic"><i class="fas fa-check"></i></span> Salles équipées : vidéoprojecteur, sonorisation, climatisation, wifi</li>
                <li><span class="atout-ic"><i class="fas fa-check"></i></span> Statut en direct : en attente, réservé, occupé</li>
                <li><span class="atout-ic"><i class="fas fa-check"></i></span> Confirmation et suivi notifiés par e-mail</li>
            </ul>
            <div class="salles-chips">
                <?php foreach ($salles as $s): ?>
                    <span class="chip"><?= e($s['nom']) ?> · <?= (int) $s['capacite'] ?> places</span>
                <?php endforeach; ?>
            </div>
            <div class="hero-cta" style="justify-content:flex-start;">
                <a class="btn btn-primaire" href="<?= url('/reserver') ?>">Demander une réservation</a>
                <span class="aide-champ" style="align-self:center;">Réponse du gestionnaire sous 24 h ouvrées</span>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════ Comment ça marche ═══════════ -->
<section class="section" id="etapes">
    <div class="conteneur">
        <div class="section-entete section-centree">
            <div>
                <span class="section-etiquette">Parcours apprenant</span>
                <h2 class="section-titre">Comment ça marche ?</h2>
                <p class="section-sous-titre">Quatre étapes simples pour rejoindre une formation.</p>
            </div>
        </div>
        <div class="etapes">
            <div class="etape">
                <div class="etape-num">1</div>
                <h3>Choisissez votre formation</h3>
                <p>Parcourez le catalogue et sélectionnez la session qui vous convient dans la liste des sessions ouvertes.</p>
            </div>
            <div class="etape">
                <div class="etape-num">2</div>
                <h3>Vérifiez votre e-mail</h3>
                <p>Un clic sur le lien reçu dans votre boîte de réception confirme votre adresse.</p>
            </div>
            <div class="etape">
                <div class="etape-num">3</div>
                <h3>Recevez vos identifiants</h3>
                <p>Votre matricule d'apprenant et votre mot de passe vous sont envoyés par e-mail.</p>
            </div>
            <div class="etape">
                <div class="etape-num">4</div>
                <h3>Suivez votre formation</h3>
                <p>Dates, horaires, salle, formateur : tout est dans votre espace personnel.</p>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════ Appel à l'action final ═══════════ -->
<section class="cta-final">
    <div class="conteneur cta-final-inner">
        <div>
            <h2>Prêt à vous lancer ?</h2>
            <p>Rejoignez nos apprenants ou réservez l'une de nos salles pour votre prochain événement.</p>
        </div>
        <div class="hero-cta">
            <a class="btn btn-blanc btn-grand" href="<?= url('/inscription') ?>">S'inscrire à une formation</a>
            <a class="btn btn-contour-clair btn-grand" href="<?= url('/reserver') ?>">Réserver une salle</a>
        </div>
    </div>
</section>

<?php require dirname(__DIR__) . '/layout/bas_publique.php'; ?>
