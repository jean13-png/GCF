<?php
namespace App\Core;

/**
 * Authentification et session sécurisée.
 * Connexion par e-mail OU matricule + mot de passe.
 */
final class Auth
{
    private const MAX_ECHECS = 5;
    private const VERROU_MINUTES = 15;

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function id(): ?int
    {
        return self::check() ? (int) self::user()['id'] : null;
    }

    public static function role(): ?string
    {
        return self::check() ? self::user()['role'] : null;
    }

    public static function is(array $roles): bool
    {
        return self::check() && in_array(self::user()['role'], $roles, true);
    }

    /**
     * Tente une connexion. Retourne true si réussie, sinon un message d'erreur clair.
     */
    public static function attempt(string $identifiant, string $motDePasse): bool|string
    {
        $identifiant = trim($identifiant);
        if ($identifiant === '' || $motDePasse === '') {
            return 'Veuillez renseigner votre identifiant et votre mot de passe.';
        }

        $user = Database::selectOne(
            'SELECT * FROM users WHERE LCASE(email) = LCASE(?) OR matricule = ? LIMIT 1',
            [$identifiant, $identifiant]
        );

        // Message volontairement identique pour ne révéler aucun compte existant.
        if ($user === null) {
            return 'Identifiant ou mot de passe incorrect.';
        }
        if ((int) $user['actif'] !== 1) {
            return 'Ce compte est désactivé. Contactez l\'administration du centre.';
        }
        if ($user['locked_until'] !== null && strtotime($user['locked_until']) > time()) {
            return 'Compte temporairement verrouillé après plusieurs échecs. Réessayez dans quelques minutes.';
        }
        if (!password_verify($motDePasse, $user['password_hash'])) {
            self::enregistrerEchec($user);
            return 'Identifiant ou mot de passe incorrect.';
        }

        self::reinitialiserEchecs($user['id']);
        self::ouvrirSession($user);
        return true;
    }

    private static function ouvrirSession(array $user): void
    {
        session_regenerate_id(true); // anti fixation de session
        unset($user['password_hash']);
        $_SESSION['user'] = $user;
    }

    private static function enregistrerEchec(array $user): void
    {
        $echecs = (int) $user['failed_attempts'] + 1;
        $verrou = null;
        if ($echecs >= self::MAX_ECHECS) {
            $verrou = date('Y-m-d H:i:s', time() + self::VERROU_MINUTES * 60);
            $echecs = 0;
        }
        Database::execute(
            'UPDATE users SET failed_attempts = ?, locked_until = ? WHERE id = ?',
            [$echecs, $verrou, $user['id']]
        );
    }

    private static function reinitialiserEchecs(int $id): void
    {
        Database::execute('UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = ?', [$id]);
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function journaliser(string $action, string $details = ''): void
    {
        Database::insert(
            'INSERT INTO activity_logs (user_id, action, details, ip, cree_le) VALUES (?, ?, ?, ?, NOW())',
            [
                self::id(),
                $action,
                $details,
                $_SERVER['REMOTE_ADDR'] ?? '',
            ]
        );
    }
}
