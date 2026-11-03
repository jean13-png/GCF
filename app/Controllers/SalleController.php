<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Flash;
use App\Core\Validator;
use App\Models\Salle;

final class SalleController
{
    public function index(): void
    {
        view('salles/index', ['salles' => Salle::tous()]);
    }

    public function form(?string $id = null): void
    {
        $salle = $id ? Salle::trouver((int) $id) : null;
        if ($id && $salle === null) {
            Flash::set('erreur', 'Cette salle n\'existe pas.');
            redirect('/salles');
        }
        view('salles/form', ['salle' => $salle]);
    }

    public function enregistrer(): void
    {
        if (!CSRF::verify()) {
            Flash::set('erreur', 'Session expirée, veuillez réessayer.');
            redirect('/salles');
        }
        $id = (int) ($_POST['id'] ?? 0);

        $v = new Validator($_POST);
        $v->required('nom', 'nom de la salle')->maxLength('nom', 'nom de la salle', 120)
          ->entier('capacite', 'capacité', 1, 2000)
          ->maxLength('equipements', 'équipements', 255)
          ->maxLength('localisation', 'localisation', 120);

        if ($v->echoue()) {
            Flash::set('erreur', $v->premierMessage());
            Flash::setOld($_POST);
            redirect($id ? "/salles/editer/$id" : '/salles/nouvelle');
        }

        if ($id > 0) {
            Salle::maj($id, $_POST);
            Auth::journaliser('SALLE_MODIFIEE', 'Salle #' . $id . ' · ' . $_POST['nom']);
            Flash::set('succes', 'Salle mise à jour.');
        } else {
            $id = Salle::creer($_POST);
            Auth::journaliser('SALLE_CREEE', 'Salle #' . $id . ' · ' . $_POST['nom']);
            Flash::set('succes', 'Salle créée.');
        }
        redirect('/salles');
    }

    public function maintenance(string $id): void
    {
        if (!CSRF::verify()) {
            Flash::set('erreur', 'Session expirée, veuillez réessayer.');
            redirect('/salles');
        }
        Salle::basculerMaintenance((int) $id);
        Auth::journaliser('SALLE_MAINTENANCE', 'Bascule maintenance salle #' . $id);
        Flash::set('succes', 'Statut de la salle mis à jour.');
        redirect('/salles');
    }
}
