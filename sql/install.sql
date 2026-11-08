-- ══════════════════════════════════════════════════════════════════
--  GCF · Gestion Centre de Formation
--  Schéma de la base MySQL (utf8mb4, InnoDB, clés étrangères)
-- ══════════════════════════════════════════════════════════════════

CREATE DATABASE IF NOT EXISTS gcf CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gcf;

-- Comptes utilisateurs (tous rôles confondus ; matricule pour les apprenants)
CREATE TABLE users (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    matricule        VARCHAR(20)  DEFAULT NULL,
    nom              VARCHAR(100) NOT NULL,
    prenom           VARCHAR(100) NOT NULL,
    email            VARCHAR(190) NOT NULL,
    telephone        VARCHAR(30)  DEFAULT NULL,
    role             ENUM('ADMIN','GESTIONNAIRE','RESPONSABLE','FORMATEUR','APPRENANT') NOT NULL DEFAULT 'APPRENANT',
    password_hash    VARCHAR(255) NOT NULL,
    failed_attempts  TINYINT      NOT NULL DEFAULT 0,
    locked_until     DATETIME     DEFAULT NULL,
    actif            TINYINT(1)   NOT NULL DEFAULT 1,
    cree_le          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_users_matricule (matricule),
    UNIQUE KEY uk_users_email (email)
) ENGINE=InnoDB;

