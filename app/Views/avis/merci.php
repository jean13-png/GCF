<?php $titrePage = 'Merci'; $descPage = 'Votre avis a bien été enregistré.'; ?>
<?php require dirname(__DIR__) . '/layout/haut_publique.php'; ?>

<div class="conteneur page-publique">
    <div class="entete-page-publique">
        <h1>Merci pour votre avis</h1>
        <p>
            Votre avis sur <strong><?= e($avis['evenement']) ?></strong> (<?= e($avis['salle_nom']) ?>)
            a bien été enregistré. Il est transmis au centre de formation.
        </p>
        <p>Nous vous remercions du temps accordé et espérons vous accueillir à nouveau.</p>
    </div>
</div>

<?php require dirname(__DIR__) . '/layout/bas_publique.php'; ?>
