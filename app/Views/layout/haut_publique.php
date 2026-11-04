<?php
/**
 * Layout public (haut) : topbar contact + navigation + flash.
 * Utilisé par l'accueil, l'inscription, la demande de salle, la connexion.
 */
$cheminCourant = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$routeActive = static function (string $route) use ($cheminCourant): string {
    return rtrim($cheminCourant, '/') === rtrim(base_path() . $route, '/') ? ' class="actif"' : '';
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Centre de formation dans le numérique : catalogue de formations, inscription en ligne et réservation de salles équipées.">
<title><?= e($titrePage ?? 'Accueil') ?> · GCF · Centre de formation</title>
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body>
<div class="publique">

    <!-- Bandeau d'informations -->
    <div class="topbar-info">
        <div class="conteneur topbar-info-inner">
            <div class="topbar-info-gauche">
                <span>✉ &nbsp;<?= e(config('mail.from')) ?></span>
                <span class="separe">📅 &nbsp;Du lundi au samedi · 8h à 18h</span>
            </div>
            <div class="topbar-info-droite">Centre de formation professionnelle · Numérique</div>
        </div>
    </div>

    <!-- Navigation principale -->
    <header class="navbar-publique">
        <div class="conteneur navbar-inner">
            <a class="marque" href="<?= url('/') ?>">
                <span class="logo">GCF</span>
                <span class="marque-texte">Centre de<br>Formation</span>
            </a>

            <nav class="menu-public" aria-label="Navigation du site">
                <a href="<?= url('/') ?>"<?= $routeActive('/') ?>>Accueil</a>
                <a href="<?= url('/#formations') ?>">Nos formations</a>
                <a href="<?= url('/#salles') ?>">Nos salles</a>
                <a href="<?= url('/#etapes') ?>">Comment ça marche</a>
                <a href="<?= url('/#contact') ?>">Contact</a>
                <div class="menu-public-actions">
                    <a class="btn btn-discret" href="<?= url('/login') ?>">Espace personnel</a>
                    <a class="btn btn-primaire" href="<?= url('/inscription') ?>">S'inscrire</a>
                </div>
            </nav>

            <button class="burger" type="button" aria-label="Ouvrir le menu">☰</button>
        </div>
    </header>

    <main class="publique-corps">
        <?php foreach (App\Core\Flash::tous() as $flash): ?>
            <div class="conteneur" style="padding-top:16px;">
                <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            </div>
        <?php endforeach; ?>
