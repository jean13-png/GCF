<?php
/**
 * Générateur du jeu de données de démonstration (sql/seed.sql).
 * Usage : php sql/seed.php > sql/seed.sql
 * Les mots de passe sont hachés avec bcrypt (jamais en clair).
 */
$hash = static fn (string $mdp): string => password_hash($mdp, PASSWORD_BCRYPT);

$admin        = $hash('Admin@2026');
$gestionnaire = $hash('Gestion@2026');
$responsable  = $hash('Responsable@2026');
$apprenant    = $hash('Apprenant@2026');
$apprenant2   = $hash('Apprenant@2026');

$aujourdhui = date('Y-m-d');
$plus = static fn (int $jours): string => date('Y-m-d', strtotime("+$jours days"));

$s1d = $plus(7);  $s1f = $plus(35);
$s2d = $plus(14); $s2f = $plus(74);
$s3d = $plus(10); $s3f = $plus(12);
$s4d = $plus(-10); $s4f = $plus(20);
$r1 = $plus(4);  $r2 = $plus(6);

echo <<<SQL
-- ════════════════════════════════════════════════════════════
--  GCF · données de démonstration
--  Comptes :
--    admin@gcf.bj / Admin@2026            (administrateur)
--    salles@gcf.bj / Gestion@2026         (gestionnaire des salles)
--    formations@gcf.bj / Responsable@2026 (responsable formations)
--    apprenant1@gmail.com / Apprenant@2026 (apprenant, matricule GCF-2026-0001)
-- ════════════════════════════════════════════════════════════
USE gcf;

INSERT INTO users (matricule, nom, prenom, email, telephone, role, password_hash) VALUES
(NULL, 'TOSSA', 'Jean', 'admin@gcf.bj', '+229 01 46 46 66 56', 'ADMIN', '$admin'),
(NULL, 'ADJOVI', 'Marie', 'salles@gcf.bj', '+229 01 97 00 00 01', 'GESTIONNAIRE', '$gestionnaire'),
(NULL, 'GBAGUIDI', 'Paul', 'formations@gcf.bj', '+229 01 97 00 00 02', 'RESPONSABLE', '$responsable'),
('GCF-2026-0001', 'DOSSOU', 'Aïcha', 'apprenant1@gmail.com', '+229 01 97 00 00 10', 'APPRENANT', '$apprenant'),
('GCF-2026-0002', 'KOUASSI', 'Ibrahim', 'apprenant2@gmail.com', '+229 01 97 00 00 11', 'APPRENANT', '$apprenant2');

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
(1, 3, '$s1d', '$s1f', '09:00', '13:00', 25, 45000.00, 'M. ANATO Rodrigue', 'PLANIFIEE'),
(4, 4, '$s2d', '$s2f', '08:30', '15:30', 20, 150000.00, 'Mme LOKO Sabine', 'PLANIFIEE'),
(6, 5, '$s3d', '$s3f', '09:00', '16:00', 12, 60000.00, 'M. HOUNKPE Éric', 'PLANIFIEE'),
(2, 3, '$s4d', '$s4f', '14:00', '18:00', 25, 90000.00, 'Mme LOKO Sabine', 'EN_COURS');

-- Réservations d'exemple (dont celle de Jean, en attente de validation)
INSERT INTO reservations (salle_id, demandeur_nom, demandeur_organisation, demandeur_email, demandeur_telephone, evenement, date_reservation, heure_debut, heure_fin, nb_personnes, statut, commentaire) VALUES
(2, 'Jean DOSSOU', 'Association Lumière', 'jean.dossou@gmail.com', '+229 01 97 00 00 20', 'Conférence annuelle', '$r1', '08:00', '12:00', 38, 'EN_ATTENTE', NULL),
(1, 'Ministère de la Formation', 'Séminaire national', 'contact@ministere.bj', '+229 21 30 00 00', 'Séminaire sur le numérique éducatif', '$r2', '09:00', '17:00', 110, 'CONFIRMEE', 'Sonorisation et pause-café à prévoir.'),
(5, 'Cabinet AFRICABENIN', NULL, 'info@africabenin.bj', '+229 21 31 11 22', 'Réunion de comité', '$aujourdhui', '10:00', '12:30', 10, 'OCCUPEE', 'Réunion en cours.');

INSERT INTO reservation_events (reservation_id, statut, commentaire, user_id) VALUES
(2, 'CONFIRMEE', 'Validation après vérification du planning', 2),
(3, 'OCCUPEE', 'Début de la réunion, salle occupée', 2);

-- Inscriptions de démonstration
INSERT INTO inscriptions (session_formation_id, user_id, nom, prenom, email, telephone, niveau_actuel, motivation, statut, verifie_le) VALUES
(1, 4, 'DOSSOU', 'Aïcha', 'apprenant1@gmail.com', '+229 01 97 00 00 10', 'Grand débutant', 'Je veux apprendre à utiliser l''ordinateur pour mon commerce.', 'CONFIRMEE', NOW()),
(1, 5, 'KOUASSI', 'Ibrahim', 'apprenant2@gmail.com', '+229 01 97 00 00 11', 'Quelques bases', 'Reconversion professionnelle vers le numérique.', 'CONFIRMEE', NOW()),
(2, NULL, 'ADJOVI', 'Fatou', 'fatou.adjovi@gmail.com', '+229 01 97 00 00 30', 'Bac + 2 en gestion', 'Devenir développeuse web.', 'EN_ATTENTE_VERIF', NULL);
SQL;
