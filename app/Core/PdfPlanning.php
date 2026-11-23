<?php
namespace App\Core;

/**
 * Export PDF du planning hebdomadaire des salles.
 * PDF écrit sans bibliothèque externe (le projet n'en utilise aucune) :
 * polices Helvetica intégrées, format paysage A4.
 * Page 1 : vue d'ensemble de la semaine (salles × jours).
 * Pages suivantes : une page par salle avec le détail de chaque réservation.
 */
final class PdfPlanning
{
    private const LARGEUR = 842;
    private const HAUTEUR = 595;
    private const MARGE   = 36;

    private const BLEU  = [0.114, 0.306, 0.847];
    private const INK   = [0.090, 0.100, 0.140];
    private const GRIS  = [0.392, 0.455, 0.545];
    private const LIGNE = [0.800, 0.839, 0.882];
    private const FOND  = [0.945, 0.961, 0.976];
    private const BLANC = [1.0, 1.0, 1.0];
    private const LIBRE = [0.086, 0.545, 0.259];

    private const JOURS = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
    private const MOIS  = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
                           'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

    private const STATUT_LIBELLE = [
        'EN_ATTENTE' => 'En attente',
        'CONFIRMEE'  => 'Réservé',
        'OCCUPEE'    => 'Occupé',
        'A_REPORTER' => 'À reporter',
    ];
    private const STATUT_COULEUR = [
        'EN_ATTENTE' => [0.800, 0.450, 0.000],
        'CONFIRMEE'  => [0.150, 0.390, 0.920],
        'OCCUPEE'    => [0.860, 0.150, 0.150],
        'A_REPORTER' => [0.490, 0.230, 0.930],
    ];

    private array $pages = [];
    private string $flux = '';
    private float $curseur = 0.0;
    private int $numero = 0;

    /**
     * Génère le PDF de la semaine (chaîne binaire).
     * $heuresParJour = fermeture - ouverture (sert au taux d'occupation).
     */
    public function semaine(array $salles, array $reservations, string $lundi, string $dimanche, int $ouverture, int $fermeture): string
    {
        $this->pages = [];
        $this->flux = '';
        $this->numero = 0;
        $periode = 'Semaine du ' . $this->dateLongue($lundi) . ' au ' . $this->dateLongue($dimanche);

        $this->vueSemaine($salles, $reservations, $lundi, $periode);
        foreach ($salles as $salle) {
            $this->pageSalle($salle, $reservations, $lundi, $dimanche, $periode, $ouverture, $fermeture);
        }
        return $this->sortie('Planning des salles - ' . $periode);
    }

    /* ─────────────── Vue d'ensemble de la semaine ─────────────── */

