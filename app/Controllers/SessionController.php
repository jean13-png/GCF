<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Flash;
use App\Core\Validator;
use App\Models\Formation;
use App\Models\Inscription;
use App\Models\Salle;
use App\Models\SessionFormation;

final class SessionController
{
    public function index(): void
    {
        view('sessions/index', ['sessions' => SessionFormation::toutes()]);
    }

    public function form(?string $id = null): void
    {
        $session = $id ? SessionFormation::trouver((int) $id) : null;
        if ($id && $session === null) {
            Flash::set('erreur', 'Cette session n\'existe pas.');
            redirect('/sessions');
        }
        view('sessions/form', [
            'session'    => $session,
            'formations' => Formation::tous(true),
            'salles'     => Salle::actives(),
        ]);
    }

    public function enregistrer(): void
    {
        if (!CSRF::verify()) {
            Flash::set('erreur', 'Session expirée, veuillez réessayer.');
            redirect('/sessions');
        }
        $id = (int) ($_POST['id'] ?? 0);

        $v = new Validator($_POST);
        $v->required('formation_id', 'formation')->dans('formation_id', 'formation', array_map('strval', array_column(Formation::tous(), 'id')))
          ->required('salle_id', 'salle')->dans('salle_id', 'salle', array_map('strval', array_column(Salle::tous(), 'id')))
          ->required('date_debut', 'date de début')->date('date_debut', 'date de début')
          ->required('date_fin', 'date de fin')->date('date_fin', 'date de fin')
          ->required('heure_debut', 'heure de début')->heure('heure_debut', 'heure de début')
          ->required('heure_fin', 'heure de fin')->heure('heure_fin', 'heure de fin')
          ->apres('heure_debut', 'heure_fin', 'début', 'fin')
          ->entier('places', 'nombre de places', 1, 500);

        $_POST['date_fin'] = $_POST['date_fin'] ?? '';
        if ($_POST['date_debut'] !== '' && $_POST['date_fin'] !== '' && $_POST['date_fin'] < $_POST['date_debut']) {
            $v->erreurs()['date_fin'] = '';
            Flash::set('erreur', 'La date de fin doit suivre la date de début.');
            Flash::setOld($_POST);
            redirect($id ? "/sessions/editer/$id" : '/sessions/nouvelle');
        }

        if ($v->echoue()) {
            Flash::set('erreur', $v->premierMessage());
            Flash::setOld($_POST);
            redirect($id ? "/sessions/editer/$id" : '/sessions/nouvelle');
        }

        if ($id > 0) {
            SessionFormation::maj($id, $_POST);
            Auth::journaliser('SESSION_MODIFIEE', 'Session #' . $id);
            Flash::set('succes', 'Session mise à jour.');
        } else {
            $id = SessionFormation::creer($_POST);
            Auth::journaliser('SESSION_CREEE', 'Session #' . $id . ' · salle réservée automatiquement');
            Flash::set('succes', 'Session créée : la salle a été réservée automatiquement dans le planning pour chaque jour de la formation.');
        }
        redirect('/sessions');
    }

    public function changerStatut(string $id): void
    {
        if (!CSRF::verify()) {
            Flash::set('erreur', 'Session expirée, veuillez réessayer.');
            redirect('/sessions');
        }
        $statuts = ['PLANIFIEE', 'EN_COURS', 'TERMINEE', 'ANNULEE'];
        $statut = $_POST['statut'] ?? '';
        if (!in_array($statut, $statuts, true)) {
            Flash::set('erreur', 'Statut inconnu.');
            redirect('/sessions');
        }
        SessionFormation::changerStatut((int) $id, $statut);
        Auth::journaliser('SESSION_STATUT', 'Session #' . $id . ' → ' . $statut);
        Flash::set('succes', 'Statut de la session mis à jour.');
        redirect('/sessions');
    }

    public function inscrits(string $id): void
    {
        $session = SessionFormation::trouver((int) $id);
        if ($session === null) {
            Flash::set('erreur', 'Cette session n\'existe pas.');
            redirect('/sessions');
        }
        view('sessions/inscrits', [
            'session'      => $session,
            'inscriptions' => Inscription::parSession((int) $id),
        ]);
    }
}
