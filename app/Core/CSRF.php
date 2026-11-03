<?php
namespace App\Core;

final class CSRF
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    public static function verify(): bool
    {
        $attendu = $_SESSION['csrf'] ?? '';
        $recu    = $_POST['_token'] ?? '';
        return $attendu !== '' && hash_equals($attendu, $recu);
    }

    public static function champ(): string
    {
        return '<input type="hidden" name="_token" value="' . self::token() . '">';
    }
}
