<?php $descPage = "Centre de formation numérique de l'ONG-GAS à Cotonou (Akpakpa) : formations en informatique pour tous les niveaux, inscription en ligne et réservation de salles équipées."; ?>
<?php require dirname(__DIR__) . '/layout/haut_publique.php'; ?>

<!-- ═══════════════════════════════════════════════════════════════
     Hero : promesse du centre sur photo réelle, avec chiffres clés
     ═══════════════════════════════════════════════════════════════ -->
<section class="accueil-hero">
    <img class="accueil-hero-img" src="<?= asset('img/hero.jpg') ?>" alt="Apprenants en formation informatique devant les ordinateurs du centre" width="1920" height="1080" fetchpriority="high" decoding="async">
    <div class="accueil-hero-voile" aria-hidden="true"></div>
    <div class="conteneur accueil-hero-contenu">
        <span class="accueil-badge">
            <i class="fas fa-location-dot" aria-hidden="true"></i>
            Centre de formation professionnelle · Cotonou
        </span>
        <h1>Apprendre le numérique,<br>bâtir son avenir.</h1>
        <p class="accueil-hero-texte">
            Bureautique, développement web, cybersécurité, réseaux, maintenance :
            des parcours pratiques animés par des professionnels, du grand débutant
            au niveau avancé. Inscrivez-vous en ligne et recevez vos identifiants par e-mail.
        </p>
        <div class="accueil-hero-cta">
            <a class="btn btn-blanc btn-grand" href="#formations">
                <i class="fas fa-graduation-cap" aria-hidden="true"></i>&nbsp; Choisir une formation
            </a>
            <a class="btn btn-contour-clair btn-grand" href="<?= url('/reserver') ?>">
                <i class="fas fa-door-open" aria-hidden="true"></i>&nbsp; Réserver une salle
            </a>
        </div>
        <ul class="accueil-garanties">
            <li><i class="fas fa-circle-check" aria-hidden="true"></i> Inscription 100 % en ligne</li>
            <li><i class="fas fa-envelope" aria-hidden="true"></i> Identifiants par e-mail</li>
            <li><i class="fas fa-clock" aria-hidden="true"></i> Salles à l'heure près</li>
        </ul>
    </div>
</section>

<!-- Chiffres clés réels, posés à cheval sur le bas du hero -->
<section class="accueil-stats" aria-label="Le centre en chiffres">
    <div class="conteneur stats-grille">
        <div class="stat-carte reveal">
            <span class="stat-ic"><i class="fas fa-chalkboard-user" aria-hidden="true"></i></span>
            <span class="stat-valeur"><span data-compteur="<?= (int) $stats['formations'] ?>">0</span></span>
            <span class="stat-libelle">formations<br>au catalogue</span>
        </div>
        <div class="stat-carte reveal">
            <span class="stat-ic"><i class="fas fa-user-graduate" aria-hidden="true"></i></span>
            <span class="stat-valeur"><span data-compteur="<?= (int) $stats['apprenants'] ?>">0</span></span>
            <span class="stat-libelle">apprenants<br>inscrits</span>
        </div>
        <div class="stat-carte reveal">
            <span class="stat-ic"><i class="fas fa-building-columns" aria-hidden="true"></i></span>
            <span class="stat-valeur"><span data-compteur="<?= (int) $stats['salles'] ?>">0</span></span>
            <span class="stat-libelle">salles équipées<br>et climatisées</span>
        </div>
        <div class="stat-carte reveal">
            <span class="stat-ic"><i class="fas fa-calendar-check" aria-hidden="true"></i></span>
            <span class="stat-valeur"><span data-compteur="<?= (int) $stats['sessions'] ?>">0</span></span>
            <span class="stat-libelle">sessions ouvertes<br>à l'inscription</span>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     Catalogue des formations
     ═══════════════════════════════════════════════════════════════ -->
