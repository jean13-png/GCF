<?php
namespace App\Models;

use App\Core\Database;

final class Salle
{
    public static function tous(): array
    {
        return Database::select('SELECT * FROM salles ORDER BY nom');
    }

    public static function actives(): array
    {
        return Database::select('SELECT * FROM salles WHERE maintenance = 0 ORDER BY nom');
    }

    public static function trouver(int $id): ?array
    {
        return Database::selectOne('SELECT * FROM salles WHERE id = ?', [$id]);
    }

    public static function creer(array $d): int
    {
        return Database::insert(
            'INSERT INTO salles (nom, capacite, equipements, localisation, tarif_horaire) VALUES (?, ?, ?, ?, ?)',
            [$d['nom'], (int) $d['capacite'], $d['equipements'] ?: null, $d['localisation'] ?: null, $d['tarif_horaire'] !== '' ? $d['tarif_horaire'] : null]
        );
    }

    public static function maj(int $id, array $d): void
    {
        Database::execute(
            'UPDATE salles SET nom = ?, capacite = ?, equipements = ?, localisation = ?, tarif_horaire = ? WHERE id = ?',
            [$d['nom'], (int) $d['capacite'], $d['equipements'] ?: null, $d['localisation'] ?: null, $d['tarif_horaire'] !== '' ? $d['tarif_horaire'] : null, $id]
        );
    }

    public static function basculerMaintenance(int $id): void
    {
        Database::execute('UPDATE salles SET maintenance = 1 - maintenance WHERE id = ?', [$id]);
    }
}