    private function vueSemaine(array $salles, array $reservations, string $lundi, string $periode): void
    {
        $titre = 'Planning des salles : vue de la semaine';
        $wSalle = 130.0;
        $wJour = (self::LARGEUR - 2 * self::MARGE - $wSalle) / 7;
        $maxLignes = 7;

        $this->page($titre, $periode);
        $this->entetesJours($lundi, $wSalle, $wJour);

        foreach ($salles as $salle) {
            $cases = [];
            $hauteur = 42.0;
            for ($j = 0; $j < 7; $j++) {
                $jour = $this->decaler($lundi, $j);
                $matches = array_values(array_filter(
                    $reservations,
                    fn($r) => (int) $r['salle_id'] === (int) $salle['id'] && $this->couvre($r, $jour)
                ));
                $lignes = [];
                $affichees = 0;
                foreach ($matches as $r) {
                    $morceaux = array_merge(
                        [substr($r['heure_debut'], 0, 5) . ' – ' . substr($r['heure_fin'], 0, 5)],
                        $this->decouper((string) $r['evenement'], 7.5, false, $wJour - 8, 2)
                    );
                    if (count($lignes) + count($morceaux) > $maxLignes) {
                        break;
                    }
                    foreach ($morceaux as $i => $m) {
                        $lignes[] = [$m, $r['statut'], $i === 0];
                    }
                    $affichees++;
                }
                $reste = count($matches) - $affichees;
                $cases[$j] = [$lignes, $reste];
                $hauteur = max($hauteur, count($lignes) * 10 + ($reste > 0 ? 10 : 0) + 14);
            }

            if ($this->curseur + $hauteur > self::HAUTEUR - 60) {
                $this->page($titre, $periode);
                $this->entetesJours($lundi, $wSalle, $wJour);
            }

            $y = $this->curseur;
            $this->rect(self::MARGE, $y, self::LARGEUR - 2 * self::MARGE, $hauteur, null, self::LIGNE);
            $nomLignes = $this->decouper((string) $salle['nom'], 8.5, true, $wSalle - 12, 2);
            foreach ($nomLignes as $k => $ligneNom) {
                $this->texte(self::MARGE + 6, $y + 6 + $k * 10.5, $ligneNom, 8.5, true, self::INK);
            }
            $info = 'Capacité ' . (int) $salle['capacite'] . ' pers.' . ((int) $salle['maintenance'] === 1 ? ' · maintenance' : '');
            $this->texte(self::MARGE + 6, $y + 6 + count($nomLignes) * 10.5 + 2, $this->ajuster($info, 7.5, false, $wSalle - 12), 7.5, false, self::GRIS);

            for ($j = 0; $j < 7; $j++) {
                $x = self::MARGE + $wSalle + $j * $wJour;
                $this->ligne($x, $y, $x, $y + $hauteur, self::LIGNE);
                [$lignes, $reste] = $cases[$j];
                foreach ($lignes as $k => [$txt, $statut, $gras]) {
                    $this->texte($x + 4, $y + 6 + $k * 10, $this->ajuster($txt, 7.5, $gras, $wJour - 8), 7.5, $gras, self::STATUT_COULEUR[$statut] ?? self::INK);
                }
                if ($reste > 0) {
                    $this->texte($x + 4, $y + 6 + count($lignes) * 10, '+ ' . $reste . ' autre(s)', 7.5, true, self::GRIS);
                }
            }
            $this->curseur += $hauteur;
        }

        $this->curseur += 14;
        if ($this->curseur > self::HAUTEUR - 60) {
            $this->page($titre, $periode);
        }
        $this->texte(self::MARGE, $this->curseur, 'Statuts :', 8, true, self::GRIS);
        $x = self::MARGE + 52;
        foreach (self::STATUT_LIBELLE as $code => $libelle) {
            $this->rect($x, $this->curseur + 1, 8, 8, self::STATUT_COULEUR[$code]);
            $this->texte($x + 12, $this->curseur, $libelle, 8, false, self::INK);
            $x += 100;
        }
    }

    private function entetesJours(string $lundi, float $wSalle, float $wJour): void
    {
        $this->rect(self::MARGE, $this->curseur, self::LARGEUR - 2 * self::MARGE, 22, self::FOND);
        $this->texte(self::MARGE + 6, $this->curseur + 7, 'Salle', 8.5, true, self::GRIS);
        for ($j = 0; $j < 7; $j++) {
            $d = $this->decaler($lundi, $j);
            $x = self::MARGE + $wSalle + $j * $wJour;
            $this->texte($x + 4, $this->curseur + 7, ucfirst($this->nomJour($d)) . ' ' . substr($d, 8, 2) . '/' . substr($d, 5, 2), 8.5, true, self::INK);
        }
        $this->curseur += 24;
    }

    /* ─────────────── Une page par salle ─────────────── */