<section class="section" id="formations">
    <div class="conteneur">
        <div class="section-entete">
            <div>
                <span class="section-etiquette">Catalogue</span>
                <h2 class="section-titre">Des formations pour tous les niveaux</h2>
                <p class="section-sous-titre">
                    Chaque parcours mêle les bases essentielles et la pratique sur machines,
                    en petits groupes, avec un formateur professionnel.
                </p>
            </div>
            <a class="btn btn-bleu" href="<?= url('/inscription') ?>">Voir les sessions et s'inscrire</a>
        </div>

        <div class="cartes-formations">
            <?php
            $niveauLib = ['DEBUTANT' => 'Débutant', 'INTERMEDIAIRE' => 'Intermédiaire', 'AVANCE' => 'Avancé'];
            $niveauCls = ['DEBUTANT' => 'niveau-debutant', 'INTERMEDIAIRE' => 'niveau-intermediaire', 'AVANCE' => 'niveau-avance'];

            // Une photo réelle par formation, selon son intitulé
            $imageParFormation = [
                'Initiation à l\'informatique'                              => 'formation-initiation.jpg',
                'Bureautique professionnelle : Word, Excel, PowerPoint'     => 'formation-bureautique.jpg',
                'Internet et outils numériques'                             => 'formation-internet.jpg',
                'Développement web : HTML, CSS et JavaScript'               => 'formation-web.jpg',
                'Créer son site avec WordPress'                             => 'formation-wordpress.jpg',
                'Cybersécurité : se protéger au quotidien'                  => 'formation-securite.jpg',
                'Réseaux informatiques : les fondamentaux'                  => 'formation-reseaux.jpg',
                'Maintenance et dépannage des ordinateurs'                  => 'formation-maintenance.jpg',
            ];
            $altParImage = [
                'formation-initiation.jpg'   => 'Débutants en apprentissage de l\'informatique en salle de formation',
                'formation-bureautique.jpg'  => 'Apprenante en formation bureautique devant son ordinateur portable',
                'formation-internet.jpg'     => 'Apprenante utilisant un ordinateur portable et un smartphone pour se connecter',
                'formation-web.jpg'          => 'Développeur web au travail sur du code dans un bureau moderne',
                'formation-wordpress.jpg'    => 'Création de site web sur écran d\'ordinateur',
                'formation-securite.jpg'     => 'Cadenas de la sécurité informatique sur une porte bleue',
                'formation-reseaux.jpg'      => 'Câbles réseau raccordés à une baie serveur',
                'formation-maintenance.jpg'  => 'Réparation d\'une carte mère d\'ordinateur au tournevis',
            ];

            foreach ($formations as $f):
                $image = $imageParFormation[$f['titre']] ?? 'formation-web.jpg';
            ?>
                <article class="carte-formation reveal">
                    <a class="carte-formation-visuel" href="<?= url('/inscription') ?>" tabindex="-1" aria-hidden="true">
                        <img src="<?= asset('img/' . $image) ?>" alt="<?= e($altParImage[$image]) ?>" loading="lazy" decoding="async" width="800" height="500">
                        <span class="badge <?= e($niveauCls[$f['niveau']]) ?>"><?= e($niveauLib[$f['niveau']]) ?></span>
                    </a>
                    <div class="carte-formation-corps">
                        <span class="carte-formation-theme"><?= e($f['theme']) ?></span>
                        <h3><a href="<?= url('/inscription') ?>"><?= e($f['titre']) ?></a></h3>
                        <p><?= e(mb_substr($f['description'] ?? '', 0, 120)) ?><?= ($f['description'] && mb_strlen($f['description']) > 120) ? '…' : '' ?></p>
                        <div class="carte-formation-meta">
                            <span><i class="fas fa-clock" aria-hidden="true"></i> <?= (int) $f['duree_heures'] ?> heures</span>
                            <span class="carte-formation-prix"><?= format_fcfa($f['tarif']) ?></span>
                        </div>
                        <a class="carte-formation-lien" href="<?= url('/inscription') ?>">S'inscrire <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if ($sessionsOuvertes !== []): ?>
            <div class="prochaines-sessions reveal">
                <h3><i class="fas fa-map-pin" aria-hidden="true"></i> Prochaines sessions ouvertes</h3>
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

