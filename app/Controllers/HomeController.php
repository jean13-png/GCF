<?php
namespace App\Controllers;

use App\Models\Formation;
use App\Models\Salle;
use App\Models\SessionFormation;
use App\Models\User;

/**
 * Page d'accueil publique : présentation du centre, formations, salles,
 * chiffres clés et appels à l'action (inscription / demande de salle).
 */
final class HomeController
{
    public function accueil(): void
    {
        view('home/index', [
            'salles'           => Salle::actives(),
            'formations'       => array_slice(Formation::tous(true), 0, 6),
            'sessionsOuvertes' => array_slice(SessionFormation::ouvertes(), 0, 3),
            'stats'            => [
                'salles'     => count(Salle::actives()),
                'formations' => count(Formation::tous(true)),
                'apprenants' => User::compterApprenants(),
                'sessions'   => count(SessionFormation::ouvertes()),
            ],
        ]);
    }

    /**
     * Page publique « Nos salles » : présentation des salles actives,
     * leurs équipements et leur tarif horaire, avec accès à la demande.
     */
    public function salles(): void
    {
        view('salles/publique', [
            'salles' => Salle::actives(),
        ]);
    }

    /**
     * Page publique « Comment ça marche » : les deux parcours,
     * inscription à une formation et demande de réservation de salle.
     */
    public function commentCaMarche(): void
    {
        view('home/comment-ca-marche', [
            'nbSalles' => Salle::compterActives(),
        ]);
    }
}
