-- ═════════════════════════════════════════════════════════════════════
-- GCF · Migration « Avis après réservation »
-- À exécuter une seule fois sur la base existante (après sauvegarde).
-- Une ligne par réservation : lien personnel envoyé au demandeur, puis
-- avis soumis (note facultative, points forts, points faibles, avis général).
-- ═════════════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS avis (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id   INT NOT NULL,
    token            CHAR(64) NOT NULL,
    token_expire_le  DATETIME NOT NULL,
    envoye_le        DATETIME DEFAULT NULL,
    note             TINYINT DEFAULT NULL,
    points_positifs  TEXT DEFAULT NULL,
    points_negatifs  TEXT DEFAULT NULL,
    avis_general     TEXT DEFAULT NULL,
    soumis_le        DATETIME DEFAULT NULL,
    cree_le          DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_avis_reservation (reservation_id),
    UNIQUE KEY uq_avis_token (token),
    CONSTRAINT fk_avis_reservation FOREIGN KEY (reservation_id)
        REFERENCES reservations (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
