<?php
namespace App\Controllers;

use App\Models\Reservation;
use App\Models\Salle;

final class PlanningController
{
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
            'salles'   => Salle::tous(),
            'grille'   => $grille,
            'lundi'    => $lundi,
            'dimanche' => $dimanche,
            'decalage' => $decalage,
        ]);
    }
}
