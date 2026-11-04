<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Flash;
use App\Core\Mailer;
use App\Core\Validator;
use App\Models\Inscription;
use App\Models\SessionFormation;
use App\Models\User;

/**
 * Inscription en ligne : dépôt, vérification e-mail, création du compte
 * apprenant et envoi des identifiants (matricule + mot de passe).
 */
final class InscriptionController
{
    /** Étape 1 : formulaire public avec la liste déroulante des sessions ouvertes. */
    public function publique(): void
    {
        view('inscriptions/publique', ['sessions' => SessionFormation::ouvertes()]);
    }

    /** Étape 2 : enregistrement de la demande + e-mail de vérification. */
    public function soumettre(): void
    {
        if (!CSRF::verify()) {
            Flash::set('erreur', 'Session expirée, veuillez réessayer.');
            redirect('/');
        }

        $v = new Validator($_POST);
        $v->required('session_id', 'session de formation')
          ->required('nom', 'nom')->maxLength('nom', 'nom', 100)
          ->required('prenom', 'prénom')->maxLength('prenom', 'prénom', 100)
          ->required('email', 'adresse e-mail')->email('email', 'adresse e-mail')
          ->maxLength('telephone', 'téléphone', 30)
          ->maxLength('niveau_actuel', 'niveau actuel', 100);

        if ($v->echoue()) {
            Flash::set('erreur', $v->premierMessage());
            Flash::setOld($_POST);
            redirect('/inscription');
        }

        $sessionId = (int) $_POST['session_id'];
        $sessionsOuvertes = array_filter(SessionFormation::ouvertes(), static fn ($s): bool => (int) $s['id'] === $sessionId);
        if ($sessionsOuvertes === []) {
            Flash::set('erreur', 'Cette session n\'est plus ouverte aux inscriptions.');
            Flash::setOld($_POST);
            redirect('/inscription');
        }
        $session = reset($sessionsOuvertes);

        if (Inscription::doublonExiste($sessionId, $_POST['email'])) {
            Flash::set('erreur', 'Une inscription avec cet e-mail existe déjà pour cette session.');
            Flash::setOld($_POST);
            redirect('/inscription');
        }

        $token = bin2hex(random_bytes(32));
        $donnees = $_POST;
        $donnees['session_formation_id'] = $sessionId;
        Inscription::creer($donnees, $token);
        Auth::journaliser('INSCRIPTION_DEPOSEE', 'Session #' . $sessionId . ' · ' . $_POST['email']);

        $lien = url('/inscription/verifier?token=' . $token);
        Mailer::envoyer(
            $_POST['email'],
            'Vérifiez votre adresse e-mail pour finaliser votre inscription',
            '<p>Bonjour ' . e($_POST['prenom']) . ',</p>'
            . '<p>Nous avons bien reçu votre inscription à la formation :</p>'
            . '<p><strong>' . e($session['formation_titre']) . '</strong><br>'
            . 'Du ' . e(format_date_fr($session['date_debut'], false)) . ' au ' . e(format_date_fr($session['date_fin'], false))
            . ' · ' . e(format_heure($session['heure_debut'])) . ' à ' . e(format_heure($session['heure_fin'])) . '</p>'
            . '<p>Cliquez sur le bouton ci-dessous pour vérifier votre adresse e-mail. '
            . 'Vos identifiants d\'apprenant (matricule et mot de passe) vous seront alors envoyés par e-mail.</p>'
            . Mailer::bouton($lien, 'Vérifier mon adresse e-mail')
            . '<p>Si vous n\'êtes pas à l\'origine de cette inscription, ignorez simplement cet e-mail.</p>'
        );

        Flash::set('succes', 'Votre demande est enregistrée ! Un e-mail de vérification vient de vous être envoyé : cliquez sur le lien qu\'il contient pour finaliser votre inscription.');
        redirect('/inscription');
    }

