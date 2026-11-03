<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Flash;
use App\Core\Validator;
use App\Models\SessionFormation;
use App\Models\User;

/**
 * Espace apprenant : mes formations, mon planning, mes documents à venir,
 * et gestion de mon profil (coordonnées, mot de passe).
 */
final class ApprenantController
{
    public function dashboard(): void
    {
        $sessions = SessionFormation::pourApprenant(Auth::id());
        $aVenir = array_values(array_filter($sessions, static fn ($s): bool => $s['date_debut'] >= date('Y-m-d')));
        $enCours = array_values(array_filter($sessions, static fn ($s): bool => $s['statut'] === 'EN_COURS'));
        $terminees = array_values(array_filter($sessions, static fn ($s): bool => $s['statut'] === 'TERMINEE'));

        view('apprenant/dashboard', [
            'aVenir'    => $aVenir,
            'enCours'   => $enCours,
            'terminees' => $terminees,
            'sessionsOuvertes' => SessionFormation::ouvertes(),
        ]);
    }

    public function profil(): void
    {
        view('apprenant/profil', ['user' => Auth::user()]);
    }

    public function enregistrerProfil(): void
    {
        if (!CSRF::verify()) {
            Flash::set('erreur', 'Session expirée, veuillez réessayer.');
            redirect('/mon-profil');
        }
        $v = new Validator($_POST);
        $v->maxLength('telephone', 'téléphone', 30);

        $changerMdp = trim((string) ($_POST['password_nouveau'] ?? '')) !== '';
        if ($changerMdp) {
            $v->required('password_actuel', 'mot de passe actuel')
              ->minLongueur('password_nouveau', 'nouveau mot de passe', 8)
              ->required('password_confirmation', 'confirmation');
        }

        if ($v->echoue()) {
            Flash::set('erreur', $v->premierMessage());
            redirect('/mon-profil');
        }

        if ($changerMdp) {
            $user = User::trouverParId(Auth::id());
            if (!password_verify($_POST['password_actuel'], $user['password_hash'])) {
                Flash::set('erreur', 'Le mot de passe actuel est incorrect.');
                redirect('/mon-profil');
            }
            if ($_POST['password_nouveau'] !== $_POST['password_confirmation']) {
                Flash::set('erreur', 'Le nouveau mot de passe et sa confirmation ne correspondent pas.');
                redirect('/mon-profil');
            }
            User::changerMotDePasse(Auth::id(), $_POST['password_nouveau']);
            Auth::journaliser('MOT_DE_PASSE_MODIFIE', 'Changement depuis le profil');
        }

        User::majProfil(Auth::id(), trim((string) ($_POST['telephone'] ?? '')));
        Flash::set('succes', $changerMdp ? 'Profil et mot de passe mis à jour.' : 'Profil mis à jour.');
        redirect('/mon-profil');
    }
}
