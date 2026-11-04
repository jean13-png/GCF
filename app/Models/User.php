<?php
namespace App\Models;

use App\Core\Database;

final class User
{
    public static function trouverParId(int $id): ?array
    {
        return Database::selectOne('SELECT * FROM users WHERE id = ?', [$id]);
    }

    /** Crée un compte apprenant avec matricule unique généré automatiquement. */
    public static function creerApprenant(string $nom, string $prenom, string $email, ?string $telephone, string $motDePasse): array
    {
        $matricule = self::genererMatricule();
        $id = Database::insert(
            'INSERT INTO users (matricule, nom, prenom, email, telephone, role, password_hash) VALUES (?, ?, ?, ?, ?, "APPRENANT", ?)',
            [$matricule, $nom, $prenom, $email, $telephone, password_hash($motDePasse, PASSWORD_BCRYPT)]
        );
        return self::trouverParId($id);
    }

    private static function genererMatricule(): string
    {
        $annee = date('Y');
        $dernier = Database::selectOne(
            'SELECT matricule FROM users WHERE matricule LIKE ? ORDER BY id DESC LIMIT 1',
            ["GCF-$annee-%"]
        );
        $suivant = 1;
        if ($dernier !== null) {
            $suivant = ((int) substr($dernier['matricule'], -4)) + 1;
        }
        do {
            $matricule = sprintf('GCF-%s-%04d', $annee, $suivant);
            $existe = Database::selectOne('SELECT id FROM users WHERE matricule = ?', [$matricule]);
            $suivant++;
        } while ($existe !== null);
        return $matricule;
    }

    public static function compterApprenants(): int
    {
        return (int) Database::selectOne('SELECT COUNT(*) AS n FROM users WHERE role = "APPRENANT"')['n'];
    }

    public static function gestionnaires(): array
    {
        return Database::select('SELECT * FROM users WHERE role = "GESTIONNAIRE" AND actif = 1');
    }

    public static function changerMotDePasse(int $id, string $nouveau): void
    {
        Database::execute(
            'UPDATE users SET password_hash = ? WHERE id = ?',
            [password_hash($nouveau, PASSWORD_BCRYPT), $id]
        );
    }

    public static function majProfil(int $id, string $telephone): void
    {
        Database::execute('UPDATE users SET telephone = ? WHERE id = ?', [$telephone, $id]);
    }
}