    private function pageSalle(array $salle, array $reservations, string $lundi, string $dimanche, string $periode, int $ouverture, int $fermeture): void
    {
        $resas = [];
        foreach ($reservations as $r) {
            if ((int) $r['salle_id'] === (int) $salle['id']) {
                $resas[] = $r;
            }
        }
        $minutes = 0;
        foreach ($resas as $r) {
            $minutes += $this->minutesDansSemaine($r, $lundi, $dimanche);
        }
        $capacite = 7 * max(1, $fermeture - $ouverture);
        $taux = (int) round(($minutes / 60) / $capacite * 100);

        // Réservations et créneaux libres, dans l'ordre chronologique
        $lignes = [];
        foreach ($resas as $r) {
            $lignes[] = ['date' => (string) $r['date_reservation'], 'debut' => $this->minutesHeure((string) $r['heure_debut']), 'resa' => $r];
        }
        foreach ($this->creneauxLibres($resas, $lundi, $dimanche, $ouverture, $fermeture) as [$jour, $d, $f]) {
            $lignes[] = ['date' => $jour, 'debut' => $d, 'libre' => [$jour, $d, $f]];
        }
        usort($lignes, static fn($a, $b) => [$a['date'], $a['debut']] <=> [$b['date'], $b['debut']]);

        $titre = 'Salle : ' . $salle['nom'];
        $this->page($titre, $periode);

        $this->texte(self::MARGE, $this->curseur, (string) $salle['nom'], 15, true, self::INK);
        $this->curseur += 22;
        $meta = 'Site : ' . ($salle['site'] ?: 'Cotonou') . '  ·  Capacité : ' . (int) $salle['capacite'] . ' personnes  ·  '
              . ((int) $salle['maintenance'] === 1 ? 'En maintenance' : 'Disponible');
        $this->texte(self::MARGE, $this->curseur, $meta, 9, false, self::GRIS);
        $this->curseur += 16;
        $stats = count($resas) . ' réservation(s) · ' . number_format($minutes / 60, 1, ',', '') . ' h réservées sur '
               . $capacite . ' h d\'ouverture (' . $taux . ' %)';
        $this->texte(self::MARGE, $this->curseur, $stats, 9, true, self::INK);
        $this->curseur += 22;

        $cols = [['Jour', 150], ['Horaire', 105], ['Événement', 240], ['Organisateur', 205], ['Statut', 70]];
        $this->enteteTableau($cols);

        if ($lignes === []) {
            $this->texte(self::MARGE + 6, $this->curseur + 8, 'Aucun créneau disponible cette semaine.', 10, false, self::GRIS);
            return;
        }

        foreach ($lignes as $i => $ligne) {
            if ($this->curseur + 22 > self::HAUTEUR - 60) {
                $this->page($titre, $periode);
                $this->enteteTableau($cols);
            }
            $y = $this->curseur;
            if ($i % 2 === 1) {
                $this->rect(self::MARGE, $y, self::LARGEUR - 2 * self::MARGE, 22, self::FOND);
            }
            if (isset($ligne['resa'])) {
                $r = $ligne['resa'];
                $formateur = trim(($r['formateur_prenom'] ?? '') . ' ' . ($r['formateur_nom'] ?? ''));
                $valeurs = [
                    $this->libelleDates($r),
                    substr($r['heure_debut'], 0, 5) . ' – ' . substr($r['heure_fin'], 0, 5),
                    (string) $r['evenement'],
                    (string) $r['demandeur_nom'] . ($formateur !== '' ? ' · formateur : ' . $formateur : ''),
                    self::STATUT_LIBELLE[$r['statut']] ?? (string) $r['statut'],
                ];
                $couleurs = [self::INK, self::INK, self::INK, self::INK, self::STATUT_COULEUR[$r['statut']] ?? self::INK];
            } else {
                [$jour, $d, $f] = $ligne['libre'];
                $valeurs = [
                    ucfirst($this->nomJour($jour)) . ' ' . substr($jour, 8, 2) . '/' . substr($jour, 5, 2),
                    $this->hm($d) . ' – ' . $this->hm($f),
                    'Créneau disponible',
                    '–',
                    'Libre',
                ];
                $couleurs = [self::INK, self::INK, self::GRIS, self::GRIS, self::LIBRE];
            }
            $x = self::MARGE;
            foreach ($cols as $c => [$nom, $larg]) {
                $statut = $c === 4;
                $this->texte($x + 6, $y + 7, $this->ajuster($valeurs[$c], 8.5, $statut, $larg - 12), 8.5, $statut, $couleurs[$c]);
                $x += $larg;
            }
            $this->ligne(self::MARGE, $y + 22, self::LARGEUR - self::MARGE, $y + 22, self::LIGNE);
            $this->curseur += 22;
        }
    }

    /** Créneaux libres d'une salle sur la semaine, entre ouverture et fermeture (minimum 30 min). */
    private function creneauxLibres(array $resas, string $lundi, string $dimanche, int $ouverture, int $fermeture): array
    {
        $libres = [];
        for ($j = 0; $j < 7; $j++) {
            $jour = $this->decaler($lundi, $j);
            $occupes = [];
            foreach ($resas as $r) {
                $fin = $r['date_fin'] ?: $r['date_reservation'];
                if ($r['date_reservation'] <= $jour && $fin >= $jour) {
                    $occupes[] = [$this->minutesHeure((string) $r['heure_debut']), $this->minutesHeure((string) $r['heure_fin'])];
                }
            }
            usort($occupes, static fn($a, $b) => $a[0] <=> $b[0]);
            $curseur = $ouverture * 60;
            foreach ($occupes as [$d, $f]) {
                if ($d - $curseur >= 30) {
                    $libres[] = [$jour, $curseur, $d];
                }
                $curseur = max($curseur, $f);
            }
            if ($fermeture * 60 - $curseur >= 30) {
                $libres[] = [$jour, $curseur, $fermeture * 60];
            }
        }
        return $libres;
    }

