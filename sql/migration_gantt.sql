-- ═════════════════════════════════════════════════════════════════════
-- GCF · Migration Gantt : vues Planning avancées
-- À exécuter une seule fois sur la base existante.
-- ═════════════════════════════════════════════════════════════════════

-- Catégorie de l'événement ( Conférence, Réunion, Formation, Atelier,
-- Séminaire, Examen, Autre ) : alimente le critère « Couleur par ».
ALTER TABLE reservations
    ADD COLUMN categorie VARCHAR(50) DEFAULT NULL AFTER evenement;

-- Réservations multi-jours : date de fin ( NULL = journée unique ).
ALTER TABLE reservations
    ADD COLUMN date_fin DATE DEFAULT NULL AFTER date_reservation;

-- Formateur responsable ( NULL = non affecté ) : alimente les
-- affichages « Par formateur » de la vue Ressources.
ALTER TABLE reservations
    ADD COLUMN formateur_id INT DEFAULT NULL AFTER traite_par,
    ADD CONSTRAINT fk_resa_formateur FOREIGN KEY (formateur_id) REFERENCES users(id);

-- Statut « à reporter » : bloc jaune, contour épais, titre gras italique.
ALTER TABLE reservations
    MODIFY statut ENUM('EN_ATTENTE','CONFIRMEE','OCCUPEE','A_REPORTER','REFUSEE','ANNULEE','TERMINEE')
    NOT NULL DEFAULT 'EN_ATTENTE';

-- Site de la salle ( aujourd'hui un seul site, prêt pour le multi-sites ).
ALTER TABLE salles
    ADD COLUMN site VARCHAR(100) NOT NULL DEFAULT 'Cotonou' AFTER nom;

-- Index pour les nouveaux accès.
CREATE INDEX idx_resa_formateur ON reservations(formateur_id);
CREATE INDEX idx_resa_periode   ON reservations(date_reservation, date_fin);