-- Salles du centre
CREATE TABLE salles (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    nom           VARCHAR(120) NOT NULL,
    capacite      INT          NOT NULL DEFAULT 20,
    equipements   VARCHAR(255) DEFAULT NULL,
    localisation  VARCHAR(120) DEFAULT NULL,
    tarif_horaire DECIMAL(10,2) DEFAULT NULL,
    maintenance   TINYINT(1)   NOT NULL DEFAULT 0,
    cree_le       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Réservations de salles (statut piloté par le gestionnaire)
CREATE TABLE reservations (
    id                     INT AUTO_INCREMENT PRIMARY KEY,
    salle_id               INT NOT NULL,
    demandeur_nom          VARCHAR(150) NOT NULL,
    demandeur_organisation VARCHAR(150) DEFAULT NULL,
    demandeur_email        VARCHAR(190) DEFAULT NULL,
    demandeur_telephone    VARCHAR(30)  DEFAULT NULL,
    evenement              VARCHAR(200) NOT NULL,
    date_reservation       DATE NOT NULL,
    heure_debut            TIME NOT NULL,
    heure_fin              TIME NOT NULL,
    nb_personnes           INT  DEFAULT NULL,
    statut                 ENUM('EN_ATTENTE','CONFIRMEE','OCCUPEE','REFUSEE','ANNULEE','TERMINEE') NOT NULL DEFAULT 'EN_ATTENTE',
    commentaire            TEXT,
    traite_par             INT DEFAULT NULL,
    cree_le                DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_resa_salle  FOREIGN KEY (salle_id)  REFERENCES salles(id),
    CONSTRAINT fk_resa_traite FOREIGN KEY (traite_par) REFERENCES users(id),
    INDEX idx_resa_date (salle_id, date_reservation, statut)
) ENGINE=InnoDB;

-- Historique des changements de statut (qui a coché quoi, et quand)
CREATE TABLE reservation_events (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    statut        VARCHAR(20) NOT NULL,
    commentaire   VARCHAR(255) DEFAULT NULL,
    user_id       INT DEFAULT NULL,
    cree_le       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_revent_resa FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    CONSTRAINT fk_revent_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- Catalogue des formations (uniquement le numérique)
CREATE TABLE formations (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    titre        VARCHAR(200) NOT NULL,
    theme        VARCHAR(120) NOT NULL,
    description  TEXT,
    duree_heures INT NOT NULL DEFAULT 20,
    niveau       ENUM('DEBUTANT','INTERMEDIAIRE','AVANCE') NOT NULL DEFAULT 'DEBUTANT',
    public_vise  VARCHAR(255) DEFAULT NULL,
    tarif        DECIMAL(10,2) DEFAULT NULL,
    actif        TINYINT(1) NOT NULL DEFAULT 1,
    cree_le      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Sessions planifiées d'une formation (réservent automatiquement la salle)
CREATE TABLE sessions_formation (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    formation_id  INT NOT NULL,
    salle_id      INT NOT NULL,
    date_debut    DATE NOT NULL,
    date_fin      DATE NOT NULL,
    heure_debut   TIME NOT NULL,
    heure_fin     TIME NOT NULL,
    places        INT NOT NULL DEFAULT 20,
    cout          DECIMAL(10,2) DEFAULT NULL,
    formateur_nom VARCHAR(150) DEFAULT NULL,
    statut        ENUM('PLANIFIEE','EN_COURS','TERMINEE','ANNULEE') NOT NULL DEFAULT 'PLANIFIEE',
    cree_le       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sess_formation FOREIGN KEY (formation_id) REFERENCES formations(id),
    CONSTRAINT fk_sess_salle     FOREIGN KEY (salle_id)     REFERENCES salles(id)
) ENGINE=InnoDB;

-- Inscriptions (déposées en ligne, confirmées après vérification e-mail)
CREATE TABLE inscriptions (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    session_formation_id INT NOT NULL,
    user_id              INT DEFAULT NULL,
    nom                  VARCHAR(100) NOT NULL,
    prenom               VARCHAR(100) NOT NULL,
    email                VARCHAR(190) NOT NULL,
    telephone            VARCHAR(30)  DEFAULT NULL,
    niveau_actuel        VARCHAR(100) DEFAULT NULL,
    motivation           TEXT,
    statut               ENUM('EN_ATTENTE_VERIF','CONFIRMEE','LISTE_ATTENTE','ANNULEE') NOT NULL DEFAULT 'EN_ATTENTE_VERIF',
    token_verif          VARCHAR(64)  DEFAULT NULL,
    verifie_le           DATETIME     DEFAULT NULL,
    cree_le              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_insc_session FOREIGN KEY (session_formation_id) REFERENCES sessions_formation(id),
    CONSTRAINT fk_insc_user    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY uk_insc_token (token_verif),
    INDEX idx_insc_session (session_formation_id, statut)
) ENGINE=InnoDB;

-- Journal des e-mails envoyés (mode log / suivi des envois)
CREATE TABLE emails (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    destinataire VARCHAR(190) NOT NULL,
    sujet        VARCHAR(255) NOT NULL,
    corps        MEDIUMTEXT,
    statut       ENUM('LOG','ENVOYE','ECHEC') NOT NULL DEFAULT 'LOG',
    cree_le      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Journal d'activité (actions sensibles)
CREATE TABLE activity_logs (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action  VARCHAR(100) NOT NULL,
    details TEXT,
    ip      VARCHAR(45)  DEFAULT NULL,
    cree_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- Messages reçus depuis la page Contact du site public
CREATE TABLE messages_contact (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    nom       VARCHAR(120) NOT NULL,
    email     VARCHAR(190) NOT NULL,
    telephone VARCHAR(30)  DEFAULT NULL,
    sujet     VARCHAR(190) NOT NULL,
    message   TEXT NOT NULL,
    statut    ENUM('NOUVEAU','TRAITE') NOT NULL DEFAULT 'NOUVEAU',
    cree_le   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ══════════════════════════════════════════════════════════════════
--  DONNÉES DE DÉMONSTRATION
--  Comptes :
--    tossajean13@gmail.com / TOSjea13#            (administrateur)
--    tossajean13salles@gmail.com / Gestion@2026         (gestionnaire des salles)
--    tossajean13formations@gmail.com / Responsable@2026 (responsable formations)
--    apprenant1@gmail.com / Apprenant@2026 (apprenant, matricule GCF-2026-0001)
-- ══════════════════════════════════════════════════════════════════
INSERT INTO users (matricule, nom, prenom, email, telephone, role, password_hash) VALUES
(NULL, 'TOSSA', 'Jean', 'tossajean13@gmail.com', '+229 01 46 46 66 56', 'ADMIN', '$2y$12$y/GibJHhJBLdwmLVbc.W0e3JIcDKb6/DtV2xzTQj541aJr8zciWL6'),
(NULL, 'ADJOVI', 'Marie', 'tossajean13salles@gmail.com', '+229 01 97 00 00 01', 'GESTIONNAIRE', '$2y$12$JKvn6V4EmIX8MRPdp6LM9OxtudqwvnUKl/l8zVHw5J10Qf5k1vH0C'),
(NULL, 'GBAGUIDI', 'Paul', 'tossajean13formations@gmail.com', '+229 01 97 00 00 02', 'RESPONSABLE', '$2y$12$tFkmNA/Ht/g6aM7qllogBO5qk4d7iE1DJyAk/vY5Y9hgDKEjqU3CC'),
(NULL, 'FORMATEUR', 'Exemple', 'formateur@gmail.com', '+229 01 97 00 00 03', 'FORMATEUR', '$2y$12$tFkmNA/Ht/g6aM7qllogBO5qk4d7iE1DJyAk/vY5Y9hgDKEjqU3CC'),
('GCF-2026-0001', 'DOSSOU', 'Aïcha', 'apprenant1@gmail.com', '+229 01 97 00 00 10', 'APPRENANT', '$2y$12$SauJnsrq2h9XqJ1axypDbOX5Bfxdaekkvt2bFP0WnjVjRRExYgrFi'),
('GCF-2026-0002', 'KOUASSI', 'Ibrahim', 'apprenant2@gmail.com', '+229 01 97 00 00 11', 'APPRENANT', '$2y$12$NhhFPmIlaVWQv6m6Ad2HYuKv9E1zCAyz6X3BMb2rRAFD7zu.ulJYy');

INSERT INTO salles (nom, capacite, equipements, localisation, tarif_horaire) VALUES
('Salle de conférence A', 120, 'Sonorisation, vidéoprojecteur, scène, climatisation', 'Bâtiment principal · rez-de-chaussée', 15000.00),
('Salle B', 40, 'Vidéoprojecteur, tableau blanc, climatisation', 'Bâtiment principal · 1er étage', 8000.00),
('Laboratoire informatique 1', 25, '25 postes informatiques, vidéoprojecteur, wifi', 'Bâtiment technique', 12000.00),
('Laboratoire informatique 2', 20, '20 postes informatiques, wifi', 'Bâtiment technique', 10000.00),
('Salle de réunion', 12, 'Écran plat, tableau blanc', 'Bâtiment principal · 1er étage', 5000.00);

INSERT INTO formations (titre, theme, description, duree_heures, niveau, public_vise, tarif) VALUES
('Initiation à l''informatique', 'Bureautique', 'Premiers pas avec l''ordinateur : manipulation de la souris et du clavier, fichiers et dossiers, notions de base de Windows.', 30, 'DEBUTANT', 'Toute personne débutante', 45000.00),
('Bureautique professionnelle : Word, Excel, PowerPoint', 'Bureautique', 'Maîtrise des outils bureautiques pour le travail quotidien : traitement de texte, tableaux croisés, présentations professionnelles.', 60, 'DEBUTANT', 'Employés de bureau, secrétaires, étudiants', 90000.00),
('Internet et outils numériques', 'Culture numérique', 'Naviguer, chercher, communiquer et travailler en ligne : e-mail, clouds, réseaux sociaux professionnels, sécurité de base.', 24, 'DEBUTANT', 'Grand public, commerçants, artisans', 35000.00),
('Développement web : HTML, CSS et JavaScript', 'Développement', 'Construire des sites web de A à Z : structure HTML, mise en forme CSS, interactivité JavaScript, mise en ligne.', 120, 'INTERMEDIAIRE', 'Jeunes diplômés, autodidactes', 150000.00),
('Créer son site avec WordPress', 'Développement', 'Monter un site vitrine ou une boutique en ligne sans coder : installation, thèmes, extensions, référencement de base.', 40, 'INTERMEDIAIRE', 'Entrepreneurs, associations, PME', 75000.00),
('Cybersécurité : se protéger au quotidien', 'Sécurité', 'Mots de passe solides, phishing, sauvegardes, sécurisation des comptes et des outils de l''entreprise.', 16, 'INTERMEDIAIRE', 'Toute personne utilisant Internet au travail', 60000.00),
('Réseaux informatiques : les fondamentaux', 'Réseaux', 'Adressage IP, switchs, routeurs, wifi : comprendre et câbler un petit réseau de bureau.', 60, 'AVANCE', 'Techniciens, administrateurs système', 140000.00),
('Maintenance et dépannage des ordinateurs', 'Maintenance', 'Diagnostiquer, ouvrir, remplacer les composants, réinstaller un système : devenir autonome face aux pannes.', 50, 'INTERMEDIAIRE', 'Techniciens, jeunes en insertion', 110000.00);

-- Sessions de formation programmées (dates relatives à l'installation)
INSERT INTO sessions_formation (formation_id, salle_id, date_debut, date_fin, heure_debut, heure_fin, places, cout, formateur_nom, statut) VALUES
(1, 3, '2026-11-10', '2026-12-08', '09:00', '13:00', 25, 45000.00, 'M. ANATO Rodrigue', 'PLANIFIEE'),
(4, 4, '2026-11-17', '2027-01-16', '08:30', '15:30', 20, 150000.00, 'Mme LOKO Sabine', 'PLANIFIEE'),
(6, 5, '2026-11-13', '2026-11-15', '09:00', '16:00', 12, 60000.00, 'M. HOUNKPE Éric', 'PLANIFIEE'),
(2, 3, '2026-10-24', '2026-11-23', '14:00', '18:00', 25, 90000.00, 'Mme LOKO Sabine', 'EN_COURS');

-- Réservations d'exemple (dont celle de Jean, en attente de validation)
INSERT INTO reservations (salle_id, demandeur_nom, demandeur_organisation, demandeur_email, demandeur_telephone, evenement, date_reservation, heure_debut, heure_fin, nb_personnes, statut, commentaire) VALUES
(2, 'Jean DOSSOU', 'Association Lumière', 'jean.dossou@gmail.com', '+229 01 97 00 00 20', 'Conférence annuelle', '2026-11-07', '08:00', '12:00', 38, 'EN_ATTENTE', NULL),
(1, 'Ministère de la Formation', 'Séminaire national', 'contact@ministere.bj', '+229 21 30 00 00', 'Séminaire sur le numérique éducatif', '2026-11-09', '09:00', '17:00', 110, 'CONFIRMEE', 'Sonorisation et pause-café à prévoir.'),
(5, 'Cabinet AFRICABENIN', NULL, 'info@africabenin.bj', '+229 21 31 11 22', 'Réunion de comité', '2026-11-03', '10:00', '12:30', 10, 'OCCUPEE', 'Réunion en cours.');

INSERT INTO reservation_events (reservation_id, statut, commentaire, user_id) VALUES
(2, 'CONFIRMEE', 'Validation après vérification du planning', 2),
(3, 'OCCUPEE', 'Début de la réunion, salle occupée', 2);

-- Inscriptions de démonstration
INSERT INTO inscriptions (session_formation_id, user_id, nom, prenom, email, telephone, niveau_actuel, motivation, statut, verifie_le) VALUES
(1, 4, 'DOSSOU', 'Aïcha', 'apprenant1@gmail.com', '+229 01 97 00 00 10', 'Grand débutant', 'Je veux apprendre à utiliser l''ordinateur pour mon commerce.', 'CONFIRMEE', NOW()),
(1, 5, 'KOUASSI', 'Ibrahim', 'apprenant2@gmail.com', '+229 01 97 00 00 11', 'Quelques bases', 'Reconversion professionnelle vers le numérique.', 'CONFIRMEE', NOW()),
(2, NULL, 'ADJOVI', 'Fatou', 'fatou.adjovi@gmail.com', '+229 01 97 00 00 30', 'Bac + 2 en gestion', 'Devenir développeuse web.', 'EN_ATTENTE_VERIF', NULL);