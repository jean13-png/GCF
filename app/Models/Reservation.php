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

    /** Un chevauchement existe-t-il déjà sur cette salle, ce jour, pour ces heures ? */
    public static function conflitExiste(int $salleId, string $date, string $debut, string $fin, ?int $ignorerId = null): bool
    {
        return Database::selectOne(
            'SELECT id FROM reservations
             WHERE salle_id = ? AND date_reservation = ?
               AND statut IN ("EN_ATTENTE","CONFIRMEE","OCCUPEE")
               AND heure_debut < ? AND heure_fin > ?
               AND (? IS NULL OR id <> ?)
             LIMIT 1',
            [$salleId, $date, $fin, $debut, $ignorerId, $ignorerId]
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

    /** Réservations d'une semaine (pour le planning), groupées par salle puis jour. */
    public static function entre(string $lundi, string $dimanche): array
    {
        return Database::select(
            'SELECT r.*, s.nom AS salle_nom FROM reservations r
             JOIN salles s ON s.id = r.salle_id
             WHERE r.date_reservation BETWEEN ? AND ?
               AND r.statut IN ("EN_ATTENTE","CONFIRMEE","OCCUPEE")
             ORDER BY r.date_reservation, r.heure_debut',
            [$lundi, $dimanche]
        );
    }

    public static function compteurParStatut(string $statut): int
    {
        return (int) Database::selectOne('SELECT COUNT(*) AS n FROM reservations WHERE statut = ?', [$statut])['n'];
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
