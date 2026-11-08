<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Flash;
use App\Core\Mailer;
use App\Core\Validator;
use App\Models\Reservation;
use App\Models\Salle;
use App\Models\User;

final class ReservationController
{
    private const TRANSITIONS = [
        'valider'  => 'CONFIRMEE',
        'refuser'  => 'REFUSEE',
        'occuper'  => 'OCCUPEE',
        'liberer'  => 'TERMINEE',
        'annuler'  => 'ANNULEE',
    ];

    public function index(): void
    {
        view('reservations/index', [
            'reservations' => Reservation::tous($_GET),
            'salles'       => Salle::tous(),
        ]);
    }

    /** Formulaire interne (personnel du centre connecté). */
    public function form(): void
    {
        view('reservations/form', [
            'salles' => Salle::actives(),
            'dispo'  => self::consommerPropositions(),
        ]);
    }

    /** Formulaire public de demande de réservation (page d'accueil → Réserver une salle). */
    public function formPublique(): void
    {
        view('reservations/publique', [
            'salles' => Salle::actives(),
            'dispo'  => self::consommerPropositions(),
        ]);
    }

    /** Récupère (une seule fois) les propositions de disponibilité nées d'un refus de conflit. */
    private static function consommerPropositions(): ?array
    {
        $dispo = $_SESSION['dispo_conflit'] ?? null;
        unset($_SESSION['dispo_conflit']);
        return $dispo;
    }

    public function enregistrer(): void
    {
        $this->traiterDemande('/reservations');
    }

    public function enregistrerPublique(): void
    {
        $this->traiterDemande('/reserver');
    }

    /**
     * Traite une demande de réservation (dépôt interne ou public).
     * La demande naît « En attente » : la salle n'est acquise qu'après
     * validation par le gestionnaire.
     */
    private function traiterDemande(string $retour): void
    {
        if (!CSRF::verify()) {
            Flash::set('erreur', 'Session expirée, veuillez réessayer.');
            redirect($retour);
        }
        $v = new Validator($_POST);
        $v->required('salle_id', 'salle')->dans('salle_id', 'salle', array_map('strval', array_column(Salle::tous(), 'id')))
          ->required('demandeur_nom', 'nom du demandeur')->maxLength('demandeur_nom', 'nom du demandeur', 150)
          ->required('evenement', 'intitulé de l\'événement')->maxLength('evenement', 'intitulé de l\'événement', 200)
          ->required('date_reservation', 'date')->date('date_reservation', 'date')
          ->required('heure_debut', 'heure de début')->heure('heure_debut', 'heure de début')
          ->required('heure_fin', 'heure de fin')->heure('heure_fin', 'heure de fin')
          ->apres('heure_debut', 'heure_fin', 'début', 'fin')
          ->required('demandeur_email', 'e-mail')->email('demandeur_email', 'e-mail')
          ->entier('nb_personnes', 'nombre de personnes', 0, 2000);

        if ($v->echoue()) {
            Flash::set('erreur', $v->premierMessage());
            Flash::setOld($_POST);
            redirect($retour);
        }

        $salle = Salle::trouver((int) $_POST['salle_id']);
        if ($salle === null || (int) $salle['maintenance'] === 1) {
            Flash::set('erreur', 'Cette salle n\'est pas disponible (maintenance ou inexistante).');
            Flash::setOld($_POST);
            redirect($retour);
        }

        if (Reservation::conflitExiste((int) $_POST['salle_id'], $_POST['date_reservation'], $_POST['heure_debut'], $_POST['heure_fin'], null)) {
            // Refus + propositions concrètes : créneaux libres du jour et prochaines dates disponibles.
            $_SESSION['dispo_conflit'] = [
                'salle'        => $salle['nom'],
                'date'         => $_POST['date_reservation'],
                'debut'        => $_POST['heure_debut'],
                'fin'          => $_POST['heure_fin'],
                'propositions' => Reservation::propositions(
                    (int) $_POST['salle_id'],
                    $_POST['date_reservation'],
                    $_POST['heure_debut'],
                    $_POST['heure_fin']
                ),
            ];
            Flash::set('erreur', 'Désolé, la salle ' . $salle['nom'] . ' est déjà réservée le '
                . format_date_fr($_POST['date_reservation']) . ' de ' . format_heure($_POST['heure_debut'])
                . ' à ' . format_heure($_POST['heure_fin']) . '. Les disponibilités vous sont proposées juste en dessous.');
            Flash::setOld($_POST);
            redirect($retour);
        }

        $id = Reservation::creer($_POST);
        Auth::journaliser('RESERVATION_CREEE', 'Réservation #' . $id . ' · ' . $_POST['evenement']);

        // Notification des gestionnaires par e-mail
        foreach (User::gestionnaires() as $gestionnaire) {
            Mailer::envoyer(
                $gestionnaire['email'],
                'Nouvelle demande de réservation à valider',
                '<p>Bonjour ' . e($gestionnaire['prenom']) . ',</p>'
                . '<p>Une nouvelle demande de réservation attend votre validation :</p>'
                . '<ul><li><strong>Salle :</strong> ' . e($salle['nom']) . '</li>'
                . '<li><strong>Événement :</strong> ' . e($_POST['evenement']) . '</li>'
                . '<li><strong>Demandeur :</strong> ' . e($_POST['demandeur_nom']) . '</li>'
                . '<li><strong>Date :</strong> ' . e(format_date_fr($_POST['date_reservation'])) . ' de ' . e($_POST['heure_debut']) . ' à ' . e($_POST['heure_fin']) . '</li></ul>'
                . Mailer::bouton(url('/login'), 'Examiner la demande')
            );
        }

        Flash::set('succes', 'Votre demande est enregistrée et passe « En attente » : le gestionnaire va l\'examiner et vous répondre par e-mail.');
        redirect($retour);
    }

