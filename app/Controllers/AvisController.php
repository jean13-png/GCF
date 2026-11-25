<?php
namespace App\Controllers;

use App\Core\CSRF;
use App\Core\Flash;
use App\Core\Mailer;
use App\Core\Router;
use App\Models\Avis;
use App\Models\Salle;

final class AvisController
{
    /** Page publique du lien personnel (pas de connexion). */
    public function afficherPublique(string $token): void
    {
        $avis = $this->avisValide($token);
        if ($avis['soumis_le'] !== null) {
            view('avis/merci', ['avis' => $avis]);
            return;
        }
        view('avis/publique', ['avis' => $avis, 'token' => $token]);
    }

    /** Soumission de l'avis : une seule fois, copie envoyée à l'ONG. */
    public function soumettre(string $token): void
    {
        $retour = '/avis/' . $token;
        if (!CSRF::verify()) {
            Flash::set('erreur', 'Session expirée, veuillez réessayer.');
            redirect($retour);
        }

        $avis = $this->avisValide($token);

        $noteBrute = trim((string) ($_POST['note'] ?? ''));
        $note = null;
        if ($noteBrute !== '') {
            if (!ctype_digit($noteBrute) || (int) $noteBrute < 1 || (int) $noteBrute > 5) {
                Flash::set('erreur', 'La note doit être comprise entre 1 et 5 étoiles.');
                redirect($retour);
            }
            $note = (int) $noteBrute;
        }

        $positifs = trim((string) ($_POST['avis_positifs'] ?? ''));
        $negatifs = trim((string) ($_POST['avis_negatifs'] ?? ''));
        $general = trim((string) ($_POST['avis_general'] ?? ''));

        if ($general === '') {
            Flash::set('erreur', 'Merci d\'écrire votre avis général avant d\'envoyer.');
            Flash::setOld($_POST);
            redirect($retour);
        }
        foreach ([$positifs, $negatifs, $general] as $texte) {
            if (mb_strlen($texte) > 2000) {
                Flash::set('erreur', 'Chaque zone de texte est limitée à 2 000 caractères.');
                Flash::setOld($_POST);
                redirect($retour);
            }
        }

        if (!Avis::soumettre($token, $note, $positifs, $negatifs, $general)) {
            Flash::set('erreur', 'Cet avis a déjà été enregistré, ou le lien n\'est plus valable.');
            redirect($retour);
        }

        // Copie à l'ONG, comme demandé : l'avis arrive dans l'admin et dans la boîte de l'ONG.
        $sujet = 'Nouvel avis : ' . $avis['evenement'] . ' (' . format_date_fr($avis['date_reservation']) . ')';
        Mailer::envoyer(
            (string) config('mail.from'),
            $sujet,
            $this->corpsCopieOng($avis, $note, $positifs, $negatifs, $general)
        );

        Flash::set('succes', 'Merci ! Votre avis a bien été enregistré.');
        redirect($retour);
    }

    /** Espace admin : liste des avis envoyés, filtrable. */
    public function liste(): void
    {
        $filtres = [
            'salle_id' => $_GET['salle_id'] ?? '',
            'du'       => $_GET['du'] ?? '',
            'etat'     => $_GET['etat'] ?? '',
            'note'     => $_GET['note'] ?? '',
        ];
        if ($filtres['du'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $filtres['du'])) {
            $filtres['du'] = '';
        }
        view('avis/index', [
            'avis'    => Avis::liste($filtres),
            'salles'  => Salle::tous(),
            'stats'   => Avis::stats(),
            'filtres' => $filtres,
        ]);
    }

    /** Renvoie l'avis si le lien est valable, sinon une page neutre (404). */
    private function avisValide(string $token): array
    {
        $avis = Avis::trouverParToken($token);
        if ($avis === null || $avis['envoye_le'] === null || $avis['token_expire_le'] < Avis::maintenant()) {
            Router::pageErreur(
                404,
                'Lien indisponible',
                'Ce lien d\'avis n\'est plus valable. Si vous pensez qu\'il s\'agit d\'une erreur, contactez le centre de formation.'
            );
        }
        return $avis;
    }

    private function corpsCopieOng(array $avis, ?int $note, string $positifs, string $negatifs, string $general): string
    {
        $bloc = fn (string $titre, string $texte): string => $texte === ''
            ? ''
            : '<p><strong>' . e($titre) . '</strong><br>' . nl2br(e($texte)) . '</p>';

        return '<ul>'
            . '<li><strong>Salle :</strong> ' . e($avis['salle_nom']) . '</li>'
            . '<li><strong>Événement :</strong> ' . e($avis['evenement']) . '</li>'
            . '<li><strong>Date :</strong> ' . e(format_date_fr($avis['date_reservation']))
            . ' de ' . e(format_heure($avis['heure_debut'])) . ' à ' . e(format_heure($avis['heure_fin'])) . '</li>'
            . '<li><strong>Demandeur :</strong> ' . e($avis['demandeur_nom'])
            . ' · ' . e($avis['demandeur_email']) . '</li>'
            . '<li><strong>Note :</strong> ' . ($note !== null ? $note . ' / 5' : 'non renseignée') . '</li>'
            . '</ul>'
            . $bloc('Ce qui a bien fonctionné :', $positifs)
            . $bloc('Ce qui pourrait être amélioré :', $negatifs)
            . $bloc('Avis général :', $general);
    }
}
