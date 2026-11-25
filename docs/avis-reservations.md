# Avis après réservation

## Fonctionnement

- Pour chaque réservation confirmée, occupée ou terminée, le demandeur reçoit automatiquement, le **dernier jour de la réservation à partir de 14h** (ou à l'heure de fin si elle est plus tardive), un e-mail avec un **lien personnel** (valable 30 jours, sans connexion).
- Il renseigne : une note de 1 à 5 étoiles (facultative), ce qui a bien fonctionné, ce qui pourrait être amélioré, un avis général (obligatoire). Une seule soumission par réservation.
- Après soumission : l'avis apparaît dans **Avis** (menu admin, rôles ADMIN, GESTIONNAIRE, RESPONSABLE) et une copie part à l'adresse de l'ONG (`mail.from`).

## Mise en service

1. Sauvegarder la base : `mysqldump gcf > sauvegarde.sql`.
2. Appliquer la migration une seule fois : `mysql gcf < sql/migration_avis.sql`.
3. Renseigner dans `config/config.local.php` :
   ```php
   'app'  => ['url' => 'https://ADRESSE_PUBLIQUE_DE_GCF'],   // sans / final
   'avis' => ['depuis' => 'AAAA-MM-JJ'],                     // premier jour concerné
   ```
   Tant que `url` est vide ou `depuis` est `null`, le script n'envoie rien.
4. Planifier le script (toutes les 10 minutes) :
   ```
   */10 * * * * root /usr/bin/flock -n /var/lock/gcf-avis.lock /usr/bin/php /var/www/gcf/bin/envoyer_avis.php >> /var/log/gcf-avis.log 2>&1
   ```

## Exploitation

- Simulation sans envoi : `php bin/envoyer_avis.php --simulation`.
- Un e-mail en échec n'est pas marqué comme envoyé : il est repris au passage suivant.
- Le script ne concerne que les réservations dont le dernier jour est la date du jour ou un jour postérieur à `avis.depuis` : pas de rétroactivité.

## Fichiers

- `sql/migration_avis.sql` : table `avis` (une ligne par réservation).
- `app/Models/Avis.php` : requêtes et corps du mail au demandeur.
- `app/Controllers/AvisController.php` : page publique, soumission, espace admin.
- `app/Views/avis/` : formulaire public, page de remerciement, liste admin.
- `bin/envoyer_avis.php` : envoi planifié.
- `public/index.php` : routes `/avis/{token}` (public) et `/avis` (admin).
- `config/config.php` : clés `app.url`, `app.fuseau`, `avis.depuis`.