    /** Le gestionnaire coche : valider, refuser, occuper, libérer, annuler. */
    public function changerStatut(string $id): void
    {
        if (!CSRF::verify()) {
            Flash::set('erreur', 'Session expirée, veuillez réessayer.');
            redirect('/reservations');
        }
        $reservation = Reservation::trouver((int) $id);
        if ($reservation === null) {
            Flash::set('erreur', 'Réservation introuvable.');
            redirect('/reservations');
        }

        $action = $_POST['action'] ?? '';
        if (!isset(self::TRANSITIONS[$action])) {
            Flash::set('erreur', 'Action inconnue.');
            redirect('/reservations');
        }

        if (in_array($action, ['valider', 'occuper'], true)
            && Reservation::conflitExiste((int) $reservation['salle_id'], $reservation['date_reservation'], $reservation['heure_debut'], $reservation['heure_fin'], (int) $reservation['id'])) {
            Flash::set('erreur', 'Impossible : une autre réservation occupe déjà ce créneau sur cette salle.');
            redirect('/reservations');
        }

        $nouveauStatut = self::TRANSITIONS[$action];
        Reservation::changerStatut((int) $id, $nouveauStatut, trim($_POST['commentaire'] ?? '') ?: null);
        Auth::journaliser('RESERVATION_STATUT', 'Réservation #' . $id . ' → ' . $nouveauStatut);

        if (!empty($reservation['demandeur_email'])) {
            $this->notifierDemandeur($reservation, $nouveauStatut, trim($_POST['commentaire'] ?? ''));
        }

        Flash::set('succes', 'Statut mis à jour : ' . statuts_reservation()[$nouveauStatut][0] . '.');
        redirect('/reservations');
    }

    private function notifierDemandeur(array $resa, string $statut, string $commentaire): void
    {
        $salle = $resa['salle_nom'];
        $dateTexte = format_date_fr($resa['date_reservation']) . ' de ' . format_heure($resa['heure_debut']) . ' à ' . format_heure($resa['heure_fin']);
        $note = $commentaire !== '' ? '<p><em>Note du gestionnaire : ' . e($commentaire) . '</em></p>' : '';

        $messages = [
            'CONFIRMEE' => ['Votre réservation est confirmée',
                '<p>Bonne nouvelle : votre réservation a été <strong>confirmée</strong>.</p><p><strong>' . e($resa['evenement']) . '</strong><br>Salle : ' . e($salle) . '<br>' . e($dateTexte) . '</p>' . $note . '<p>Merci de vous présenter 15 minutes avant le début.</p>'],
            'REFUSEE' => ['Votre demande de réservation ne peut être retenue',
                '<p>Nous sommes désolés : votre demande pour <strong>' . e($resa['evenement']) . '</strong> (salle ' . e($salle) . ', ' . e($dateTexte) . ') n\'a pas pu être retenue.</p>' . $note . '<p>N\'hésitez pas à reformuler une demande sur un autre créneau.</p>'],
            'OCCUPEE' => ['Votre événement a commencé : salle occupée',
                '<p>Votre événement <strong>' . e($resa['evenement']) . '</strong> a débuté dans la salle ' . e($salle) . '.</p><p>Bon événement !</p>'],
            'TERMINEE' => ['Fin de votre réservation : la salle est libérée',
                '<p>Votre réservation du ' . e($dateTexte) . ' (salle ' . e($salle) . ') est terminée et la salle a été libérée.</p><p>Merci d\'avoir choisi notre centre.</p>'],
            'ANNULEE' => ['Votre réservation a été annulée',
                '<p>Votre réservation <strong>' . e($resa['evenement']) . '</strong> (salle ' . e($salle) . ', ' . e($dateTexte) . ') a été annulée.</p>' . $note],
        ];

        if (isset($messages[$statut])) {
            [$sujet, $corps] = $messages[$statut];
            Mailer::envoyer($resa['demandeur_email'], $sujet, $corps);
        }
    }
}
