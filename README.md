# GCF · Gestion Centre de Formation

Plateforme web de gestion d'un centre de formation : **réservation de salles** avec planning et statuts pilotés par le gestionnaire (esprit palais des congrès), et **formations dans le numérique** : catalogue, sessions, inscription en ligne, espace apprenant.

Développée en **PHP pur + MySQL**, sans aucun framework ni dépendance externe : architecture MVC claire, prête à héberger sur un serveur LAMP/XAMPP classique.

---

## Fonctionnalités

### Site public
- **Page d'accueil complète** : bandeau d'informations, menu de navigation, hero avec appels à l'action
  (« S'inscrire à une formation » / « Réserver une salle »), chiffres clés en direct de la base,
  catalogue des formations avec photos, présentation des salles, parcours d'inscription en 4 étapes,
  pied de page structuré. Responsive téléphone.
- **Inscription en ligne** (`/inscription`) et **demande de réservation de salle en ligne** (`/reserver`),
  accessibles sans compte.

### Volet 1 · Réservation de salles
- Fiches salles : capacité, équipements, localisation, tarif horaire, mise en maintenance
- Dépôt de demandes de réservation à l'heure précise, **y compris depuis le site public** (`/reserver`)
- **Anti-conflit garanti** : deux réservations ne peuvent pas se chevaucher sur une même salle
- Statuts pilotés par le gestionnaire : **En attente → Réservé → Occupé → Libéré** (ou Refusée / Annulée)
- Historique complet des changements de statut (qui, quand, commentaire)
- Planning hebdomadaire visuel : salles en lignes, jours en colonnes, navigation semaine par semaine

### Volet 2 · Formations (uniquement le numérique)
- Catalogue : titre, thème, durée, niveau, public visé, tarif, visibilité en ligne
- Sessions programmées : période, horaire quotidien, places, formateur, coût
- **Réservation automatique de la salle** pour chaque jour d'une session créée
- Inscription en ligne publique avec liste déroulante des sessions ouvertes
- Vérification de l'adresse e-mail, puis création automatique du compte apprenant avec **matricule** (GCF-AAAA-NNNN) et mot de passe provisoire envoyés par e-mail
- Gestion des inscriptions : confirmées, liste d'attente, annulations

### Espace apprenant
- Connexion par **e-mail ou matricule** + mot de passe
- Mes formations à venir (dates, horaires, salle, formateur), formations terminées, nouvelles sessions ouvertes
- Profil : coordonnées, changement de mot de passe

### Notifications e-mail
Chaque étape clé notifie par e-mail : nouvelle demande de réservation (au gestionnaire), confirmation / refus / occupation / libération (au demandeur), vérification d'inscription et envoi des identifiants (au candidat).

Deux pilotes : `log` (e-mails journalisés en base, affichables dans l'administration) et `mail` (envoi réel via la fonction PHP `mail()`).

### Sécurité
- Requêtes **100 % préparées** (PDO), aucune concaténation SQL
- Protection **CSRF** sur tous les formulaires
- Échappement **XSS** systématique dans les vues
- Mots de passe **hachés avec bcrypt**
- Verrouillage du compte après **5 échecs** de connexion (15 minutes)
- Régénération de l'identifiant de session à la connexion (anti-fixation)
- Contrôle des rôles côté serveur sur chaque route
- Journal d'activité (connexions, actions sensibles)

---

## Installation (XAMPP / LAMP)

### 1. Copier le projet
Placer le dossier `GCF` dans le répertoire du serveur (par exemple `C:\xampp\htdocs\GCF` sous XAMPP).

### 2. Créer la base et les tables en une seule fois
Dans phpMyAdmin : onglet **SQL**, copier-coller tout le contenu de `sql/install.sql`, puis **Exécuter**.
Ce fichier unique crée la base `gcf`, les 9 tables, et insère les données de démonstration (salles, formations, sessions, comptes).

Ou en console :

```bash
mysql -u root < sql/install.sql
```

> Préfère-tu installer sans les données de démo ? Exécute `sql/schema.sql` seul (base + tables vierges).

### 3. Configurer la connexion
Par défaut, la configuration vise XAMPP (`root`, mot de passe vide, base `gcf`). Pour un autre environnement, créer `config/config.local.php` (non versionné) :

```php
<?php
return [
    'db' => ['user' => 'monutilisateur', 'pass' => 'monmotdepasse'],
];
```

Le pilote e-mail se règle dans `config/config.php` : `'driver' => 'log'` (journal) ou `'mail'` (envoi réel).

### 4. Ouvrir la plateforme
**`http://localhost/GCF/`** — c'est tout ! Le fichier `index.php` à la racine et le `.htaccess` servent directement la page publique d'inscription ; toutes les routes (`/login`, `/dashboard`, `/salles`…) fonctionnent depuis la racine, et les dossiers internes (`app/`, `config/`, `sql/`) sont interdits d'accès depuis le navigateur.

> L'accès direct par `http://localhost/GCF/public/` reste possible également.

---

## Comptes de démonstration

| Rôle | Identifiant | Mot de passe |
|---|---|---|
| Administrateur | `admin@gcf.bj` | `Admin@2026` |
| Gestionnaire des salles | `salles@gcf.bj` | `Gestion@2026` |
| Responsable formations | `formations@gcf.bj` | `Responsable@2026` |
| Apprenant (exemple) | `apprenant1@gmail.com` ou `GCF-2026-0001` | `Apprenant@2026` |

> Les adresses en `@gcf.bj` et `@gmail.com` sont fictives : elles servent uniquement à la démonstration locale. En pilote `log`, tous les e-mails sont consultables dans **Journal des e-mails**.

---

## Tester le parcours complet

1. **Accueil** : ouvrir `http://localhost/GCF/` : page complète avec chiffres clés, formations et salles.
2. **Inscription** : cliquer sur « S'inscrire à une formation », choisir une session et remplir le formulaire.
2. **Vérification** : ouvrir le journal des e-mails (compte administrateur) et cliquer sur le lien de vérification du mail reçu.
3. **Identifiants** : le mail suivant contient le matricule et le mot de passe provisoire. Se connecter avec.
4. **Réservation** : depuis l'accueil (bouton « Réserver une salle ») ou avec le compte gestionnaire, déposer une demande, puis la valider (Réservé), l'occuper (Occupé) et la libérer. Le planning se met à jour à chaque étape.
5. **Session de formation** : programmer une session : la salle est réservée automatiquement pour chaque jour de la période.

---

## Structure du projet

```
GCF/
├── index.php            Point d'entrée à la racine (localhost/GCF pointe dessus)
├── .htaccess            Réécriture des routes + protection des dossiers internes
├── public/              Cœur servi (index.php, assets, images) — accessible aussi en direct
├── config/              Configuration centrale + surcharge locale
├── sql/                 install.sql (tout-en-un) + schema.sql + seed
├── docs/                Cahier des charges (v1.1)
└── app/
    ├── Core/            Autoloader, routeur, PDO, auth, CSRF, validation, flash, mailer, helpers
    ├── Controllers/     10 contrôleurs (auth, dashboard, salles, réservations, planning,
    │                    formations, sessions, inscriptions, apprenant, journal e-mails)
    ├── Models/          Accès aux données (requêtes préparées uniquement)
    └── Views/           22 vues + 2 layouts (application / public)
```

## Feuille de route

- **Phase 1 (livrée)** : socle, salles, réservations, planning, catalogue, sessions, inscriptions en ligne, espace apprenant, journal e-mails
- **Phase 2** : feuilles de présence, évaluations, espace formateur
- **Phase 3** : portail entreprises (demandes sur mesure, propositions de coût)
- **Phase 4** : pilotage, statistiques, documents PDF
