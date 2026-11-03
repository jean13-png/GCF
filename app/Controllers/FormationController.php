<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Flash;
use App\Core\Validator;
use App\Models\Formation;

final class FormationController
{
    private const NIVEAUX = ['DEBUTANT', 'INTERMEDIAIRE', 'AVANCE'];

    public function index(): void
    {
        view('formations/index', ['formations' => Formation::tous()]);
    }

    public function form(?string $id = null): void
    {
        $formation = $id ? Formation::trouver((int) $id) : null;
        if ($id && $formation === null) {
            Flash::set('erreur', 'Cette formation n\'existe pas.');
            redirect('/formations');
        }
        view('formations/form', ['formation' => $formation, 'niveaux' => self::NIVEAUX]);
    }

    public function enregistrer(): void
    {
        if (!CSRF::verify()) {
            Flash::set('erreur', 'Session expirée, veuillez réessayer.');
            redirect('/formations');
        }
        $id = (int) ($_POST['id'] ?? 0);

        $v = new Validator($_POST);
        $v->required('titre', 'titre')->maxLength('titre', 'titre', 200)
          ->required('theme', 'thème')->maxLength('theme', 'thème', 120)
          ->entier('duree_heures', 'durée en heures', 1, 2000)
          ->dans('niveau', 'niveau', self::NIVEAUX);

        if ($v->echoue()) {
            Flash::set('erreur', $v->premierMessage());
            Flash::setOld($_POST);
            redirect($id ? "/formations/editer/$id" : '/formations/nouvelle');
        }

        if ($id > 0) {
            Formation::maj($id, $_POST);
            Auth::journaliser('FORMATION_MODIFIEE', 'Formation #' . $id . ' · ' . $_POST['titre']);
            Flash::set('succes', 'Formation mise à jour.');
        } else {
            $id = Formation::creer($_POST);
            Auth::journaliser('FORMATION_CREEE', 'Formation #' . $id . ' · ' . $_POST['titre']);
            Flash::set('succes', 'Formation ajoutée au catalogue.');
        }
        redirect('/formations');
    }

    public function basculerActif(string $id): void
    {
        if (!CSRF::verify()) {
            Flash::set('erreur', 'Session expirée, veuillez réessayer.');
            redirect('/formations');
        }
        Formation::basculerActif((int) $id);
        Auth::journaliser('FORMATION_STATUT', 'Activation/bascule formation #' . $id);
        Flash::set('succes', 'Visibilité de la formation mise à jour.');
        redirect('/formations');
    }
}
