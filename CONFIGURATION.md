# Configuration GCF - Récapitulatif

## ✅ Configuration Terminée

### 1. Base de données MySQL
- **Serveur** : 127.0.0.1:3306
- **Base** : `gcf`
- **Utilisateur** : `root`
- **Mot de passe** : Configuré dans `config/config.php`

### 2. Tables créées
- ✅ users (5 utilisateurs de démonstration)
- ✅ salles (5 salles)
- ✅ formations (8 formations)
- ✅ sessions_formation (4 sessions planifiées)
- ✅ inscriptions
- ✅ reservations
- ✅ reservation_events
- ✅ emails
- ✅ activity_logs

### 3. Apache Configuration
- ✅ mod_rewrite activé
- ✅ AllowOverride All pour /var/www/html/GCF
- ✅ Fichier .htaccess configuré
- ✅ Router PHP corrigé pour supporter les sous-répertoires

### 4. Accès à l'application
- **URL** : http://localhost/GCF/
- **Page d'inscription publique** : http://localhost/GCF/
- **Page de connexion** : http://localhost/GCF/login

### 5. Comptes de démonstration

#### Administrateur
- **Email** : tossajean13@gmail.com
- **Mot de passe** : TOSjea13#
- **Rôle** : ADMIN

#### Gestionnaire des salles
- **Email** : tossajean13salles@gmail.com
- **Mot de passe** : Gestion@2026
- **Rôle** : GESTIONNAIRE

#### Responsable formations
- **Email** : tossajean13formations@gmail.com
- **Mot de passe** : Responsable@2026
- **Rôle** : RESPONSABLE

#### Apprenant 1
- **Email** : apprenant1@gmail.com
- **Matricule** : GCF-2026-0001
- **Mot de passe** : Apprenant@2026
- **Rôle** : APPRENANT

#### Apprenant 2
- **Email** : apprenant2@gmail.com
- **Matricule** : GCF-2026-0002
- **Mot de passe** : Apprenant@2026
- **Rôle** : APPRENANT

### 6. Fonctionnalités disponibles

#### Pour tous (public)
- Inscription en ligne aux formations
- Vérification d'email

#### Pour l'administrateur
- Tableau de bord
- Gestion des salles (CRUD)
- Gestion des formations (CRUD)
- Gestion des sessions de formation
- Gestion des réservations
- Gestion des inscriptions
- Journal des emails
- Planning hebdomadaire

#### Pour le gestionnaire
- Tableau de bord
- Consultation des salles
- Gestion des réservations
- Mise en maintenance des salles
- Planning hebdomadaire

#### Pour le responsable formations
- Tableau de bord
- Gestion des formations
- Gestion des sessions
- Gestion des inscriptions
- Planning hebdomadaire

#### Pour l'apprenant
- Mon espace personnel
- Mes formations inscrites
- Mon profil

### 7. Structure des fichiers
```
GCF/
├── app/
│   ├── Controllers/      # Contrôleurs MVC
│   ├── Core/            # Classes système (Router, Database, Auth...)
│   ├── Models/          # Modèles de données
│   └── Views/           # Vues HTML/PHP
├── config/
│   └── config.php       # Configuration (BDD, email...)
├── public/
│   ├── assets/          # CSS, JS, images
│   └── index.php        # Point d'entrée de l'application
├── sql/
│   ├── install.sql      # Script d'installation complet
│   ├── schema.sql       # Schéma seul
│   └── seed.sql         # Données de démonstration
├── .htaccess            # Réécriture d'URL Apache
└── index.php            # Redirecteur vers public/

```

### 8. Commandes utiles

#### Réinitialiser la base de données
```bash
mysql -u root -p632562 < sql/install.sql
```

#### Redémarrer Apache
```bash
sudo systemctl restart apache2
```

#### Voir les logs Apache
```bash
sudo tail -f /var/log/apache2/error.log
```

#### Tester la connexion à la base
```bash
cd /var/www/html/GCF
php -r "
require 'app/Core/Autoloader.php';
require 'app/Core/Helpers.php';
use App\Core\Database;
\$users = Database::select('SELECT COUNT(*) as total FROM users');
echo 'Utilisateurs: ' . \$users[0]['total'] . PHP_EOL;
"
```

## 🎉 Tout est prêt !

L'application GCF (Gestion Centre de Formation) est maintenant entièrement configurée et opérationnelle.

Vous pouvez accéder à : **http://localhost/GCF/**
