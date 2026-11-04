<?php
namespace App\Models;

use App\Core\Database;

final class Formation
{
    public static function tous(bool $seulementActives = false): array
    {
        $sql = 'SELECT * FROM formations' . ($seulementActives ? ' WHERE actif = 1' : '') . ' ORDER BY theme, titre';
        return Database::select($sql);
    }

    public static function compterActives(): int
    {
        return (int) Database::selectOne('SELECT COUNT(*) AS n FROM formations WHERE actif = 1')['n'];
    }

    public static function trouver(int $id): ?array
    {
        return Database::selectOne('SELECT * FROM formations WHERE id = ?', [$id]);
    }

    public static function creer(array $d): int
    {
        return Database::insert(
            'INSERT INTO formations (titre, theme, description, duree_heures, niveau, public_vise, tarif) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$d['titre'], $d['theme'], $d['description'] ?: null, (int) $d['duree_heures'], $d['niveau'], $d['public_vise'] ?: null, $d['tarif'] !== '' ? $d['tarif'] : null]
        );
    }

    public static function maj(int $id, array $d): void
    {
        Database::execute(
            'UPDATE formations SET titre = ?, theme = ?, description = ?, duree_heures = ?, niveau = ?, public_vise = ?, tarif = ? WHERE id = ?',
            [$d['titre'], $d['theme'], $d['description'] ?: null, (int) $d['duree_heures'], $d['niveau'], $d['public_vise'] ?: null, $d['tarif'] !== '' ? $d['tarif'] : null, $id]
        );
    }

    public static function basculerActif(int $id): void
    {
        Database::execute('UPDATE formations SET actif = 1 - actif WHERE id = ?', [$id]);
    }
}
