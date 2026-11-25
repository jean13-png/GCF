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
        // Adresse publique absolue de la plateforme (ex. https://domaine/GCF).
        // Obligatoire pour les liens des e-mails d'avis : vide = aucun envoi.
        'url'        => '',
        'fuseau'     => 'Africa/Porto-Novo',
    ],
    // Envoi des avis : date à partir de laquelle les réservations sont concernées
    // (ex. '2026-09-05'). null = désactivé. Jamais de rétroactivité.
    'avis' => [
        'depuis' => null,
    ],
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => '3306',
        'name'    => 'gcf',
        'user'    => 'root',
        'pass'    => '632562',
        'charset' => 'utf8mb4',
    ],
    'mail' => [
        // 'log'  : les e-mails sont journalisés dans la base (idéal en local / démo)
        // 'mail' : envoi réel via la fonction mail() de PHP (hébergement configuré)
        'driver'    => 'log',
        'from'      => 'contact@centredesformation.bj',
        'from_name' => 'Centre de formation',
    ],
    'planning' => [
        // Plage horaire affichée dans les grilles du planning ( configurable ).
        'ouverture' => 6,
        'fermeture' => 22,
        // Nombre de semaines chargées d'avance dans la vue Ressources.
        'semaines_ressources' => 8,
    ],
];
