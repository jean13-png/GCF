<?php
namespace App\Models;

use App\Core\Auth;
use App\Core\Database;

final class Reservation
{
    /** Liste filtrable (statut, salle, date), avec le nom de la salle. */
    public static function tous(array $filtres = []): array
    {
        $sql = 'SELECT r.*, s.nom AS salle_nom, s.capacite AS salle_capacite
                FROM reservations r JOIN salles s ON s.id = r.salle_id WHERE 1 = 1';
        $params = [];
        if (!empty($filtres['statut'])) {
            $sql .= ' AND r.statut = ?';
            $params[] = $filtres['statut'];
        }
        if (!empty($filtres['salle_id'])) {
            $sql .= ' AND r.salle_id = ?';
            $params[] = (int) $filtres['salle_id'];
        }
        if (!empty($filtres['du'])) {
            $sql .= ' AND r.date_reservation >= ?';
            $params[] = $filtres['du'];
        }
        $sql .= ' ORDER BY r.date_reservation DESC, r.heure_debut DESC LIMIT 200';
        return Database::select($sql, $params);
    }

    public static function trouver(int $id): ?array
    {
        return Database::selectOne(
            'SELECT r.*, s.nom AS salle_nom FROM reservations r JOIN salles s ON s.id = r.salle_id WHERE r.id = ?',
            [$id]
        );
    }

    /**
     * Un chevauchement existe-t-il déjà sur cette salle, sur cette période
     * ( une réservation peut couvrir plusieurs jours ), pour ces heures ?
     */
    public static function conflitExiste(int $salleId, string $dateDebut, ?string $dateFin, string $debut, string $fin, ?int $ignorerId = null): bool
    {
        /* une réservation sans date_fin ne couvre que son premier jour */
        $dateFin = $dateFin ?: $dateDebut;
        return Database::selectOne(
            'SELECT id FROM reservations
             WHERE salle_id = ?
               AND statut IN ("EN_ATTENTE","CONFIRMEE","OCCUPEE","A_REPORTER")
               AND date_reservation <= ?
               AND COALESCE(date_fin, date_reservation) >= ?
               AND heure_debut < ? AND heure_fin > ?
               AND (? IS NULL OR id <> ?)
             LIMIT 1',
            [$salleId, $dateFin, $dateDebut, $fin, $debut, $ignorerId, $ignorerId]
        ) !== null;
    }

    public static function creer(array $d): int
    {
        $id = Database::insert(
            'INSERT INTO reservations (salle_id, demandeur_nom, demandeur_organisation, demandeur_email, demandeur_telephone, evenement, date_reservation, heure_debut, heure_fin, nb_personnes, statut)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "EN_ATTENTE")',
            [$d['salle_id'], $d['demandeur_nom'], $d['demandeur_organisation'] ?: null, $d['demandeur_email'] ?: null,
             $d['demandeur_telephone'] ?: null, $d['evenement'], $d['date_reservation'], $d['heure_debut'], $d['heure_fin'],
             $d['nb_personnes'] !== '' ? (int) $d['nb_personnes'] : null]
        );
        self::journaliserEvent($id, 'EN_ATTENTE', 'Demande déposée');
        return $id;
    }

    /**
     * Change le statut (le cœur du système : le gestionnaire coche
     * Réservé, puis Occupé, puis rend la salle Libre en terminant).
     */
    public static function changerStatut(int $id, string $statut, ?string $commentaire = null): void
    {
        Database::execute(
            'UPDATE reservations SET statut = ?, traite_par = ? WHERE id = ?',
            [$statut, Auth::id(), $id]
        );
        self::journaliserEvent($id, $statut, $commentaire);
    }

    public static function journaliserEvent(int $reservationId, string $statut, ?string $commentaire): void
    {
        Database::insert(
            'INSERT INTO reservation_events (reservation_id, statut, commentaire, user_id) VALUES (?, ?, ?, ?)',
            [$reservationId, $statut, $commentaire, Auth::id()]
        );
    }

    public static function events(int $reservationId): array
    {
        return Database::select(
            'SELECT e.*, u.nom AS user_nom, u.prenom AS user_prenom
             FROM reservation_events e LEFT JOIN users u ON u.id = e.user_id
             WHERE e.reservation_id = ? ORDER BY e.cree_le DESC',
            [$reservationId]
        );
    }

    /**
     * Réservations actives chevauchant une période ( chevauchement :
     * une réservation commencée avant la période ou s'étendant au-delà
     * est inclue ), avec salle, site et formateur.
     */
    public static function entre(string $debut, string $fin): array
    {
        return Database::select(
            'SELECT r.*, s.nom AS salle_nom, s.site AS salle_site, s.maintenance AS salle_maintenance,
                    u.nom AS formateur_nom, u.prenom AS formateur_prenom
             FROM reservations r
             JOIN salles s ON s.id = r.salle_id
             LEFT JOIN users u ON u.id = r.formateur_id
             WHERE r.date_reservation <= ? AND COALESCE(r.date_fin, r.date_reservation) >= ?
               AND r.statut IN ("EN_ATTENTE","CONFIRMEE","OCCUPEE","A_REPORTER")
             ORDER BY r.date_reservation, r.heure_debut',
            [$fin, $debut]
        );
    }

    public static function compteurParStatut(string $statut): int
    {
        return (int) Database::selectOne('SELECT COUNT(*) AS n FROM reservations WHERE statut = ?', [$statut])['n'];
    }

    /* ═══════════════════════════════════════════════════════════════
       Journée d'une salle : créneaux, disponibilités, programmation
       ═══════════════════════════════════════════════════════════════ */

