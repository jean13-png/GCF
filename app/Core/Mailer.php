<?php
namespace App\Core;

/**
 * Envoi des e-mails de la plateforme.
 * Driver "log" : chaque e-mail est journalisé en base (mode local / démonstration).
 * Driver "mail" : envoi réel via la fonction mail() de PHP (hébergement configuré).
 */
final class Mailer
{
    public static function envoyer(string $destinataire, string $sujet, string $contenuHtml): void
    {
        $html = self::enveloppe($sujet, $contenuHtml);
        $statut = 'LOG';

        if (config('mail.driver') === 'mail') {
            $entetes = 'From: ' . config('mail.from_name') . ' <' . config('mail.from') . ">\r\n"
                . "Content-Type: text/html; charset=UTF-8\r\n";
            $statut = mail($destinataire, $sujet, $html, $entetes) ? 'ENVOYE' : 'ECHEC';
        }

        Database::insert(
            'INSERT INTO emails (destinataire, sujet, corps, statut, cree_le) VALUES (?, ?, ?, ?, NOW())',
            [$destinataire, $sujet, $html, $statut]
        );
    }

    private static function enveloppe(string $sujet, string $contenu): string
    {
        $nom = e(config('app.nom_complet'));
        return '<!DOCTYPE html><html lang="fr"><body style="margin:0;background:#f4f6fb;padding:24px;font-family:Arial,Helvetica,sans-serif;color:#222;">'
            . '<div style="max-width:560px;margin:0 auto;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 8px 24px rgba(20,30,80,.10);">'
            . '<div style="background:#1c2b6b;color:#fff;padding:20px 28px;"><div style="font-size:20px;font-weight:bold;">' . $nom . '</div>'
            . '<div style="font-size:13px;opacity:.85;">Plateforme de gestion du centre de formation</div></div>'
            . '<div style="padding:28px;"><h2 style="margin:0 0 14px;font-size:18px;color:#1c2b6b;">' . e($sujet) . '</h2>'
            . '<div style="font-size:14px;line-height:1.7;">' . $contenu . '</div></div>'
            . '<div style="padding:16px 28px;background:#f8f9fc;font-size:12px;color:#8892a8;">'
            . 'Cet e-mail vous a été envoyé automatiquement par la plateforme ' . $nom . '.</div>'
            . '</div></body></html>';
    }

    /** Bloc HTML réutilisable : bouton d'action cliquable. */
    public static function bouton(string $lien, string $texte): string
    {
        return '<p style="text-align:center;margin:22px 0;"><a href="' . e($lien) . '" '
            . 'style="background:#b3121a;color:#fff;padding:12px 26px;border-radius:10px;text-decoration:none;font-weight:bold;display:inline-block;">'
            . e($texte) . '</a></p>';
    }
}
