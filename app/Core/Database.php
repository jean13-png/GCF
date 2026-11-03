<?php
namespace App\Core;

use PDO;

/**
 * Connexion PDO unique. Toutes les requêtes de la plateforme passent
 * par des requêtes préparées : aucune concaténation SQL, jamais.
 */
final class Database
{
    private static ?PDO $instance = null;

    public static function pdo(): PDO
    {
        if (self::$instance === null) {
            $config = config('db');
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['name'],
                $config['charset']
            );
            self::$instance = new PDO($dsn, $config['user'], $config['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
        return self::$instance;
    }

    /** SELECT retournant toutes les lignes. */
    public static function select(string $sql, array $params = []): array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** SELECT retournant la première ligne ou null. */
    public static function selectOne(string $sql, array $params = []): ?array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        $ligne = $stmt->fetch();
        return $ligne === false ? null : $ligne;
    }

    /** INSERT / UPDATE / DELETE : retourne le nombre de lignes affectées. */
    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /** INSERT avec retour de l'identifiant créé. */
    public static function insert(string $sql, array $params = []): int
    {
        self::execute($sql, $params);
        return (int) self::pdo()->lastInsertId();
    }
}
