<?php $titrePage = 'Messages de contact'; ?>
<?php require dirname(__DIR__) . '/layout/haut.php'; ?>

<div class="carte">
    <div class="carte-entete">
        <h3>
            <?= count($messages) ?> message<?= count($messages) > 1 ? 's' : '' ?>
            · <?= (int) $nbNouveaux ?> nouveau<?= (int) $nbNouveaux > 1 ? 'x' : '' ?>
        </h3>
        <p class="sous-titre" style="margin:4px 0 0;">
            Messages envoyés depuis la page Contact du site public. Les non traités apparaissent en premier.
        </p>
    </div>

    <?php if ($messages === []): ?>
        <div class="vide">
            Aucun message pour le moment.<br>
            Les messages envoyés depuis la page Contact du site apparaîtront ici.
        </div>
    <?php else: ?>
        <div class="tableau-wrap">
            <table class="tableau tableau-messages">
                <thead>
                    <tr>
                        <th>Reçu le</th>
                        <th>Expéditeur</th>
                        <th>Sujet et message</th>
                        <th>Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $m): ?>
                        <tr class="<?= $m['statut'] === 'NOUVEAU' ? 'ligne-nouveau' : '' ?>">
                            <td class="nowrap">
                                <?= e(date('d/m/Y', strtotime((string) $m['cree_le']))) ?>
                                <div class="secondaire"><?= e(date('H:i', strtotime((string) $m['cree_le']))) ?></div>
                            </td>
                            <td>
                                <strong><?= e($m['nom']) ?></strong>
                                <div class="secondaire"><?= e($m['email']) ?></div>
                                <?php if (!empty($m['telephone'])): ?>
                                    <div class="secondaire"><?= e($m['telephone']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="msg-cellule">
                                <strong><?= e($m['sujet']) ?></strong>
                                <div class="msg-contenu"><?= nl2br(e($m['message'])) ?></div>
                            </td>
                            <td>
                                <?php if ($m['statut'] === 'NOUVEAU'): ?>
                                    <span class="badge badge-attente">Nouveau</span>
                                <?php else: ?>
                                    <span class="badge badge-termine">Traité</span>
                                <?php endif; ?>
                            </td>
                            <td class="nowrap">
                                <a class="btn btn-petit btn-discret" href="mailto:<?= e($m['email']) ?>?subject=<?= e('Re: ' . $m['sujet']) ?>">
                                    <i class="fas fa-reply" aria-hidden="true"></i> Répondre
                                </a>
                                <?php if ($m['statut'] === 'NOUVEAU'): ?>
                                    <form method="post" action="<?= url('/messages/' . (int) $m['id'] . '/traiter') ?>" style="display:inline;">
                                        <?= \App\Core\CSRF::champ() ?>
                                        <button type="submit" class="btn btn-petit btn-primaire" data-confirm="Marquer ce message comme traité ?">
                                            <i class="fas fa-check" aria-hidden="true"></i> Traiter
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require dirname(__DIR__) . '/layout/bas.php'; ?>
