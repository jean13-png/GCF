<?php
/**
 * Bloc « disponibilités » : affiché après un refus de conflit.
 * Propose les créneaux libres du jour et les prochaines dates disponibles,
 * cliquables pour remplir le formulaire automatiquement.
 * Variables attendues : $dispo (ou rien).
 */
if (empty($dispo)) {
    return;
}
$prop = $dispo['propositions'] ?? ['creneaux_libres' => [], 'dates_alternatives' => []];
?>
<div class="carte dispo-bloc">
    <h3 class="dispo-titre">
        <i class="fas fa-calendar-check"></i>
        Disponibilités de la salle <?= e($dispo['salle']) ?>
    </h3>

    <p class="dispo-intro">
        Le <?= e(format_date_fr($dispo['date'])) ?>, créneaux encore libres
        <span class="aide-champ">(cliquez pour appliquer au formulaire)</span> :
    </p>

    <?php if (!empty($prop['creneaux_libres'])): ?>
        <div class="dispo-creneaux">
            <?php foreach ($prop['creneaux_libres'] as $c): ?>
                <button type="button" class="dispo-btn"
                        data-debut="<?= e($c['debut']) ?>" data-fin="<?= e($c['fin']) ?>">
                    <i class="far fa-clock"></i>
                    <?= e(format_heure($c['debut'])) ?> – <?= e(format_heure($c['fin'])) ?>
                </button>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p><em>Aucun créneau libre ce jour-là : la salle est occupée de 07h00 à 21h00.</em></p>
    <?php endif; ?>

    <?php if (!empty($prop['dates_alternatives'])): ?>
        <p class="dispo-intro">
            Ou la salle est libre à vos heures (<?= e(format_heure($dispo['debut'])) ?> – <?= e(format_heure($dispo['fin'])) ?>)
            les jours suivants :
        </p>
        <div class="dispo-creneaux">
            <?php foreach ($prop['dates_alternatives'] as $d): ?>
                <button type="button" class="dispo-btn dispo-date" data-date="<?= e($d) ?>">
                    <i class="far fa-calendar"></i>
                    <?= e(format_date_fr($d)) ?>
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <p class="aide-champ" style="margin-top:8px;">
        La salle reste bloquée pour le créneau demandé : deux événements ne peuvent jamais se chevaucher.
    </p>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    Array.prototype.forEach.call(document.querySelectorAll('.dispo-btn'), function (b) {
        b.addEventListener('click', function () {
            var debut = b.getAttribute('data-debut');
            var fin = b.getAttribute('data-fin');
            var date = b.getAttribute('data-date');
            if (date && document.getElementById('date_reservation')) {
                document.getElementById('date_reservation').value = date;
            }
            if (debut && document.getElementById('heure_debut')) {
                document.getElementById('heure_debut').value = debut;
            }
            if (fin && document.getElementById('heure_fin')) {
                document.getElementById('heure_fin').value = fin;
            }
            var f = document.getElementById('date_reservation');
            if (f) { f.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
            b.classList.add('dispo-choisi');
        });
    });
});
</script>
