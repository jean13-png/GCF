<?php
use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Flash;

/*
 * Sécurité de portabilité : si l'extension mbstring n'est pas active
 * sur l'hébergement, on fournit des équivalents simples (suffisants
 * pour les usages de la plateforme : longueurs, initiales, majuscules).
 */
if (!function_exists('mb_strlen')) {
    function mb_strlen(string $s): int { return strlen($s); }
    function mb_substr(string $s, int $debut, ?int $long = null): string { return $long === null ? substr($s, $debut) : substr($s, $debut, $long); }
    function mb_strtoupper(string $s): string { return strtoupper($s); }
}

/** Lit la configuration (avec fusion du fichier local s'il existe). */
function config(string $cle, mixed $defaut = null): mixed
{
    static $config = null;
    if ($config === null) {
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $local = dirname(__DIR__, 2) . '/config/config.local.php';
        if (is_file($local)) {
            $config = array_replace_recursive($config, require $local);
        }
    }
    $valeur = $config;
    foreach (explode('.', $cle) as $partie) {
        if (!is_array($valeur) || !array_key_exists($partie, $valeur)) {
            return $defaut;
        }
        $valeur = $valeur[$partie];
    }
    return $valeur;
}

/** Échappe une sortie (protection XSS, à utiliser dans TOUTES les vues). */
function e(mixed $valeur): string
{
    return htmlspecialchars((string) ($valeur ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Chemin de base public, calculé dynamiquement (compatible XAMPP et php -S). */
function base_path(): string
{
    static $base = null;
    if ($base === null) {
        $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $base = rtrim(str_replace('\\', '/', dirname($script)), '/');
    }
    return $base;
}

/** URL absolue d'une route. */
function url(string $chemin = '/'): string
{
    return base_path() . ('/' . ltrim($chemin, '/'));
}

function asset(string $chemin): string
{
    // Selon que la plateforme est servie depuis la racine (index.php)
    // ou depuis public/, le préfixe des fichiers statiques change.
    $prefixe = defined('DEPUIS_RACINE') ? '/public' : '';
    return url($prefixe . '/assets/' . ltrim($chemin, '/'));
}

function redirect(string $chemin): never
{
    header('Location: ' . url($chemin));
    exit;
}

function auth(): ?array
{
    return Auth::user();
}

/** Rend une vue : app/Views/<chemin>.php avec le layout commun. */
function view(string $chemin, array $donnees = []): void
{
    extract($donnees, EXTR_SKIP);
    $contenu = dirname(__DIR__) . '/Views/' . $chemin . '.php';
    if (!is_file($contenu)) {
        App\Core\Router::pageErreur(500, 'Erreur interne', 'Vue introuvable : ' . $chemin);
    }
    require $contenu;
}

function csrf_champ(): string
{
    return CSRF::champ();
}

function old(string $cle, mixed $defaut = ''): mixed
{
    return Flash::old($cle, $defaut);
}

/** Libellés et couleurs des statuts de réservation. */
function statuts_reservation(): array
{
    return [
        'EN_ATTENTE' => ['En attente',  'attente'],
        'CONFIRMEE'  => ['Réservé',     'reserve'],
        'OCCUPEE'    => ['Occupé',      'occupe'],
        'REFUSEE'    => ['Refusée',     'refuse'],
        'ANNULEE'    => ['Annulée',     'annule'],
        'TERMINEE'   => ['Terminée',    'termine'],
    ];
}

function badge_statut(string $statut): string
{
    $infos = statuts_reservation()[$statut] ?? [$statut, 'termine'];
    return '<span class="badge badge-' . $infos[1] . '">' . e($infos[0]) . '</span>';
}

function statuts_inscription(): array
{
    return [
        'EN_ATTENTE_VERIF' => ['En attente de vérification e-mail', 'attente'],
        'CONFIRMEE'        => ['Inscrit',   'libre'],
        'LISTE_ATTENTE'    => ['Liste d\'attente', 'reserve'],
        'ANNULEE'          => ['Annulée',   'annule'],
    ];
}

function badge_inscription(string $statut): string
{
    $infos = statuts_inscription()[$statut] ?? [$statut, 'termine'];
    return '<span class="badge badge-' . $infos[1] . '">' . e($infos[0]) . '</span>';
}

function format_fcfa(mixed $montant): string
{
    if ($montant === null || $montant === '') {
        return '·';
    }
    return number_format((float) $montant, 0, ',', ' ') . ' FCFA';
}

/** Dates en français, sans dépendance externe. */
function jour_fr(int $numero): string
{
    $jours = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
    return $jours[$numero] ?? '';
}

function mois_fr(int $numero): string
{
    $mois = [1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
        'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    return $mois[$numero] ?? '';
}

function format_date_fr(?string $date, bool $avecJour = true): string
{
    if (!$date) {
        return '·';
    }
    $ts = strtotime($date);
    $texte = date('j', $ts) . ' ' . mois_fr((int) date('n', $ts)) . ' ' . date('Y', $ts);
    return $avecJour ? jour_fr((int) date('w', $ts)) . ' ' . $texte : $texte;
}

function format_heure(?string $heure): string
{
    return $heure ? substr($heure, 0, 5) : '·';
}

function initiales(array $user): string
{
    return mb_strtoupper(mb_substr($user['prenom'] ?? '', 0, 1) . mb_substr($user['nom'] ?? '', 0, 1));
}
