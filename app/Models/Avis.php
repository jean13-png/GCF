<?php
namespace App\Models;

use App\Core\Database;
use DateTimeImmutable;
use DateTimeZone;

/** Avis après réservation : lien personnel, soumission publique, suivi admin. */
final class Avis
{
    /** Durée de validité du lien envoyé au demandeur. */
    public const DUREE_JOURS = 30;

    /** Date et heure courantes dans le fuseau du centre. */
    public static function maintenant(): string
    {
        return (new DateTimeImmutable('now', self::fuseau()))->format('Y-m-d H:i:s');
    }

    public static function aujourdhui(): string
    {
        return (new DateTimeImmutable('now', self::fuseau()))->format('Y-m-d');
    }

    public static function expiration(): string
    {
        return (new DateTimeImmutable('now', self::fuseau()))
            ->modify('+' . self::DUREE_JOURS . ' days')->format('Y-m-d H:i:s');
    }

    private static function fuseau(): DateTimeZone
    {
        return new DateTimeZone((string) config('app.fuseau', 'Africa/Porto-Novo'));
    }

    /**
     * Réservations dont le dernier jour est aujourd'hui, au statut éligible,
     * qui n'ont pas encore reçu leur e-mail d'avis (ou dont l'envoi a échoué).
     */
    public static function candidates(string $depuis, string $aujourdhui): array
    {
        return Database::select(
            "SELECT r.id, r.evenement, r.demandeur_nom, r.demandeur_email,
                    r.date_reservation, r.date_fin, r.heure_debut, r.heure_fin,
                    s.nom AS salle_nom, a.id AS avis_id, a.token, a.envoye_le
             FROM reservations r
             JOIN salles s ON s.id = r.salle_id
             LEFT JOIN avis a ON a.reservation_id = r.id
             WHERE r.statut IN ('CONFIRMEE', 'OCCUPEE', 'TERMINEE')
               AND COALESCE(r.date_fin, r.date_reservation) = ?
               AND COALESCE(r.date_fin, r.date_reservation) >= ?
               AND r.demandeur_email IS NOT NULL AND r.demandeur_email <> ''
               AND (a.id IS NULL OR a.envoye_le IS NULL)
             ORDER BY r.heure_fin, r.id",
            [$aujourdhui, $depuis]
        );
    }

    public static function creer(int $reservationId, string $token, string $expire): int
    {
        return Database::insert(
            'INSERT INTO avis (reservation_id, token, token_expire_le) VALUES (?, ?, ?)',
            [$reservationId, $token, $expire]
        );
    }

    public static function marquerEnvoye(int $avisId, string $quand): void
    {
        Database::execute('UPDATE avis SET envoye_le = ? WHERE id = ?', [$quand, $avisId]);
    }

    /** Vrai si le dernier e-mail journalisé pour ce destinataire et ce sujet n'est pas un échec. */
    public static function mailNonEchec(string $destinataire, string $sujet): bool
    {
        $ligne = Database::selectOne(
            'SELECT statut FROM emails WHERE destinataire = ? AND sujet = ? ORDER BY id DESC LIMIT 1',
            [$destinataire, $sujet]
        );
        return $ligne !== null && $ligne['statut'] !== 'ECHEC';
    }

    public static function trouverParToken(string $token): ?array
    {
        return Database::selectOne(
            'SELECT a.*, r.evenement, r.demandeur_nom, r.demandeur_email, r.date_reservation,
                    r.date_fin, r.heure_debut, r.heure_fin, s.nom AS salle_nom
             FROM avis a
             JOIN reservations r ON r.id = a.reservation_id
             JOIN salles s ON s.id = r.salle_id
             WHERE a.token = ?',
            [$token]
        );
    }

    /** Enregistre l'avis une seule fois : renvoie false si déjà soumis ou lien expiré. */
    public static function soumettre(string $token, ?int $note, string $positifs, string $negatifs, string $general): bool
    {
        $maintenant = self::maintenant();
        return Database::execute(
            'UPDATE avis
             SET note = ?, points_positifs = ?, points_negatifs = ?, avis_general = ?, soumis_le = ?
             WHERE token = ? AND soumis_le IS NULL AND token_expire_le >= ?',
            [$note, $positifs !== '' ? $positifs : null, $negatifs !== '' ? $negatifs : null,
             $general, $maintenant, $token, $maintenant]
        ) === 1;
    }

    /** Liste admin : seulement les avis dont le lien a été envoyé. */
    public static function liste(array $filtres): array
    {
        $sql = "SELECT a.*, r.evenement, r.demandeur_nom, r.demandeur_organisation, r.demandeur_email,
                       r.date_reservation, r.heure_debut, r.heure_fin, s.nom AS salle_nom
                FROM avis a
                JOIN reservations r ON r.id = a.reservation_id
                JOIN salles s ON s.id = r.salle_id
                WHERE a.envoye_le IS NOT NULL";
        $params = [];
        if (!empty($filtres['salle_id'])) {
            $sql .= ' AND r.salle_id = ?';
            $params[] = (int) $filtres['salle_id'];
        }
        if (!empty($filtres['du'])) {
            $sql .= ' AND r.date_reservation >= ?';
            $params[] = $filtres['du'];
        }
        if (($filtres['etat'] ?? '') === 'soumis') {
            $sql .= ' AND a.soumis_le IS NOT NULL';
        } elseif (($filtres['etat'] ?? '') === 'attente') {
            $sql .= ' AND a.soumis_le IS NULL';
        }
        if (!empty($filtres['note']) && in_array((int) $filtres['note'], [1, 2, 3, 4, 5], true)) {
            $sql .= ' AND a.note = ?';
            $params[] = (int) $filtres['note'];
        }
        $sql .= ' ORDER BY r.date_reservation DESC, r.heure_debut DESC LIMIT 300';
        return Database::select($sql, $params);
    }

    /** Compteurs de la page admin. */
    public static function stats(): array
    {
        return Database::selectOne(
            'SELECT COUNT(*) AS envoyes,
                    SUM(soumis_le IS NOT NULL) AS soumis,
                    ROUND(AVG(note), 1) AS moyenne
             FROM avis WHERE envoye_le IS NOT NULL'
        ) ?? ['envoyes' => 0, 'soumis' => 0, 'moyenne' => null];
    }

    /** Corps HTML du mail envoyé au demandeur, avec son lien personnel. */
    public static function corpsDemande(array $r, string $lien): string
    {
        return '<p>Bonjour ' . e($r['demandeur_nom']) . ',</p>'
            . '<p>Merci d\'avoir utilisé notre salle pour <strong>' . e($r['evenement']) . '</strong> '
            . '(' . e($r['salle_nom']) . ', ' . e(format_date_fr($r['date_reservation'])) . ', '
            . e(format_heure($r['heure_debut'])) . ' – ' . e(format_heure($r['heure_fin'])) . ').</p>'
            . '<p>Votre avis nous aide à améliorer nos services : qu\'est-ce qui a bien fonctionné, '
            . 'qu\'est-ce qui pourrait être amélioré ? Cela ne prend qu\'une minute.</p>'
            . \App\Core\Mailer::bouton($lien, 'Donner mon avis')
            . '<p style="color:#667;font-size:13px;">Ce lien est personnel et reste valable '
            . self::DUREE_JOURS . ' jours.</p>';
    }
}
