<?php
namespace App\Core;

final class Flash
{
    public static function set(string $type, string $message): void
    {
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
    }

    public static function tous(): array
    {
        $messages = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $messages;
    }

    public static function setOld(array $donnees): void
    {
        unset($donnees['_token'], $donnees['password'], $donnees['password_actuel'], $donnees['password_confirmation']);
        $_SESSION['old'] = $donnees;
    }

    public static function old(string $cle, mixed $defaut = ''): mixed
    {
        $valeur = $_SESSION['old'][$cle] ?? $defaut;
        return $valeur === '' ? $defaut : $valeur;
    }

    public static function viderOld(): void
    {
        unset($_SESSION['old']);
    }
}