    /**
     * Réservations actives d'une salle couvrant une date précise
     * ( y compris les réservations multi-jours qui l'englobent ).
     */
    public static function duJour(int $salleId, string $date): array
    {
        $lignes = Database::select(
            'SELECT * FROM reservations
             WHERE salle_id = ? AND date_reservation <= ? AND COALESCE(date_fin, date_reservation) >= ?
               AND statut IN ("EN_ATTENTE","CONFIRMEE","OCCUPEE","A_REPORTER")
             ORDER BY heure_debut',
            [$salleId, $date, $date]
        );
        // Heures normalisées au format HH:MM (MySQL renvoie HH:MM:SS)
        foreach ($lignes as &$l) {
            $l['heure_debut'] = substr($l['heure_debut'], 0, 5);
            $l['heure_fin']   = substr($l['heure_fin'], 0, 5);
        }
        unset($l);
        return $lignes;
    }

    /**
     * Intervalles libres d'une salle sur une journée, entre l'ouverture et la fermeture.
     * Retour : liste de ['debut' => '08:00', 'fin' => '12:00'].
     */
    public static function creneauxLibres(int $salleId, string $date, string $ouverture = '07:00', string $fermeture = '21:00'): array
    {
        $libres = [];
        $curseur = $ouverture;
        foreach (self::duJour($salleId, $date) as $resa) {
            if ($resa['heure_debut'] > $curseur) {
                $libres[] = ['debut' => $curseur, 'fin' => $resa['heure_debut']];
            }
            if ($resa['heure_fin'] > $curseur) {
                $curseur = $resa['heure_fin'];
            }
        }
        if ($curseur < $fermeture) {
            $libres[] = ['debut' => $curseur, 'fin' => $fermeture];
        }
        return $libres;
    }

    /**
     * Propositions quand un créneau est déjà pris :
     * intervalles libres restants du jour + prochaines dates disponibles aux heures demandées.
     */
    public static function propositions(int $salleId, string $date, string $debut, string $fin, int $jours = 14): array
    {
        $datesAlternatives = [];
        $essai = new \DateTime($date);
        for ($i = 0; $i < $jours && count($datesAlternatives) < 4; $i++) {
            $essai->modify('+1 day');
            $jour = $essai->format('Y-m-d');
            if (!self::conflitExiste($salleId, $jour, $jour, $debut, $fin, null)) {
                $datesAlternatives[] = $jour;
            }
        }
        return [
            'creneaux_libres'    => self::creneauxLibres($salleId, $date),
            'dates_alternatives' => $datesAlternatives,
        ];
    }

    /**
     * L'administration programme directement un événement depuis le planning :
     * la réservation naît « Réservé » (CONFIRMEE), sans passage par « En attente ».
     */
    public static function creerAdmin(array $d): int
    {
        $id = Database::insert(
            'INSERT INTO reservations (salle_id, demandeur_nom, demandeur_organisation, demandeur_email, demandeur_telephone,
                                       evenement, categorie, date_reservation, date_fin, heure_debut, heure_fin,
                                       nb_personnes, statut, traite_par, formateur_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "CONFIRMEE", ?, ?)',
            [$d['salle_id'], $d['demandeur_nom'], $d['organisation'] ?? null, $d['email'] ?? null, $d['telephone'] ?? null,
             $d['evenement'], $d['categorie'] ?? null, $d['date_reservation'], $d['date_fin'] ?? null,
             $d['heure_debut'], $d['heure_fin'],
             $d['nb_personnes'] !== '' ? (int) $d['nb_personnes'] : null, Auth::id(),
             !empty($d['formateur_id']) ? (int) $d['formateur_id'] : null]
        );
        self::journaliserEvent($id, 'CONFIRMEE', 'Programmé depuis le planning par l\'administration');
        return $id;
    }

    /** Modification d'une réservation depuis la fiche détail du planning. */
    public static function modifier(int $id, array $d): void
    {
        Database::execute(
            'UPDATE reservations SET
                salle_id = ?, evenement = ?, categorie = ?, date_reservation = ?, date_fin = ?,
                heure_debut = ?, heure_fin = ?, nb_personnes = ?, demandeur_nom = ?,
                demandeur_organisation = ?, demandeur_email = ?, demandeur_telephone = ?,
                formateur_id = ?, statut = ?
             WHERE id = ?',
            [(int) $d['salle_id'], $d['evenement'], $d['categorie'] ?? null,
             $d['date_reservation'], $d['date_fin'] ?? null,
             $d['heure_debut'], $d['heure_fin'],
             $d['nb_personnes'] !== '' ? (int) $d['nb_personnes'] : null,
             $d['demandeur_nom'], $d['organisation'] ?? null, $d['email'] ?? null, $d['telephone'] ?? null,
             !empty($d['formateur_id']) ? (int) $d['formateur_id'] : null,
             $d['statut'], $id]
        );
        self::journaliserEvent($id, $d['statut'], 'Modifiée depuis le planning par l\'administration');
    }

    /** Suppression d'une réservation et de son historique. */
    public static function supprimer(int $id): void
    {
        Database::execute('DELETE FROM reservation_events WHERE reservation_id = ?', [$id]);
        Database::execute('DELETE FROM reservations WHERE id = ?', [$id]);
    }

    public static function aVenir(int $limite = 8): array
    {
        return Database::select(
            'SELECT r.*, s.nom AS salle_nom FROM reservations r JOIN salles s ON s.id = r.salle_id
             WHERE r.date_reservation >= CURDATE() AND r.statut IN ("EN_ATTENTE","CONFIRMEE","OCCUPEE")
             ORDER BY r.date_reservation, r.heure_debut LIMIT ' . (int) $limite
        );
    }
}