    private function minutesHeure(string $heure): int
    {
        [$h, $m] = array_map('intval', explode(':', substr($heure, 0, 5)));
        return $h * 60 + $m;
    }

    private function hm(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    private function enteteTableau(array $cols): void
    {
        $this->rect(self::MARGE, $this->curseur, self::LARGEUR - 2 * self::MARGE, 22, self::FOND);
        $x = self::MARGE;
        foreach ($cols as [$nom, $larg]) {
            $this->texte($x + 6, $this->curseur + 7, $nom, 8.5, true, self::GRIS);
            $x += $larg;
        }
        $this->curseur += 24;
    }

    /* ─────────────── Données ─────────────── */

    private function couvre(array $r, string $jour): bool
    {
        $fin = $r['date_fin'] ?: $r['date_reservation'];
        return $r['date_reservation'] <= $jour && $fin >= $jour;
    }

    private function minutesDansSemaine(array $r, string $lundi, string $dimanche): int
    {
        $debut = max($r['date_reservation'], $lundi);
        $fin = min($r['date_fin'] ?: $r['date_reservation'], $dimanche);
        $jours = (int) floor((strtotime($fin) - strtotime($debut)) / 86400) + 1;
        if ($jours <= 0) {
            return 0;
        }
        $duree = (int) round((strtotime($r['heure_fin']) - strtotime($r['heure_debut'])) / 60);
        return max(0, $jours * $duree);
    }

    private function libelleDates(array $r): string
    {
        $debut = (string) $r['date_reservation'];
        $fin = (string) ($r['date_fin'] ?? '');
        if ($fin !== '' && $fin > $debut) {
            return 'du ' . substr($debut, 8, 2) . '/' . substr($debut, 5, 2) . ' au ' . substr($fin, 8, 2) . '/' . substr($fin, 5, 2);
        }
        return ucfirst($this->nomJour($debut)) . ' ' . substr($debut, 8, 2) . '/' . substr($debut, 5, 2);
    }

    private function decaler(string $ymd, int $n): string
    {
        return (new \DateTime($ymd))->modify('+' . $n . ' days')->format('Y-m-d');
    }

    private function nomJour(string $ymd): string
    {
        return self::JOURS[(int) (new \DateTime($ymd))->format('w')];
    }

    private function dateLongue(string $ymd): string
    {
        $d = new \DateTime($ymd);
        return self::JOURS[(int) $d->format('w')] . ' ' . (int) $d->format('j') . ' ' . self::MOIS[(int) $d->format('n')] . ' ' . $d->format('Y');
    }

    /* ─────────────── Mise en page ─────────────── */

    private function page(string $titre, string $sous): void
    {
        if ($this->flux !== '') {
            $this->pied();
            $this->pages[] = $this->flux;
        }
        $this->flux = '';
        $this->numero = count($this->pages) + 1;
        $this->rect(0, 0, self::LARGEUR, 50, self::BLEU);
        $this->texte(self::MARGE, 11, $this->ajuster($titre, 16, true, self::LARGEUR - 2 * self::MARGE), 16, true, self::BLANC);
        $this->texte(self::MARGE, 33, $this->ajuster($sous, 9, false, self::LARGEUR - 2 * self::MARGE), 9, false, [0.86, 0.92, 1.0]);
        $this->curseur = 66;
    }

    private function pied(): void
    {
        $this->texte(self::MARGE, self::HAUTEUR - 20, 'Généré le ' . date('d/m/Y à H:i') . '  ·  page ' . $this->numero, 7.5, false, self::GRIS);
    }

    private function rect(float $x, float $y, float $w, float $h, ?array $fond = null, ?array $trait = null): void
    {
        $op = '';
        if ($fond !== null) {
            $op .= $this->couleur($fond, 'rg') . ' ';
        }
        if ($trait !== null) {
            $op .= $this->couleur($trait, 'RG') . ' 0.6 w ';
        }
        $op .= sprintf('%.2f %.2f %.2f %.2f re ', $x, $this->yPdf($y + $h), $w, $h);
        $op .= ($fond !== null && $trait !== null) ? 'B' : ($fond !== null ? 'f' : 'S');
        $this->flux .= $op . "\n";
    }

    private function ligne(float $x1, float $y1, float $x2, float $y2, array $couleur): void
    {
        $this->flux .= $this->couleur($couleur, 'RG') . ' 0.6 w '
            . sprintf('%.2f %.2f m %.2f %.2f l S', $x1, $this->yPdf($y1), $x2, $this->yPdf($y2)) . "\n";
    }

    private function texte(float $x, float $y, string $s, float $taille = 9.0, bool $gras = false, array $couleur = self::INK): void
    {
        $this->flux .= sprintf("BT /%s %.2f Tf %s %.2f %.2f Td (%s) Tj ET\n",
            $gras ? 'F2' : 'F1', $taille, $this->couleur($couleur, 'rg'),
            $x, $this->yPdf($y + $taille * 0.85), $this->pdfTexte($s));
    }

    /** Découpe un texte en au plus $maxLignes lignes qui tiennent dans la largeur. */
    private function decouper(string $s, float $taille, bool $gras, float $largeurMax, int $maxLignes): array
    {
        $lignes = [];
        $cur = '';
        foreach (explode(' ', $this->nettoyer($s)) as $mot) {
            $essai = $cur === '' ? $mot : $cur . ' ' . $mot;
            if ($cur === '' || $this->largeur($essai, $taille, $gras) <= $largeurMax) {
                $cur = $essai;
            } else {
                $lignes[] = $cur;
                $cur = $mot;
            }
        }
        if ($cur !== '') {
            $lignes[] = $cur;
        }
        $lignes = array_slice($lignes, 0, $maxLignes);
        return array_map(fn($l) => $this->ajuster($l, $taille, $gras, $largeurMax), $lignes);
    }

    private function ajuster(string $s, float $taille, bool $gras, float $largeurMax): string
    {
        $s = $this->nettoyer($s);
        if ($this->largeur($s, $taille, $gras) <= $largeurMax) {
            return $s;
        }
        while ($s !== '' && $this->largeur($s . '...', $taille, $gras) > $largeurMax) {
            $s = mb_substr($s, 0, -1, 'UTF-8');
        }
        return rtrim($s) . '...';
    }

    private function largeur(string $s, float $taille, bool $gras): float
    {
        // Estimation prudente pour Helvetica : évite le débordement des cellules
        return mb_strlen($s, 'UTF-8') * $taille * ($gras ? 0.62 : 0.55);
    }

    private function nettoyer(string $s): string
    {
        return trim(preg_replace('/\s+/u', ' ', $s) ?? $s);
    }

    private function couleur(array $c, string $operateur): string
    {
        return sprintf('%.3f %.3f %.3f %s', $c[0], $c[1], $c[2], $operateur);
    }

    private function yPdf(float $y): float
    {
        return self::HAUTEUR - $y;
    }

    private function pdfTexte(string $s): string
    {
        $s = $this->nettoyer($s);
        $octets = @iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', $s);
        if ($octets === false) {
            $octets = $s;
        }
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $octets);
    }

