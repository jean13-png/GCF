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
