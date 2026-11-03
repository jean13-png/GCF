<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Flash;
use App\Core\Validator;

final class AuthController
{
    public function form(): void
    {
        if (Auth::check()) {
            $this->redirigerSelonRole();
        }
        Flash::viderOld();
        view('auth/login');
    }

    public function connexion(): void
    {
        if (!CSRF::verify()) {
            Flash::set('erreur', 'Session expirée, veuillez réessayer.');
            redirect('/login');
        }
        $v = new Validator($_POST);
        $v->required('identifiant', 'identifiant')->required('password', 'mot de passe');

        if ($v->echoue()) {
            Flash::set('erreur', $v->premierMessage());
            Flash::setOld($_POST);
            redirect('/login');
        }

        $resultat = Auth::attempt($_POST['identifiant'], $_POST['password']);
        if ($resultat !== true) {
            Flash::set('erreur', $resultat);
            Flash::setOld(['identifiant' => $_POST['identifiant']]);
            redirect('/login');
        }

        Auth::journaliser('CONNEXION', 'Connexion réussie');
        Flash::set('succes', 'Bienvenue ' . Auth::user()['prenom'] . ' !');
        $this->redirigerSelonRole();
    }

    public function deconnexion(): void
    {
        if (CSRF::verify()) {
            Auth::journaliser('DECONNEXION', 'Déconnexion');
            Auth::logout();
        }
        redirect('/login');
    }

    private function redirigerSelonRole(): never
    {
        redirect(Auth::role() === 'APPRENANT' ? '/mon-espace' : '/dashboard');
    }
}
