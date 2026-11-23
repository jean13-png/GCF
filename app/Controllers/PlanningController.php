<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Validator;
use App\Models\Reservation;
use App\Models\Salle;
use App\Models\User;

final class PlanningController
{
    private int $ouverture;
    private int $fermeture;

    public function __construct()
    {
        $this->ouverture = max(0, (int) config('planning.ouverture', 6));
        $this->fermeture = max($this->ouverture + 1, (int) config('planning.fermeture', 22));
    }

    public function index(): void
    {
        $decalage = (int) ($_GET['semaine'] ?? 0);
        $decalage = max(-52, min(52, $decalage));
        $lundi = new \DateTime('monday this week');
        $lundi->modify(($decalage >= 0 ? '+' : '') . $decalage . ' weeks');
        $dimanche = (clone $lundi)->modify('+6 days');

        $salles = Salle::tous();
        $reservations = Reservation::entre($lundi->format('Y-m-d'), $dimanche->format('Y-m-d'));

        /* ── Mode d'affichage : grille d'une salle (défaut), synthèse
              ou vue Ressources multi-semaines ( Gantt ) ─────────── */
        $vue = $_GET['vue'] ?? 'salle';
        if (!in_array($vue, ['salle', 'synthese', 'ressources'], true)) {
            $vue = 'salle';
        }

        /* ── Salle affichée dans la grille détaillée ───────────── */
        $salleId = (int) ($_GET['salle_id'] ?? 0);
        $salle = $salleId > 0 ? Salle::trouver($salleId) : null;
        if ($salle === null && count($salles) > 0) {
            $salle = $salles[0];
            $salleId = (int) $salle['id'];
        }

        /* ── Réservations de la semaine :
              · resasSalle : celles de la salle affichée ( couverture
                multi-jours prise en compte dans la vue )
              · grille     : par salle puis jour, pour la synthèse ── */
        $resasSalle = [];
        $grille = [];
        foreach ($salles as $s) {
            foreach (range(0, 6) as $jour) {
                $grille[$s['id']][$jour] = [];
            }
        }
        $couvre = static function (array $r, string $date): bool {
            $fin = $r['date_fin'] ?: $r['date_reservation'];
            return $r['date_reservation'] <= $date && $fin >= $date;
        };
        foreach (range(0, 6) as $jour) {
            $dateJour = (clone $lundi)->modify("+$jour days")->format('Y-m-d');
            foreach ($reservations as $resa) {
                if ($couvre($resa, $dateJour)) {
                    $grille[$resa['salle_id']][$jour][] = $resa;
                    if ((int) $resa['salle_id'] === $salleId) {
                        $resasSalle[$resa['id']] = $resa;
                    }
                }
            }
        }
        $resasSalle = array_values($resasSalle);

        /* ── Statistiques d'occupation de la salle affichée ────── */
        $nbEvenements = count($resasSalle);
        $minutesReservees = 0;
        $lundiIso = $lundi->format('Y-m-d');
        $dimancheIso = $dimanche->format('Y-m-d');
        foreach ($resasSalle as $r) {
            $debutCouvert = max($r['date_reservation'], $lundiIso);
            $finCouverte = min($r['date_fin'] ?: $r['date_reservation'], $dimancheIso);
            $joursCouverts = (int) floor((strtotime($finCouverte) - strtotime($debutCouvert)) / 86400) + 1;
            $minutesReservees += $joursCouverts * ((strtotime($r['heure_fin']) - strtotime($r['heure_debut'])) / 60);
        }
        $capaciteHebdo = 7 * ($this->fermeture - $this->ouverture) * 60;
        $tauxOccupation = $capaciteHebdo > 0
            ? (int) round($minutesReservees / $capaciteHebdo * 100)
            : 0;

        view('planning/index', [
            'salles'          => $salles,
            'salle'           => $salle,
            'salleId'         => $salleId,
            'vue'             => $vue,
            'resasSalle'      => $resasSalle,
            'grille'          => $grille,
            'lundi'           => $lundi,
            'dimanche'        => $dimanche,
            'decalage'        => $decalage,
            'nbEvenements'    => $nbEvenements,
            'heuresReservees' => round($minutesReservees / 60, 1),
            'tauxOccupation'  => $tauxOccupation,
            'ouverture'       => $this->ouverture,
            'fermeture'       => $this->fermeture,
            'formateurs'      => User::formateurs(),
            'categories'      => ['Conférence', 'Réunion', 'Formation', 'Atelier', 'Séminaire', 'Examen', 'Autre'],
            'statuts'         => ['EN_ATTENTE', 'CONFIRMEE', 'OCCUPEE', 'A_REPORTER'],
            'peutGerer'       => in_array(Auth::role(), ['ADMIN', 'GESTIONNAIRE', 'RESPONSABLE'], true),
            'utilisateur'     => Auth::user(),
        ]);
    }