    /* ─────────────── Fichier PDF ─────────────── */

    private function sortie(string $titreDocument): string
    {
        $this->pied();
        $this->pages[] = $this->flux;
        $n = count($this->pages);

        $obj = [];
        $obj[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $kids = [];
        for ($i = 0; $i < $n; $i++) {
            $kids[] = (5 + 2 * $i) . ' 0 R';
        }
        $obj[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . $n . ' >>';
        $obj[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $obj[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        for ($i = 0; $i < $n; $i++) {
            $obj[5 + 2 * $i] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . self::LARGEUR . ' ' . self::HAUTEUR . ']'
                . ' /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . (6 + 2 * $i) . ' 0 R >>';
            $obj[6 + 2 * $i] = '<< /Length ' . strlen($this->pages[$i]) . " >>\nstream\n" . $this->pages[$i] . 'endstream';
        }
        $info = 5 + 2 * $n;
        $obj[$info] = '<< /Title (' . $this->pdfTexte($titreDocument) . ') /Creator (GCF) /Producer (GCF) >>';

        $out = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $off = [];
        foreach ($obj as $id => $corps) {
            $off[$id] = strlen($out);
            $out .= $id . " 0 obj\n" . $corps . "\nendobj\n";
        }
        $xref = strlen($out);
        $total = $info + 1;
        $out .= "xref\n0 $total\n0000000000 65535 f \n";
        for ($id = 1; $id < $total; $id++) {
            $out .= sprintf("%010d 00000 n \n", $off[$id]);
        }
        $out .= "trailer\n<< /Size $total /Root 1 0 R /Info $info 0 R >>\nstartxref\n$xref\n%%EOF\n";
        return $out;
    }
}
