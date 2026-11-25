<?php
declare(strict_types=1);

/**
 * GCF · Envoi automatique des e-mails d'avis après réservation.
 *
 * À lancer toutes les 10 minutes (cron). Pour chaque réservation dont le
 * dernier jour est aujourd'hui, au statut confirmé, occupé ou terminé,
 * l'e-mail part à partir de 14h, ou à l'heure de fin si elle est plus tard.
 *
 * Usage :
 *   php bin/envoyer_avis.php              envoi réel
 *   php bin/envoyer_avis.php --simulation  affiche ce qui serait envoyé, sans rien écrire
 */

if (PHP_SAPI !== 'cli') {
    exit("Ce script ne s'exécute qu'en ligne de commande.\n");
}

require dirname(__DIR__) . '/app/Core/Autoloader.php';
require dirname(__DIR__) . '/app/Core/Helpers.php';

use App\Core\Mailer;
use App\Models\Avis;

$simulation = in_array('--simulation', $argv, true);

// Garde-fous : rien ne part tant que la date d'activation et l'adresse publique ne sont pas renseignées.
$depuis = config('avis.depuis');
if ($depuis === null || $depuis === '') {
    echo "Avis désactivés : avis.depuis n'est pas renseigné dans la configuration.\n";
    exit(0);
}
$baseUrl = rtrim((string) config('app.url', ''), '/');
if ($baseUrl === '') {
    fwrite(STDERR, "Adresse publique absente (app.url) : aucun e-mail envoyé.\n");
    exit(1);
}

// Une seule exécution à la fois.
$verrou = fopen(sys_get_temp_dir() . '/gcf_envoyer_avis.lock', 'c');
if ($verrou === false || !flock($verrou, LOCK_EX | LOCK_NB)) {
    echo "Une exécution est déjà en cours.\n";
    exit(0);
}

$tz = new DateTimeZone((string) config('app.fuseau', 'Africa/Porto-Novo'));
$maintenant = new DateTimeImmutable('now', $tz);
$aujourdhui = $maintenant->format('Y-m-d');

$trouves = 0;
$envoyes = 0;
$echecs = 0;

foreach (Avis::candidates((string) $depuis, $aujourdhui) as $r) {
    // Déclenchement : 14h, ou l'heure de fin si elle est plus tardive.
    $heure = max('14:00:00', (string) $r['heure_fin']);
    $declenchement = new DateTimeImmutable($aujourdhui . ' ' . $heure, $tz);
    if ($maintenant < $declenchement) {
        continue;
    }
    $trouves++;

    if ($simulation) {
        echo "[simulation] à envoyer : {$r['demandeur_email']} · {$r['evenement']} · {$r['salle_nom']}\n";
        continue;
    }

    // Lien personnel : créé une seule fois, repris si un envoi précédent a échoué.
    if ($r['avis_id'] === null) {
        $token = bin2hex(random_bytes(32));
        $avisId = Avis::creer((int) $r['id'], $token, Avis::expiration());
    } else {
        $token = (string) $r['token'];
        $avisId = (int) $r['avis_id'];
    }

    $sujet = 'Votre avis sur votre réservation du ' . format_date_fr($r['date_reservation']);
    try {
        Mailer::envoyer($r['demandeur_email'], $sujet, Avis::corpsDemande($r, $baseUrl . '/avis/' . $token));
    } catch (Throwable $ex) {
        $echecs++;
        fwrite(STDERR, "Réservation #{$r['id']} : échec d'envoi (" . $ex->getMessage() . ")\n");
        continue;
    }

    if (Avis::mailNonEchec($r['demandeur_email'], $sujet)) {
        Avis::marquerEnvoye($avisId, Avis::maintenant());
        $envoyes++;
        echo "Envoyé : réservation #{$r['id']} → {$r['demandeur_email']}\n";
    } else {
        $echecs++;
        fwrite(STDERR, "Réservation #{$r['id']} : l'envoi a échoué, nouvelle tentative au prochain passage.\n");
    }
}

echo sprintf("[%s] éligibles : %d · envoyés : %d · échecs : %d%s\n",
    $maintenant->format('Y-m-d H:i'), $trouves, $envoyes, $echecs, $simulation ? ' (simulation)' : '');
