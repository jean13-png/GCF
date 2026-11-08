<?php
namespace App\Models;

use App\Core\Database;

final class MessageContact
{
    /**
     * Enregistre un message envoyé depuis la page Contact du site public.
     */
    public static function creer(array $d): int
    {
        return Database::insert(
            'INSERT INTO messages_contact (nom, email, telephone, sujet, message, cree_le) VALUES (?, ?, ?, ?, ?, NOW())',
            [$d['nom'], $d['email'], $d['telephone'] ?: null, $d['sujet'], $d['message']]
        );
    }

    /**
     * Messages les plus récents d'abord, non traités en tête.
     */
    public static function tous(): array
    {
        return Database::select(
            "SELECT * FROM messages_contact ORDER BY (statut = 'NOUVEAU') DESC, cree_le DESC"
        );
    }

    public static function trouver(int $id): ?array
    {
        return Database::selectOne('SELECT * FROM messages_contact WHERE id = ?', [$id]);
    }

    public static function marquerTraite(int $id): void
    {
        Database::execute("UPDATE messages_contact SET statut = 'TRAITE' WHERE id = ?", [$id]);
    }

    public static function compterNouveaux(): int
    {
        return (int) Database::selectOne("SELECT COUNT(*) AS n FROM messages_contact WHERE statut = 'NOUVEAU'")['n'];
    }

    /**
     * Adresses e-mail des administrateurs, destinataires des notifications.
     */
    public static function emailsAdministrateurs(): array
    {
        return array_column(
            Database::select("SELECT email FROM users WHERE role = 'ADMIN' ORDER BY id"),
            'email'
        );
    }
}
