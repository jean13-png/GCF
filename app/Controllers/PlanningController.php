<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Validator;
use App\Models\Reservation;
use App\Models\Salle;

final class PlanningController
{
    /** Heures d'ouverture du centre : créneaux affichés de 07h à 21h. */
    private const HEURE_OUVERTURE = 7;
    private const HEURE_FERMETURE = 21;

    public function index(): void
    {
        $decalage = (int) ($_GET['semaine'] ?? 0);
        $lundi = new \DateTime('monday this week');
        $lundi->modify(($decalage >= 0 ? '+' : '') . max(-52, min(52, $decalage)) . ' weeks');
        $dimanche = (clone $lundi)->modify('+6 days');

        $reservations = Reservation::entre($lundi->format('Y-m-d'), $dimanche->format('Y-m-d'));

        // groupement : par salle puis par jour de la semaine
        $grille = [];
        foreach (Salle::tous() as $salle) {
            foreach (range(0, 6) as $jour) {
                $grille[$salle['id']][$jour] = [];
            }
        }
        foreach ($reservations as $resa) {
            $numeroJour = (int) date('w', strtotime($resa['date_reservation']));
            $grille[$resa['salle_id']][($numeroJour + 6) % 7][] = $resa;
        }

        view('planning/index', [
            'salles'     => Salle::tous(),
            'grille'     => $grille,
            'lundi'      => $lundi,
            'dimanche'   => $dimanche,
            'decalage'   => $decalage,
            'utilisateur'=> Auth::user(),
        ]);
    }

    /**
     * Détail d'une journée d'une salle (JSON) : découpage de la journée en
     * créneaux d'une heure avec, pour chacun, son statut et son événement.
     * Utilisé par le modal du planning (clic sur une case du planning).
     */
    public function jour(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $salleId = (int) ($_GET['salle_id'] ?? 0);
        $date    = (string) ($_GET['date'] ?? '');
        $salle   = Salle::trouver($salleId);

        if ($salle === null || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            echo json_encode(['erreur' => 'Salle ou date invalide.']);
            return;
        }

        $occurrences = Reservation::duJour($salleId, $date);
        $priorites   = ['OCCUPEE' => 4, 'CONFIRMEE' => 3, 'EN_ATTENTE' => 2];

        $creneaux = [];
        for ($h = self::HEURE_OUVERTURE; $h < self::HEURE_FERMETURE; $h++) {
            $debut = sprintf('%02d:00', $h);
            $fin   = sprintf('%02d:00', $h + 1);

            $statut = 'LIBRE';
            $detail = null;
            $poids  = 0;
            foreach ($occurrences as $o) {
                if ($o['heure_debut'] < $fin && $o['heure_fin'] > $debut) {
                    $p = $priorites[$o['statut']] ?? 1;
                    if ($p > $poids) {
                        $poids  = $p;
                        $statut = $o['statut'];
                        $detail = $o;
                    }
                }
            }

            $creneaux[] = [
                'debut'     => $debut,
                'fin'       => $fin,
                'statut'    => $statut,
                'evenement' => $detail['evenement'] ?? null,
                'demandeur' => $detail['demandeur_nom'] ?? null,
                'resa_debut'=> $detail['heure_debut'] ?? null,
                'resa_fin'  => $detail['heure_fin'] ?? null,
            ];
        }

        echo json_encode([
            'salle' => [
                'id'          => (int) $salle['id'],
                'nom'         => $salle['nom'],
                'capacite'    => (int) $salle['capacite'],
                'maintenance' => (int) $salle['maintenance'] === 1,
            ],
            'date'       => $date,
            'occurrences'=> $occurrences,
            'creneaux'   => $creneaux,
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * L'administration programme un événement directement depuis le planning :
     * le créneau devient « Réservé » aussitôt, et toute autre demande sur ce
     * créneau (publique ou interne) sera refusée.
     */
    public function programmer(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!CSRF::verify()) {
            echo json_encode(['erreur' => 'Session expirée : rechargez la page puis réessayez.']);
            return;
        }

        $v = new Validator($_POST);
        $v->required('salle_id', 'salle')->dans('salle_id', 'salle', array_map('strval', array_column(Salle::tous(), 'id')))
          ->required('evenement', 'intitulé')->maxLength('evenement', 'intitulé', 200)
          ->required('demandeur_nom', 'organisateur')->maxLength('demandeur_nom', 'organisateur', 150)
          ->required('date_reservation', 'date')->date('date_reservation', 'date')
          ->required('heure_debut', 'heure de début')->heure('heure_debut', 'heure de début')
          ->required('heure_fin', 'heure de fin')->heure('heure_fin', 'heure de fin')
          ->apres('heure_debut', 'heure_fin', 'début', 'fin')
          ->entier('nb_personnes', 'nombre de personnes', 0, 2000);

        if ($v->echoue()) {
            echo json_encode(['erreur' => $v->premierMessage()]);
            return;
        }

        $salle = Salle::trouver((int) $_POST['salle_id']);
        if ($salle === null || (int) $salle['maintenance'] === 1) {
            echo json_encode(['erreur' => 'Cette salle n\'est pas disponible (maintenance ou inexistante).']);
            return;
        }

        if (Reservation::conflitExiste((int) $_POST['salle_id'], $_POST['date_reservation'], $_POST['heure_debut'], $_POST['heure_fin'], null)) {
            echo json_encode(['erreur' => 'Conflit d\'horaires : un événement occupe déjà ce créneau. Choisissez un créneau libre.']);
            return;
        }

        $id = Reservation::creerAdmin($_POST);
        Auth::journaliser('RESERVATION_PROGRAMMEE', 'Programmation planning #' . $id . ' · ' . $_POST['evenement'] . ' · ' . $_POST['date_reservation'] . ' ' . $_POST['heure_debut'] . '-' . $_POST['heure_fin']);

        echo json_encode(['ok' => true, 'id' => $id], JSON_UNESCAPED_UNICODE);
    }
}
