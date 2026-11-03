<?php $titrePage = 'Journal des e-mails'; ?>
<?php require dirname(__DIR__) . '/layout/haut.php'; ?>

<div class="carte">
    <div class="carte-entete">
        <h3><?= count($emails) ?> e-mail<?= count($emails) > 1 ? 's' : '' ?> · <?= config('mail.driver') === 'log' ? 'mode journal (aucun envoi réel)' : 'envoi réel actif' ?></h3>
    </div>

    <?php if (config('mail.driver') === 'log'): ?>
        <div class="encart-info">
            Le pilote d'e-mails est réglé sur <strong>journal</strong> : les messages sont enregistrés ici au lieu d'être
            réellement envoyés. C'est le mode recommandé en local et pour la démonstration. Pour activer l'envoi réel,
            réglez <code>mail.driver</code> sur <code>mail</code> dans la configuration.
        </div>
    <?php endif; ?>

    <?php if ($emails === []): ?>
        <div class="vide">Aucun e-mail journalisé pour le moment.</div>
    <?php else: ?>
        <?php foreach ($emails as $m): ?>
            <details class="mail">
                <summary>
                    <?= e($m['sujet']) ?>
                    <span class="meta">→ <?= e($m['destinataire']) ?> · <?= e(date('d/m/Y H:i', strtotime($m['cree_le']))) ?></span>
                </summary>
                <div class="corps"><?= $m['corps'] ?></div>
            </details>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require dirname(__DIR__) . '/layout/bas.php'; ?>
