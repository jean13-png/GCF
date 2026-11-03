<?php
/** Layout public (haut) : accueil, connexion, vérification e-mail. */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titrePage ?? 'GCF') ?> · GCF</title>
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body>
<div class="publique">
    <nav class="publique-nav">
        <div class="marque">
            <span class="logo">GCF</span>
            <span>Centre de formation</span>
        </div>
        <a href="<?= url('/login') ?>" style="color:#fff;font-size:13.5px;font-weight:600;">Espace personnel →</a>
    </nav>
    <div class="publique-corps">
        <div class="carte-publique">
            <?php foreach (App\Core\Flash::tous() as $flash): ?>
                <div class="flash flash-<?= e($flash['type']) ?>" style="margin-bottom:14px;"><?= e($flash['message']) ?></div>
            <?php endforeach; ?>