    /**
     * Données de la vue Ressources ( Gantt multi-semaines ) en JSON :
     * salles, formateurs et réservations chevauchant la période, plus
     * les alertes ( conflits, doubles réservations, salles en maintenance ).
     */
    public function donnees(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $debut = (string) ($_GET['debut'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $debut)) {
            $debut = (new \DateTime('monday this week'))->format('Y-m-d');
        }
        $semaines = max(1, min(26, (int) ($_GET['semaines'] ?? (int) config('planning.semaines_ressources', 8))));
        $fin = (new \DateTime($debut))->modify('+' . (7 * $semaines - 1) . ' days')->format('Y-m-d');

        $reservations = Reservation::entre($debut, $fin);

        /* Normalisation + champs dérivés consommés par le front */
        $liste = [];
        foreach ($reservations as $r) {
            $liste[] = [
                'id'            => (int) $r['id'],
                'evenement'     => $r['evenement'],
                'categorie'     => $r['categorie'],
                'statut'        => $r['statut'],
                'salle_id'      => (int) $r['salle_id'],
                'salle_nom'     => $r['salle_nom'],
                'client'        => $r['demandeur_organisation'] ?: $r['demandeur_nom'],
                'demandeur_nom' => $r['demandeur_nom'],
                'formateur_id'  => $r['formateur_id'] !== null ? (int) $r['formateur_id'] : null,
                'formateur_nom' => trim(($r['formateur_prenom'] ?? '') . ' ' . ($r['formateur_nom'] ?? '')) ?: null,
                'date_debut'    => $r['date_reservation'],
                'date_fin'      => $r['date_fin'] ?: $r['date_reservation'],
                'heure_debut'   => substr($r['heure_debut'], 0, 5),
                'heure_fin'     => substr($r['heure_fin'], 0, 5),
                'nb_personnes'  => $r['nb_personnes'],
                'email'         => $r['demandeur_email'],
                'telephone'     => $r['demandeur_telephone'],
            ];
        }

        $salles = [];
        foreach (Salle::tous() as $s) {
            $salles[] = [
                'id'          => (int) $s['id'],
                'nom'         => $s['nom'],
                'site'        => $s['site'] ?: 'Cotonou',
                'capacite'    => (int) $s['capacite'],
                'maintenance' => (int) $s['maintenance'] === 1,
            ];
        }

        $formateurs = [];
        foreach (User::formateurs() as $f) {
            $formateurs[] = [
                'id'    => (int) $f['id'],
                'nom'   => $f['nom'],
                'prenom'=> $f['prenom'],
            ];
        }

        echo json_encode([
            'debut'        => $debut,
            'fin'          => $fin,
            'semaines'     => $semaines,
            'salles'       => $salles,
            'formateurs'   => $formateurs,
            'reservations' => $liste,
            'alertes'      => self::detecterAlertes($liste, $salles),
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Détection des messages d'alerte ( affichés si l'option est cochée ) :
     * conflits d'horaires sur une même salle, double réservation d'un même
     * client sur un même créneau, réservation sur une salle en maintenance.
     */
    private static function detecterAlertes(array $reservations, array $salles): array
    {
        $alertes = [];
        $parSalle = [];
        $parClient = [];
        $parFormateur = [];
        foreach ($reservations as $r) {
            $parSalle[$r['salle_id']][] = $r;
            $cleClient = $r['client'] ?: $r['demandeur_nom'];
            $parClient[$cleClient][] = $r;
            if (!empty($r['formateur_id'])) {
                $parFormateur[$r['formateur_id']][] = $r;
            }
        }

        $chevauchent = static function (array $a, array $b): bool {
            return $a['date_debut'] <= $b['date_fin'] && $b['date_debut'] <= $a['date_fin']
                && $a['heure_debut'] < $b['heure_fin'] && $b['heure_debut'] < $a['heure_fin'];
        };

        foreach ($parSalle as $salleId => $resas) {
            $n = count($resas);
            for ($i = 0; $i < $n; $i++) {
                for ($j = $i + 1; $j < $n; $j++) {
                    if ($chevauchent($resas[$i], $resas[$j])) {
                        $alertes[] = [
                            'type'    => 'conflit',
                            'message' => 'Conflit d\'horaires : « ' . $resas[$i]['evenement'] . ' » et « ' . $resas[$j]['evenement'] . ' » se chevauchent en ' . $resas[$i]['salle_nom'] . '.',
                        ];
                    }
                }
            }
        }

        foreach ($parClient as $client => $resas) {
            $n = count($resas);
            for ($i = 0; $i < $n; $i++) {
                for ($j = $i + 1; $j < $n; $j++) {
                    if ($resas[$i]['salle_id'] !== $resas[$j]['salle_id'] && $chevauchent($resas[$i], $resas[$j])) {
                        $alertes[] = [
                            'type'    => 'double',
                            'message' => 'Double réservation : ' . $client . ' est attendu en même temps en ' . $resas[$i]['salle_nom'] . ' et en ' . $resas[$j]['salle_nom'] . '.',
                        ];
                    }
                }
            }
        }

        foreach ($parFormateur as $formateurId => $resas) {
            $n = count($resas);
            for ($i = 0; $i < $n; $i++) {
                for ($j = $i + 1; $j < $n; $j++) {
                    if ($resas[$i]['salle_id'] !== $resas[$j]['salle_id'] && $chevauchent($resas[$i], $resas[$j])) {
                        $nom = $resas[$i]['formateur_nom'] ?: ('formateur #' . $formateurId);
                        $alertes[] = [
                            'type'    => 'double',
                            'message' => 'Double réservation : le formateur ' . $nom . ' est attendu en même temps en ' . $resas[$i]['salle_nom'] . ' et en ' . $resas[$j]['salle_nom'] . '.',
                        ];
                    }
                }
            }
        }

        $maintenance = [];
        foreach ($salles as $s) {
            if ($s['maintenance']) {
                $maintenance[$s['id']] = $s['nom'];
            }
        }
        if ($maintenance !== []) {
            foreach ($reservations as $r) {
                if (isset($maintenance[$r['salle_id']])) {
                    $alertes[] = [
                        'type'    => 'maintenance',
                        'message' => 'Salle indisponible : « ' . $r['evenement'] . ' » est programmée en ' . $maintenance[$r['salle_id']] . ' ( maintenance ).',
                    ];
                }
            }
        }

        return array_slice($alertes, 0, 30);
    }

    /**
     * Détail d'une journée d'une salle (JSON) : découpage de la journée en
     * créneaux d'une heure avec, pour chacun, son statut et son événement,
     * plus les intervalles libres et les réservations complètes.
     * Utilisé par le modal du planning (clic sur une case du planning).
     */
    public function jour(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $salleId = (int) ($_GET['salle_id'] ?? 0);
        $date    = (string) ($_GET['date'] ?? '');
        $salle   = Salle::trouver($salleId);

        if ($salle === null || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            echo json_encode(['erreur' => 'Salle ou date invalide.']);
            return;
        }

        $occurrences = Reservation::duJour($salleId, $date);
        $priorites   = ['OCCUPEE' => 4, 'CONFIRMEE' => 3, 'EN_ATTENTE' => 2, 'A_REPORTER' => 2];

        $creneaux = [];
        for ($h = $this->ouverture; $h < $this->fermeture; $h++) {
            $debut = sprintf('%02d:00', $h);
            $fin   = sprintf('%02d:00', $h + 1);

            $statut = 'LIBRE';
            $detail = null;
            $poids  = 0;
            foreach ($occurrences as $o) {
                if ($o['heure_debut'] < $fin && $o['heure_fin'] > $debut) {
                    $p = $priorites[$o['statut']] ?? 1;
                    if ($p > $poids) {
                        $poids  = $p;
                        $statut = $o['statut'];
                        $detail = $o;
                    }
                }
            }

            $creneaux[] = [
                'debut'     => $debut,
                'fin'       => $fin,
                'statut'    => $statut,
                'evenement' => $detail['evenement'] ?? null,
                'demandeur' => $detail['demandeur_nom'] ?? null,
                'resa_debut'=> $detail['heure_debut'] ?? null,
                'resa_fin'  => $detail['heure_fin'] ?? null,
            ];
        }

        echo json_encode([
            'salle' => [
                'id'           => (int) $salle['id'],
                'nom'          => $salle['nom'],
                'capacite'     => (int) $salle['capacite'],
                'localisation' => $salle['localisation'],
                'equipements'  => $salle['equipements'],
                'maintenance'  => (int) $salle['maintenance'] === 1,
            ],
            'date'            => $date,
            'occurrences'     => $occurrences,
            'creneaux'        => $creneaux,
            'creneaux_libres' => Reservation::creneauxLibres(
                $salleId,
                $date,
                sprintf('%02d:00', $this->ouverture),
                sprintf('%02d:00', $this->fermeture)
            ),
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * L'administration programme un événement directement depuis le planning :
     * le créneau devient « Réservé » aussitôt, et toute autre demande sur ce
     * créneau (publique ou interne) sera refusée.
     */
    public function programmer(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!CSRF::verify()) {
            echo json_encode(['erreur' => 'Session expirée : rechargez la page puis réessayez.']);
            return;
        }

        $v = new Validator($_POST);
        $v->required('salle_id', 'salle')->dans('salle_id', 'salle', array_map('strval', array_column(Salle::tous(), 'id')))
          ->required('evenement', 'intitulé')->maxLength('evenement', 'intitulé', 200)
          ->required('demandeur_nom', 'organisateur')->maxLength('demandeur_nom', 'organisateur', 150)
          ->required('date_reservation', 'date')->date('date_reservation', 'date')
          ->required('heure_debut', 'heure de début')->heure('heure_debut', 'heure de début')
          ->required('heure_fin', 'heure de fin')->heure('heure_fin', 'heure de fin')
          ->apres('heure_debut', 'heure_fin', 'début', 'fin')
          ->entier('nb_personnes', 'nombre de personnes', 0, 2000);

        if ($v->echoue()) {
            echo json_encode(['erreur' => $v->premierMessage()]);
            return;
        }

        $salle = Salle::trouver((int) $_POST['salle_id']);
        if ($salle === null || (int) $salle['maintenance'] === 1) {
            echo json_encode(['erreur' => 'Cette salle n\'est pas disponible (maintenance ou inexistante).']);
            return;
        }

        $dateFin = $this->lireDateFin();
        if (Reservation::conflitExiste((int) $_POST['salle_id'], $_POST['date_reservation'], $dateFin, $_POST['heure_debut'], $_POST['heure_fin'], null)) {
            echo json_encode(['erreur' => 'Conflit d\'horaires : un événement occupe déjà ce créneau. Choisissez un créneau libre.']);
            return;
        }

        $_POST['date_fin'] = $dateFin;
        $_POST['categorie'] = $this->lireCategorie();
        $id = Reservation::creerAdmin($_POST);
        Auth::journaliser('RESERVATION_PROGRAMMEE', 'Programmation planning #' . $id . ' · ' . $_POST['evenement'] . ' · ' . $_POST['date_reservation'] . ' ' . $_POST['heure_debut'] . '-' . $_POST['heure_fin']);

        echo json_encode(['ok' => true, 'id' => $id], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Modification d'une réservation depuis la fiche détail du planning
     * ( y compris le glisser-déposer et le redimensionnement des blocs ).
     */
    public function modifier(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!CSRF::verify()) {
            echo json_encode(['erreur' => 'Session expirée : rechargez la page puis réessayez.']);
            return;
        }
        if (!in_array(Auth::role(), ['ADMIN', 'GESTIONNAIRE', 'RESPONSABLE'], true)) {
            echo json_encode(['erreur' => 'Vous n\'avez pas le droit de modifier les réservations.']);
            return;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $existante = Reservation::trouver($id);
        if ($existante === null) {
            echo json_encode(['erreur' => 'Réservation introuvable.']);
            return;
        }

        $v = new Validator($_POST);
        $v->required('salle_id', 'salle')->dans('salle_id', 'salle', array_map('strval', array_column(Salle::tous(), 'id')))
          ->required('evenement', 'intitulé')->maxLength('evenement', 'intitulé', 200)
          ->required('demandeur_nom', 'organisateur')->maxLength('demandeur_nom', 'organisateur', 150)
          ->required('date_reservation', 'date')->date('date_reservation', 'date')
          ->required('heure_debut', 'heure de début')->heure('heure_debut', 'heure de début')
          ->required('heure_fin', 'heure de fin')->heure('heure_fin', 'heure de fin')
          ->apres('heure_debut', 'heure_fin', 'début', 'fin')
          ->entier('nb_personnes', 'nombre de personnes', 0, 2000);

        if ($v->echoue()) {
            echo json_encode(['erreur' => $v->premierMessage()]);
            return;
        }

        $statut = (string) ($_POST['statut'] ?? $existante['statut']);
        if (!in_array($statut, ['EN_ATTENTE', 'CONFIRMEE', 'OCCUPEE', 'A_REPORTER'], true)) {
            $statut = $existante['statut'];
        }

        $dateFin = $this->lireDateFin();
        if ($dateFin !== null && $dateFin < $_POST['date_reservation']) {
            $dateFin = null;
        }

        if (Reservation::conflitExiste((int) $_POST['salle_id'], $_POST['date_reservation'], $dateFin ?? $_POST['date_reservation'], $_POST['heure_debut'], $_POST['heure_fin'], $id)) {
            echo json_encode(['erreur' => 'Conflit d\'horaires : un autre événement occupe déjà ce créneau.']);
            return;
        }

        $_POST['date_fin'] = $dateFin;
        $_POST['categorie'] = $this->lireCategorie();
        $_POST['statut'] = $statut;
        Reservation::modifier($id, $_POST);
        Auth::journaliser('RESERVATION_MODIFIEE', 'Modification planning #' . $id . ' · ' . $_POST['evenement'] . ' · ' . $_POST['date_reservation'] . ' ' . $_POST['heure_debut'] . '-' . $_POST['heure_fin']);

        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    }

    /** Suppression d'une réservation depuis la fiche détail du planning. */
    public function supprimer(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!CSRF::verify()) {
            echo json_encode(['erreur' => 'Session expirée : rechargez la page puis réessayez.']);
            return;
        }
        if (!in_array(Auth::role(), ['ADMIN', 'GESTIONNAIRE', 'RESPONSABLE'], true)) {
            echo json_encode(['erreur' => 'Vous n\'avez pas le droit de supprimer les réservations.']);
            return;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $existante = Reservation::trouver($id);
        if ($existante === null) {
            echo json_encode(['erreur' => 'Réservation introuvable.']);
            return;
        }

        Reservation::supprimer($id);
        Auth::journaliser('RESERVATION_SUPPRIMEE', 'Suppression planning #' . $id . ' · ' . $existante['evenement'] . ' · ' . $existante['date_reservation']);

        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    }

    /* ── Lectures sécurisées des champs optionnels ─────────────── */

    private function lireDateFin(): ?string
    {
        $d = trim((string) ($_POST['date_fin'] ?? ''));
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) ? $d : null;
    }

    private function lireCategorie(): ?string
    {
        $c = trim((string) ($_POST['categorie'] ?? ''));
        return $c !== '' ? mb_substr($c, 0, 50) : null;
    }

    /**
     * Export PDF du planning de la semaine affichée : vue d'ensemble puis une page par salle.
     */
    public function exporter(): void
    {
        $decalage = (int) ($_GET['semaine'] ?? 0);
        $decalage = max(-52, min(52, $decalage));
        $lundi = new \DateTime('monday this week');
        $lundi->modify(($decalage >= 0 ? '+' : '') . $decalage . ' weeks');
        $dimanche = (clone $lundi)->modify('+6 days');

        $salles = Salle::tous();
        $salleId = (int) ($_GET['salle_id'] ?? 0);
        $nomFichier = 'planning_salles_';
        if ($salleId > 0) {
            $choisie = array_values(array_filter($salles, static fn($s) => (int) $s['id'] === $salleId));
            if ($choisie !== []) {
                $salles = $choisie;
                $slug = strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', (string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string) $choisie[0]['nom'])));
                $nomFichier = 'planning_' . trim($slug, '-') . '_';
            }
        }
        $reservations = Reservation::entre($lundi->format('Y-m-d'), $dimanche->format('Y-m-d'));
        $pdf = (new \App\Core\PdfPlanning())->semaine(
            $salles,
            $reservations,
            $lundi->format('Y-m-d'),
            $dimanche->format('Y-m-d'),
            $this->ouverture,
            $this->fermeture
        );

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $nomFichier . $lundi->format('Y-m-d') . '.pdf"');
        header('Content-Length: ' . strlen($pdf));
        header('Cache-Control: no-store');
        echo $pdf;
        exit;
    }
}
