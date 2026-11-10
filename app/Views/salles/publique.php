<?php $titrePage = 'Nos salles'; $descPage = "Les salles du centre de formation à Cotonou : capacités, équipements, tarifs horaires et disponibilités en temps réel pour préparer votre réservation."; ?>
<?php require dirname(__DIR__) . '/layout/haut_publique.php'; ?>

<div class="conteneur page-publique page-publique-large">

    <div class="entete-page-publique">
        <h1>Nos salles</h1>
        <p>
            <?= count($salles) ?> salles équipées pour vos formations, conférences, séminaires
            et réunions. Toutes se demandent en ligne, à l'heure près, sans chevauchement possible.
        </p>
    </div>

    <?php
    // Une photo par salle, reconnue depuis son nom ; les salles sans indice
    // reçoivent une image de repli pas encore utilisee (jamais de doublon).
    $imagesRotation = ['salle-formation.jpg', 'salle-conference.jpg', 'salle-reunion.jpg', 'salle-informatique.jpg'];
    $imagesChoisies = [];
    foreach ($salles as $i => $s) {
        $nom = $s['nom'];
        if (mb_stripos($nom, 'conférence') !== false || mb_stripos($nom, 'conference') !== false) {
            $imagesChoisies[] = 'salle-conference.jpg';
        } elseif (mb_stripos($nom, 'laboratoire') !== false || mb_stripos($nom, 'informatique') !== false) {
            $imagesChoisies[] = ($i % 2 === 0) ? 'salle-informatique.jpg' : 'salle-informatique-2.jpg';
        } elseif (mb_stripos($nom, 'réunion') !== false || mb_stripos($nom, 'reunion') !== false) {
            $imagesChoisies[] = 'salle-reunion.jpg';
        } else {
            $repli = 'salle-formation.jpg';
            foreach ($imagesRotation as $img) {
                if (!in_array($img, $imagesChoisies, true)) { $repli = $img; break; }
            }
            $imagesChoisies[] = $repli;
        }
    }
    ?>

    <div class="cartes-salles">
        <?php foreach ($salles as $i => $s): ?>
            <?php
            $image = $imagesChoisies[$i] ?? 'salle-formation.jpg';
            $altMap = [
                'salle-conference.jpg'    => 'Salle de conférence avec chaises et table en bois',
                'salle-formation.jpg'     => 'Salle de formation spacieuse avec tables et chaises',
                'salle-informatique.jpg'  => 'Laboratoire informatique avec rangées d\'ordinateurs',
                'salle-informatique-2.jpg'=> 'Salle informatique avec postes de travail',
                'salle-reunion.jpg'       => 'Salle de réunion avec écran et table',
            ];
            $equipements = array_filter(array_map('trim', explode(',', (string) $s['equipements'])));
            ?>
            <article class="carte-salle reveal">
                <div class="carte-salle-visuel">
                    <img src="<?= asset('img/' . $image) ?>" alt="<?= e($altMap[$image] ?? 'Salle du centre') ?>" loading="lazy" decoding="async" width="900" height="675">
                </div>
                <div class="carte-salle-corps">
                    <div class="carte-salle-entete">
                        <h2><?= e($s['nom']) ?></h2>
                        <span class="carte-salle-capacite">
                            <i class="fas fa-users" aria-hidden="true"></i>
                            <?= (int) $s['capacite'] ?> places
                        </span>
                    </div>
                    <?php if ($equipements !== []): ?>
                        <div class="carte-salle-equipements">
                            <?php foreach ($equipements as $eq): ?>
                                <span class="chip"><i class="fas fa-check" aria-hidden="true"></i>&nbsp; <?= e(mb_ucfirst($eq)) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <ul class="carte-salle-details">
                        <li>
                            <i class="fas fa-location-dot" aria-hidden="true"></i>
                            <?= e($s['localisation'] ?: 'Centre de formation') ?>
                        </li>
                        <?php if (!empty($s['tarif_horaire'])): ?>
                            <li>
                                <i class="fas fa-clock" aria-hidden="true"></i>
                                <?= format_fcfa($s['tarif_horaire']) ?> par heure
                            </li>
                        <?php endif; ?>
                    </ul>
                    <a class="btn btn-primaire carte-salle-cta" href="<?= url('/reserver') ?>">
                        Demander cette salle <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="encart-info" style="margin-top:34px;">
        <i class="fas fa-circle-info" aria-hidden="true"></i>
        Une demande n'est jamais en conflit avec une autre : si le créneau est déjà pris, la plateforme
        vous propose immédiatement les créneaux libres du même jour et les prochaines dates disponibles.
        Réponse du gestionnaire sous 24 h ouvrées.
    </div>

</div>

<?php require dirname(__DIR__) . '/layout/bas_publique.php'; ?>
