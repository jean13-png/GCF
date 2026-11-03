<?php
/**
 * Configuration centrale de la plateforme GCF.
 * Copier ce fichier en config.local.php pour surcharger sans commiter.
 */
return [
    'app' => [
        'nom'        => 'GCF',
        'nom_complet'=> 'Gestion Centre de Formation',
        'dev'        => true,           // false en production
    ],
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => '3306',
        'name'    => 'gcf',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],
    'mail' => [
        // 'log'  : les e-mails sont journalisés dans la base (idéal en local / démo)
        // 'mail' : envoi réel via la fonction mail() de PHP (hébergement configuré)
        'driver'    => 'log',
        'from'      => 'contact@centredesformation.bj',
        'from_name' => 'Centre de formation',
    ],
];
