<?php
namespace App\Controllers;

use App\Models\Inscription;
use App\Models\Reservation;
use App\Models\Salle;
use App\Models\SessionFormation;

final class DashboardController
{
    public function index(): void
    {
        view('dashboard/index', [
            'statutsResa'     => Reservation::compteurParStatut('EN_ATTENTE'),
            'resasConfirmees' => Reservation::compteurParStatut('CONFIRMEE'),
            'resasOccupees'   => Reservation::compteurParStatut('OCCUPEE'),
            'nbSalles'        => count(Salle::tous()),
            'sessionsPlanifiees' => SessionFormation::compteurParStatut('PLANIFIEE'),
            'sessionsEnCours' => SessionFormation::compteurParStatut('EN_COURS'),
            'inscritsConfirmes' => Inscription::compteurParStatut('CONFIRMEE'),
            'inscriptionsAttente' => Inscription::compteurParStatut('EN_ATTENTE_VERIF'),
            'listeAttente'    => Inscription::compteurParStatut('LISTE_ATTENTE'),
            'resasAVenir'     => Reservation::aVenir(),
            'dernieresInscriptions' => Inscription::dernieres(),
        ]);
    }
}