<!-- ═══════════════════════════════════════════════════════════════
     Notre approche : split photo formateur + atouts
     ═══════════════════════════════════════════════════════════════ -->
<section class="section section-teinte" id="approche">
    <div class="conteneur accueil-split">
        <div class="accueil-split-media reveal">
            <img src="<?= asset('img/approche.jpg') ?>" alt="Formatrice accompagnant une apprenante sur ordinateur portable en salle de formation" loading="lazy" decoding="async" width="1100" height="1375">
            <div class="accueil-split-carte">
                <i class="fas fa-award" aria-hidden="true"></i>
                <div>
                    <strong>Attestation</strong>
                    <span>remise en fin de formation</span>
                </div>
            </div>
        </div>
        <div class="accueil-split-texte">
            <span class="section-etiquette">Notre approche</span>
            <h2 class="section-titre">Apprendre par la pratique, à taille humaine</h2>
            <p class="section-sous-titre">
                Ici, on ne reste pas spectateur : chaque notion travaillée se met
                en pratique immédiatement sur les machines du centre.
            </p>
            <ul class="atouts">
                <li><span class="atout-ic"><i class="fas fa-users" aria-hidden="true"></i></span> Groupes restreints et accompagnement de chaque apprenant</li>
                <li><span class="atout-ic"><i class="fas fa-chalkboard-user" aria-hidden="true"></i></span> Formateurs professionnels, en activité dans leur domaine</li>
                <li><span class="atout-ic"><i class="fas fa-computer" aria-hidden="true"></i></span> Pratique sur les machines du centre, en salles climatisées</li>
                <li><span class="atout-ic"><i class="fas fa-award" aria-hidden="true"></i></span> Attestation de fin de formation remise à chaque apprenant</li>
                <li><span class="atout-ic"><i class="fas fa-user-shield" aria-hidden="true"></i></span> Dossier suivi dans l'espace personnel : dates, horaires, salle, formateur</li>
            </ul>
            <div class="accueil-hero-cta accueil-cta-start">
                <a class="btn btn-primaire btn-grand" href="<?= url('/inscription') ?>">S'inscrire à une formation</a>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     Salles : split inversé photo + équipements réels
     ═══════════════════════════════════════════════════════════════ -->
<section class="section" id="salles">
    <div class="conteneur accueil-split accueil-split-inversee">
        <div class="accueil-split-texte">
            <span class="section-etiquette">Vos événements</span>
            <h2 class="section-titre">Des salles équipées pour vos événements</h2>
            <p class="section-sous-titre">
                Conférences, séminaires, réunions ou formations : déposez votre demande
                en ligne à l'heure près, le planning vous répond en direct.
            </p>
            <ul class="atouts">
                <li><span class="atout-ic"><i class="fas fa-video" aria-hidden="true"></i></span> Vidéoprojecteur, sonorisation, climatisation et wifi</li>
                <li><span class="atout-ic"><i class="fas fa-clock" aria-hidden="true"></i></span> Réservation à l'heure près, de 07h à 21h</li>
                <li><span class="atout-ic"><i class="fas fa-shuffle" aria-hidden="true"></i></span> Aucun chevauchement possible : un créneau, une salle, un usage</li>
                <li><span class="atout-ic"><i class="fas fa-bell" aria-hidden="true"></i></span> Suivi de la demande et confirmation notifiés par e-mail</li>
            </ul>
            <div class="salles-chips">
                <?php foreach ($salles as $s): ?>
                    <span class="chip"><i class="fas fa-door-open" aria-hidden="true"></i>&nbsp; <?= e($s['nom']) ?> · <?= (int) $s['capacite'] ?> places</span>
                <?php endforeach; ?>
            </div>
            <div class="accueil-hero-cta accueil-cta-start">
                <a class="btn btn-primaire btn-grand" href="<?= url('/reserver') ?>">Demander une réservation</a>
                <a class="btn btn-contour-bleu btn-grand" href="<?= url('/nos-salles') ?>">Découvrir nos salles</a>
            </div>
            <p class="accueil-note"><i class="fas fa-circle-info" aria-hidden="true"></i> Réponse du gestionnaire sous 24 h ouvrées.</p>
        </div>
        <div class="accueil-split-media reveal">
            <img src="<?= asset('img/salles.jpg') ?>" alt="Salle de réunion équipée avec écran de projection" loading="lazy" decoding="async" width="1400" height="1050">
            <div class="accueil-split-carte">
                <i class="fas fa-calendar-check" aria-hidden="true"></i>
                <div>
                    <strong>Planning en direct</strong>
                    <span>libre, en attente, réservé</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     Parcours apprenant : 4 étapes
     ═══════════════════════════════════════════════════════════════ -->
