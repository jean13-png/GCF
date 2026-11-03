<?php
declare(strict_types=1);

/**
 * Point d'entrée à la racine du projet.
 * http://localhost/GCF pointe directement sur la plateforme,
 * sans avoir à taper /public dans l'adresse.
 */

define('DEPUIS_RACINE', true);

/*
 * Serveur de développement PHP (php -S) : on sert les fichiers statiques
 * directement (assets), mais on interdit l'accès aux dossiers internes.
 */
if (PHP_SAPI === 'cli-server') {
    $chemin = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    if (preg_match('#^/(app|config|sql|docs)(/|$)#', $chemin)) {
        http_response_code(404);
        exit('Page introuvable');
    }
    if ($chemin !== '/' && is_file(__DIR__ . $chemin)) {
        return false; // le serveur intégré sert le fichier tel quel
    }
}

require __DIR__ . '/public/index.php';
