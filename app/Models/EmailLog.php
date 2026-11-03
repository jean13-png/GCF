<?php
namespace App\Models;

use App\Core\Database;

final class EmailLog
{
    public static function derniers(int $limite = 150): array
    {
        return Database::select('SELECT * FROM emails ORDER BY id DESC LIMIT ' . max(1, min(500, $limite)));
    }
}