<section class="section section-teinte" id="etapes">
    <div class="conteneur">
        <div class="section-entete section-centree">
            <div>
                <span class="section-etiquette">Parcours apprenant</span>
                <h2 class="section-titre">Comment ça marche ?</h2>
                <p class="section-sous-titre">Quatre étapes simples pour rejoindre une formation. <a href="<?= url('/comment-ca-marche') ?>">Voir le détail des deux parcours</a>.</p>
            </div>
        </div>
        <ol class="etapes">
            <li class="etape reveal">
                <div class="etape-num">1</div>
                <h3>Choisissez votre formation</h3>
                <p>Parcourez le catalogue et sélectionnez la session qui vous convient parmi les sessions ouvertes.</p>
            </li>
            <li class="etape reveal">
                <div class="etape-num">2</div>
                <h3>Confirmez votre e-mail</h3>
                <p>Un clic sur le lien reçu dans votre boîte de réception valide votre adresse.</p>
            </li>
            <li class="etape reveal">
                <div class="etape-num">3</div>
                <h3>Recevez vos identifiants</h3>
                <p>Votre matricule d'apprenant et votre mot de passe vous sont envoyés par e-mail.</p>
            </li>
            <li class="etape reveal">
                <div class="etape-num">4</div>
                <h3>Suivez votre formation</h3>
                <p>Dates, horaires, salle et formateur : tout est visible dans votre espace personnel.</p>
            </li>
        </ol>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════
     Appel à l'action final
     ═══════════════════════════════════════════════════════════════ -->
<section class="cta-final">
    <div class="conteneur cta-final-inner">
        <div>
            <h2>Prêt à vous lancer ?</h2>
            <p>Rejoignez nos apprenants ou réservez l'une de nos salles pour votre prochain événement.</p>
        </div>
        <div class="accueil-hero-cta">
            <a class="btn btn-blanc btn-grand" href="<?= url('/inscription') ?>">S'inscrire à une formation</a>
            <a class="btn btn-contour-clair btn-grand" href="<?= url('/reserver') ?>">Réserver une salle</a>
        </div>
    </div>
</section>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "EducationalOrganization",
  "name": "Centre de formation",
  "alternateName": ["GCF", "Centre de formation ONG-GAS"],
  "description": "Centre de formation professionnelle dans le numérique : catalogue de formations, inscription en ligne et réservation de salles équipées.",
  "url": "https://planninggaspipt.com",
  "image": "https://planninggaspipt.com/public/assets/img/og-gcf.png",
  "telephone": "+229 01 46 46 66 56",
  "address": { "@type": "PostalAddress", "streetAddress": "Quartier Akpakpa", "addressLocality": "Cotonou", "addressCountry": "BJ" },
  "areaServed": "Cotonou"
}
</script>

<?php require dirname(__DIR__) . '/layout/bas_publique.php'; ?>
