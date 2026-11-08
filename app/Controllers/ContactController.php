<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Flash;
use App\Core\Mailer;
use App\Core\Validator;
use App\Models\MessageContact;

/**
 * Page Contact du site public et gestion des messages dans l'espace admin.
 */
final class ContactController
{
    /**
     * Formulaire public de contact.
     */
    public function publique(): void
    {
        view('contact/publique');
    }

    /**
     * Enregistrement d'un message : validation, anti-spam, notification
     * des administrateurs par e-mail, puis retour au formulaire.
     */
    public function soumettre(): void
    {
        if (!CSRF::verify()) {
            Flash::set('erreur', 'Session expirée, merci de renvoyer votre message.');
            redirect('/contact');
        }

        // Piège anti-robots : champ caché, un humain ne le remplit jamais
        if (trim($_POST['site_web'] ?? '') !== '') {
            Flash::set('succes', 'Votre message a bien été envoyé.');
            redirect('/contact');
        }

        $v = new Validator($_POST);
        $v->required('nom', 'Le nom')
            ->maxLength('nom', 'Le nom', 120)
            ->required('email', 'L\'adresse e-mail')
            ->maxLength('email', 'L\'adresse e-mail', 190)
            ->email('email', 'L\'adresse e-mail')
            ->maxLength('telephone', 'Le numéro de téléphone', 30)
            ->required('sujet', 'Le sujet')
            ->maxLength('sujet', 'Le sujet', 190)
            ->required('message', 'Le message')
            ->minLongueur('message', 'Le message', 10);

        if ($v->echoue()) {
            Flash::set('erreur', implode('<br>', $v->erreurs()));
            redirect('/contact');
        }

        $id = MessageContact::creer([
            'nom'       => trim($_POST['nom']),
            'email'     => trim($_POST['email']),
            'telephone' => trim($_POST['telephone'] ?? ''),
            'sujet'     => trim($_POST['sujet']),
            'message'   => trim($_POST['message']),
        ]);

        Auth::journaliser('CONTACT_MESSAGE', 'Message #' . $id . ' · ' . $_POST['sujet']);

        $this->notifierAdministrateurs([
            'id'        => $id,
            'nom'       => trim($_POST['nom']),
            'email'     => trim($_POST['email']),
            'telephone' => trim($_POST['telephone'] ?? ''),
            'sujet'     => trim($_POST['sujet']),
            'message'   => trim($_POST['message']),
        ]);

        Flash::set('succes', 'Votre message a bien été envoyé. L\'équipe du centre vous répondra dans les meilleurs délais.');
        redirect('/contact');
    }

    /**
     * Liste des messages dans l'espace admin (les non traités en premier).
     */
    public function liste(): void
    {
        view('dashboard/messages', [
            'messages'    => MessageContact::tous(),
            'nbNouveaux'  => MessageContact::compterNouveaux(),
        ]);
    }

    /**
     * Marque un message comme traité.
     */
    public function traiter(int $id): void
    {
        if (!CSRF::verify()) {
            Flash::set('erreur', 'Session expirée, merci de réessayer.');
            redirect('/messages');
        }
        $message = MessageContact::trouver($id);
        if ($message === null) {
            Flash::set('erreur', 'Ce message n\'existe pas.');
            redirect('/messages');
        }
        MessageContact::marquerTraite($id);
        Auth::journaliser('CONTACT_TRAITE', 'Message #' . $id . ' · ' . $message['sujet']);
        Flash::set('succes', 'Message de ' . $message['nom'] . ' marqué comme traité.');
        redirect('/messages');
    }

    /**
     * Notification e-mail des administrateurs à chaque nouveau message.
     */
    private function notifierAdministrateurs(array $m): void
    {
        $destinataires = MessageContact::emailsAdministrateurs();
        if ($destinataires === []) {
            return;
        }

        $lignes = '<p style="margin:0 0 10px;">Un nouveau message a été envoyé depuis la page Contact du site :</p>'
            . '<table style="width:100%;border-collapse:collapse;font-size:14px;">'
            . '<tr><td style="padding:6px 10px;color:#66708a;width:120px;">Nom</td><td style="padding:6px 10px;"><strong>' . e($m['nom']) . '</strong></td></tr>'
            . '<tr><td style="padding:6px 10px;color:#66708a;">E-mail</td><td style="padding:6px 10px;">' . e($m['email']) . '</td></tr>'
            . '<tr><td style="padding:6px 10px;color:#66708a;">Téléphone</td><td style="padding:6px 10px;">' . e($m['telephone'] ?: 'non renseigné') . '</td></tr>'
            . '<tr><td style="padding:6px 10px;color:#66708a;">Sujet</td><td style="padding:6px 10px;"><strong>' . e($m['sujet']) . '</strong></td></tr>'
            . '</table>'
            . '<div style="background:#f8f9fc;border-left:3px solid #1c2b6b;border-radius:8px;padding:14px 16px;margin:14px 0;white-space:pre-wrap;">' . e($m['message']) . '</div>'
            . Mailer::bouton(url('/messages'), 'Voir le message dans l\'espace admin');

        foreach ($destinataires as $email) {
            Mailer::envoyer($email, 'Nouveau message de contact · ' . $m['sujet'], $lignes);
        }
    }
}
