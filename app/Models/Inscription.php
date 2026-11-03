<?php
namespace App\Models;

use App\Core\Database;

final class Inscription
{
    /** Toutes les inscriptions (vue responsable), filtrables. */
    public static function toutes(array $filtres = []): array
    {
        $sql = 'SELECT i.*, sf.date_debut, sf.date_fin, f.titre AS formation_titre, u.matricule
                FROM inscriptions i
                JOIN sessions_formation sf ON sf.id = i.session_formation_id
                JOIN formations f ON f.id = sf.formation_id
                LEFT JOIN users u ON u.id = i.user_id
                WHERE 1 = 1';
        $params = [];
        if (!empty($filtres['statut'])) {
            $sql .= ' AND i.statut = ?';
            $params[] = $filtres['statut'];
        }
        if (!empty($filtres['session_id'])) {
            $sql .= ' AND i.session_formation_id = ?';
            $params[] = (int) $filtres['session_id'];
        }
        $sql .= ' ORDER BY i.cree_le DESC LIMIT 300';
        return Database::select($sql, $params);
    }

    public static function trouver(int $id): ?array
    {
        return Database::selectOne(
            'SELECT i.*, f.titre AS formation_titre
             FROM inscriptions i
             JOIN sessions_formation sf ON sf.id = i.session_formation_id
             JOIN formations f ON f.id = sf.formation_id
             WHERE i.id = ?',
            [$id]
        );
    }

    public static function creer(array $d, string $token): int
    {
        return Database::insert(
            'INSERT INTO inscriptions (session_formation_id, nom, prenom, email, telephone, niveau_actuel, motivation, statut, token_verif)
             VALUES (?, ?, ?, ?, ?, ?, ?, "EN_ATTENTE_VERIF", ?)',
            [(int) $d['session_formation_id'], $d['nom'], $d['prenom'], $d['email'], $d['telephone'] ?: null,
             $d['niveau_actuel'] ?: null, $d['motivation'] ?: null, $token]
        );
    }

    public static function trouverParToken(string $token): ?array
    {
        return Database::selectOne('SELECT * FROM inscriptions WHERE token_verif = ? AND statut = "EN_ATTENTE_VERIF"', [$token]);
    }

    public static function doublonExiste(int $sessionId, string $email): bool
    {
        return Database::selectOne(
            'SELECT id FROM inscriptions WHERE session_formation_id = ? AND LCASE(email) = LCASE(?) AND statut <> "ANNULEE" LIMIT 1',
            [$sessionId, $email]
        ) !== null;
    }

    public static function confirmer(int $id, ?int $userId): string
    {
        $statut = SessionFormation::placesRestantes(
            (int) Database::selectOne('SELECT session_formation_id FROM inscriptions WHERE id = ?', [$id])['session_formation_id']
        ) > 0 ? 'CONFIRMEE' : 'LISTE_ATTENTE';
        Database::execute(
            'UPDATE inscriptions SET statut = ?, user_id = ?, verifie_le = NOW() WHERE id = ?',
            [$statut, $userId, $id]
        );
        return $statut;
    }

    public static function annuler(int $id): void
    {
        Database::execute('UPDATE inscriptions SET statut = "ANNULEE" WHERE id = ?', [$id]);
    }

    public static function compteurParStatut(string $statut): int
    {
        return (int) Database::selectOne('SELECT COUNT(*) AS n FROM inscriptions WHERE statut = ?', [$statut])['n'];
    }

    public static function dernieres(int $limite = 6): array
    {
        return Database::select(
            'SELECT i.*, f.titre AS formation_titre FROM inscriptions i
             JOIN sessions_formation sf ON sf.id = i.session_formation_id
             JOIN formations f ON f.id = sf.formation_id
             ORDER BY i.id DESC LIMIT ' . (int) $limite
        );
    }

    public static function parSession(int $sessionId): array
    {
        return self::toutes(['session_id' => $sessionId]);
    }
}
