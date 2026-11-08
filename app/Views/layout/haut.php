<?php
/**
 * Layout application (haut) : sidebar + topbar + flash.
 * Variables attendues : $titrePage (string), éventuellement $user.
 */
$user = auth();
$roleLibelles = [
    'ADMIN' => 'Administrateur',
    'GESTIONNAIRE' => 'Gestionnaire des salles',
    'RESPONSABLE' => 'Responsable formations',
    'FORMATEUR' => 'Formateur',
    'APPRENANT' => 'Apprenant',
];
$role = $user['role'] ?? '';
$estAdmin = $role === 'ADMIN';
$estSalle = in_array($role, ['ADMIN', 'GESTIONNAIRE', 'RESPONSABLE'], true);
$estFormation = in_array($role, ['ADMIN', 'RESPONSABLE'], true);

$cheminCourant = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$actif = static function (string $route) use ($cheminCourant): string {
    return rtrim($cheminCourant, '/') === rtrim(base_path() . $route, '/') ? ' class="actif"' : '';
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titrePage ?? 'Accueil') ?> · Centre de formation</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body>
<div class="app">

    <nav class="sidebar" aria-label="Navigation principale">
        <div class="sidebar-marque">
            <span class="logo">CF</span>
            <h1>Centre de formation</h1>
            <p>Gestion · Salles · Formations</p>
        </div>
        <div class="sidebar-nav">
            <?php if ($role === 'APPRENANT'): ?>
                <a href="<?= url('/mon-espace') ?>"<?= $actif('/mon-espace') ?>><span class="ic"><i class="fas fa-home"></i></span> Mon espace</a>
                <a href="<?= url('/mon-profil') ?>"<?= $actif('/mon-profil') ?>><span class="ic"><i class="fas fa-user"></i></span> Mon profil</a>
            <?php else: ?>
                <div class="groupe">Pilotage</div>
                <a href="<?= url('/dashboard') ?>"<?= $actif('/dashboard') ?>><span class="ic">▤</span> Tableau de bord</a>
                <?php if ($estAdmin): ?>
                    <a href="<?= url('/emails') ?>"<?= $actif('/emails') ?>><span class="ic">✉</span> Journal des e-mails</a>
                <?php endif; ?>

                <?php if ($estSalle): ?>
                    <div class="groupe">Réservation de salles</div>
                    <a href="<?= url('/salles') ?>"<?= $actif('/salles') ?>><span class="ic"><i class="fas fa-door-open"></i></span> Salles</a>
                    <a href="<?= url('/reservations') ?>"<?= $actif('/reservations') ?>><span class="ic"><i class="fas fa-check"></i></span> Réservations</a>
                <?php endif; ?>
                <a href="<?= url('/planning') ?>"<?= $actif('/planning') ?>><span class="ic"><i class="fas fa-calendar-alt"></i></span> Planning</a>

                <?php if ($estSalle || $role === 'FORMATEUR'): ?>
                    <div class="groupe">Formations</div>
                <?php endif; ?>
                <?php if ($estSalle): ?>
                    <a href="<?= url('/formations') ?>"<?= $actif('/formations') ?>><span class="ic">◈</span> Catalogue</a>
                <?php endif; ?>
                <?php if (in_array($role, ['ADMIN', 'RESPONSABLE', 'FORMATEUR'], true)): ?>
                    <a href="<?= url('/sessions') ?>"<?= $actif('/sessions') ?>><span class="ic">◇</span> Sessions</a>
                <?php endif; ?>
                <?php if ($estFormation): ?>
                    <a href="<?= url('/inscriptions') ?>"<?= $actif('/inscriptions') ?>><span class="ic">✎</span> Inscriptions</a>
                <?php endif; ?>

                <div class="groupe">Mon compte</div>
                <a href="<?= url('/mon-profil') ?>"<?= $actif('/mon-profil') ?>><span class="ic"><i class="fas fa-user"></i></span> Mon profil</a>
            <?php endif; ?>
        </div>
        <div class="sidebar-pied">Centre de formation · ONG-GAS</div>
    </nav>

    <div class="voile"></div>

    <div class="contenu-app">
        <header class="topbar">
            <div style="display:flex;align-items:center;gap:12px;">
                <button class="burger" type="button" aria-label="Ouvrir le menu"><i class="fas fa-bars"></i></button>
                <h2><?= e($titrePage ?? '') ?></h2>
            </div>
            <div class="topbar-droite">
                <div class="user-chip">
                    <div class="infos">
                        <div class="nom"><?= e($user['prenom'] ?? '') ?> <?= e($user['nom'] ?? '') ?></div>
                        <div class="role"><?= e($roleLibelles[$role] ?? $role) ?></div>
                    </div>
                    <div class="avatar"><?= e(initiales($user ?? [])) ?></div>
                </div>
                <form method="post" action="<?= url('/logout') ?>">
                    <?= csrf_champ() ?>
                    <button class="btn-deconnexion" type="submit">Déconnexion</button>
                </form>
            </div>
        </header>

        <main class="page">
            <?php foreach (App\Core\Flash::tous() as $flash): ?>
                <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endforeach; ?>