    /** Étape 3 : vérification, création du compte apprenant et envoi des identifiants. */
    public function verifier(): void
    {
        $token = trim((string) ($_GET['token'] ?? ''));
        if ($token === '' || strlen($token) > 64) {
            view('inscriptions/verifier', ['erreur' => 'Lien invalide : aucun jeton de vérification fourni.']);
            return;
        }

        $inscription = Inscription::trouverParToken($token);
        if ($inscription === null) {
            view('inscriptions/verifier', ['erreur' => 'Ce lien de vérification est invalide ou a déjà été utilisé.']);
            return;
        }

        // Création du compte apprenant : matricule + mot de passe aléatoire fort
        $motDePasse = self::motDePasseAleatoire();
        $compte = User::creerApprenant($inscription['nom'], $inscription['prenom'], $inscription['email'], $inscription['telephone'], $motDePasse);
        $statut = Inscription::confirmer((int) $inscription['id'], (int) $compte['id']);
        Auth::journaliser('COMPTE_APPRENANT_CREE', $compte['matricule'] . ' · ' . $compte['email']);

        $session = SessionFormation::trouver((int) $inscription['session_formation_id']);
        $enListeAttente = $statut === 'LISTE_ATTENTE';

        $message = '<p>Bonjour ' . e($compte['prenom']) . ',</p>'
            . '<p>Votre adresse e-mail est vérifiée : votre inscription à la formation <strong>' . e($session['formation_titre']) . '</strong> '
            . ($enListeAttente ? 'est placée en <strong>liste d\'attente</strong> (la session est complète) et vous serez contacté dès qu\'une place se libère.' : 'est <strong>confirmée</strong>.') . '</p>'
            . '<p>Voici vos identifiants pour accéder à votre espace apprenant :</p>'
            . '<p style="background:#f4f6fb;border:1px solid #e3e8f4;border-radius:10px;padding:14px 18px;font-size:15px;">'
            . '<strong>Identifiant (e-mail) :</strong> ' . e($compte['email']) . '<br>'
            . '<strong>Matricule :</strong> ' . e($compte['matricule']) . '<br>'
            . '<strong>Mot de passe :</strong> ' . e($motDePasse) . '</p>'
            . '<p>Vous pouvez vous connecter avec votre e-mail <em>ou</em> votre matricule. '
            . 'Conservez précieusement ce message et changez votre mot de passe depuis votre espace.</p>'
            . Mailer::bouton(url('/login'), 'Accéder à mon espace');
        Mailer::envoyer($compte['email'], 'Vos identifiants apprenant · ' . $compte['matricule'], $message);

        view('inscriptions/verifier', [
            'erreur'    => null,
            'compte'    => $compte,
            'session'   => $session,
            'enListeAttente' => $enListeAttente,
        ]);
    }

    /** Vue gestion : inscriptions reçues, à confirmer et à programmer. */
    public function index(): void
    {
        view('inscriptions/index', ['inscriptions' => Inscription::toutes($_GET)]);
    }

    public function annuler(string $id): void
    {
        if (!CSRF::verify()) {
            Flash::set('erreur', 'Session expirée, veuillez réessayer.');
            redirect('/inscriptions');
        }
        $inscription = Inscription::trouver((int) $id);
        if ($inscription !== null) {
            Inscription::annuler((int) $id);
            Auth::journaliser('INSCRIPTION_ANNULEE', 'Inscription #' . $id);
            Flash::set('succes', 'Inscription annulée.');
        }
        redirect('/inscriptions');
    }

    /** Mot de passe provisoire lisible et sûr : 12 caractères, majuscules, chiffres, symboles. */
    private static function motDePasseAleatoire(): string
    {
        $consonnes = 'bcdghjklmnpqrstvwxz';
        $voyelles = 'aeiou';
        $symboles = '!@#$%&*?-+';
        $mdp = '';
        for ($i = 0; $i < 4; $i++) {
            $mdp .= $consonnes[random_int(0, strlen($consonnes) - 1)]
                . $voyelles[random_int(0, strlen($voyelles) - 1)];
        }
        $mdp .= random_int(10, 99)
            . strtoupper($consonnes[random_int(0, strlen($consonnes) - 1)])
            . $symboles[random_int(0, strlen($symboles) - 1)];
        return str_shuffle($mdp);
    }
}
