<?php
namespace App\Models;

use App\Core\Database;

final class SessionFormation
{
    /** Toutes les sessions, avec formation, salle et nombre d'inscrits confirmés. */
    public static function toutes(): array
    {
        return Database::select(
            'SELECT sf.*, f.titre AS formation_titre, f.theme AS formation_theme, s.nom AS salle_nom,
                    (SELECT COUNT(*) FROM inscriptions i WHERE i.session_formation_id = sf.id AND i.statut = "CONFIRMEE") AS nb_inscrits
             FROM sessions_formation sf
             JOIN formations f ON f.id = sf.formation_id
             JOIN salles s ON s.id = sf.salle_id
             ORDER BY sf.date_debut DESC LIMIT 200'
        );
    }

    public static function trouver(int $id): ?array
    {
        return Database::selectOne(
            'SELECT sf.*, f.titre AS formation_titre, s.nom AS salle_nom, s.capacite AS salle_capacite
             FROM sessions_formation sf
             JOIN formations f ON f.id = sf.formation_id
             JOIN salles s ON s.id = sf.salle_id
             WHERE sf.id = ?',
            [$id]
        );
    }

    /** Sessions ouvertes à l'inscription en ligne (à venir, places disponibles). */
    public static function ouvertes(): array
    {
        return Database::select(
            'SELECT sf.*, f.titre AS formation_titre, f.theme AS formation_theme, f.niveau, s.nom AS salle_nom,
                    sf.places - (SELECT COUNT(*) FROM inscriptions i WHERE i.session_formation_id = sf.id AND i.statut = "CONFIRMEE") AS places_restantes
             FROM sessions_formation sf
             JOIN formations f ON f.id = sf.formation_id
             JOIN salles s ON s.id = sf.salle_id
             WHERE sf.statut IN ("PLANIFIEE","EN_COURS") AND sf.date_fin >= CURDATE()
             ORDER BY sf.date_debut'
        );
    }

    public static function placesRestantes(int $id): int
    {
        $session = self::trouver($id);
        if ($session === null) {
            return 0;
        }
        $prises = (int) Database::selectOne(
            'SELECT COUNT(*) AS n FROM inscriptions WHERE session_formation_id = ? AND statut = "CONFIRMEE"',
            [$id]
        )['n'];
        return max(0, (int) $session['places'] - $prises);
    }

    /**
     * Crée la session ET réserve automatiquement la salle pour chaque jour
     * de la période (réservations CONFIRMEE, visibles dans le planning).
     */
    public static function creer(array $d): int
    {
        $id = Database::insert(
            'INSERT INTO sessions_formation (formation_id, salle_id, date_debut, date_fin, heure_debut, heure_fin, places, cout, formateur_nom)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [(int) $d['formation_id'], (int) $d['salle_id'], $d['date_debut'], $d['date_fin'], $d['heure_debut'], $d['heure_fin'],
             (int) $d['places'], $d['cout'] !== '' ? $d['cout'] : null, $d['formateur_nom'] ?: null]
        );
        self::reserverSalle($id);
        return $id;
    }

    public static function maj(int $id, array $d): void
    {
        Database::execute(
            'UPDATE sessions_formation SET formation_id = ?, salle_id = ?, date_debut = ?, date_fin = ?, heure_debut = ?, heure_fin = ?, places = ?, cout = ?, formateur_nom = ? WHERE id = ?',
            [(int) $d['formation_id'], (int) $d['salle_id'], $d['date_debut'], $d['date_fin'], $d['heure_debut'], $d['heure_fin'],
             (int) $d['places'], $d['cout'] !== '' ? $d['cout'] : null, $d['formateur_nom'] ?: null, $id]
        );
        // Les réservations automatiques suivent la session : on retire les
        // anciennes puis on réserve la salle pour la nouvelle période.
        self::libererSalle($id);
        self::reserverSalle($id);
    }

    /** Supprime les réservations automatiques liées à une session. */
    private static function libererSalle(int $sessionId): void
    {
        Database::execute(
            'DELETE FROM reservations WHERE demandeur_nom = "Centre de formation" AND evenement LIKE ?',
            ['%(session #' . $sessionId . ')%']
        );
    }

    /** Une réservation journalière par jour de session, au nom du centre. */
    private static function reserverSalle(int $sessionId): void
    {
        $session = self::trouver($sessionId);
        if ($session === null) {
            return;
        }
        $formation = Formation::trouver((int) $session['formation_id']);
        $intitule = 'Formation : ' . ($formation['titre'] ?? 'session') . ' (session #' . $sessionId . ')';

        $debut = new \DateTime($session['date_debut']);
        $fin = new \DateTime($session['date_fin']);
        $fin = $fin->modify('+1 day');
        $interval = new \DateInterval('P1D');
        foreach (new \DatePeriod($debut, $interval, $fin) as $jour) {
            if (Reservation::conflitExiste((int) $session['salle_id'], $jour->format('Y-m-d'), $session['heure_debut'], $session['heure_fin'], null)) {
                continue; // un conflit existe déjà : ce jour-là reste géré manuellement
            }
            Database::insert(
                'INSERT INTO reservations (salle_id, demandeur_nom, demandeur_organisation, evenement, date_reservation, heure_debut, heure_fin, nb_personnes, statut, commentaire)
                 VALUES (?, "Centre de formation", "Session interne", ?, ?, ?, ?, ?, "CONFIRMEE", "Réservation automatique liée à la session de formation")',
                [(int) $session['salle_id'], $intitule, $jour->format('Y-m-d'), $session['heure_debut'], $session['heure_fin'], (int) $session['places']]
            );
        }
    }

    public static function changerStatut(int $id, string $statut): void
    {
        Database::execute('UPDATE sessions_formation SET statut = ? WHERE id = ?', [$statut, $id]);
    }

    public static function compteurParStatut(string $statut): int
    {
        return (int) Database::selectOne('SELECT COUNT(*) AS n FROM sessions_formation WHERE statut = ?', [$statut])['n'];
    }

    /** Sessions d'un apprenant (via ses inscriptions confirmées). */
    public static function pourApprenant(int $userId): array
    {
        return Database::select(
            'SELECT sf.*, f.titre AS formation_titre, f.niveau, s.nom AS salle_nom, i.statut AS inscription_statut
             FROM inscriptions i
             JOIN sessions_formation sf ON sf.id = i.session_formation_id
             JOIN formations f ON f.id = sf.formation_id
             JOIN salles s ON s.id = sf.salle_id
             WHERE i.user_id = ? AND i.statut IN ("CONFIRMEE","LISTE_ATTENTE")
             ORDER BY sf.date_debut',
            [$userId]
        );
    }
}
