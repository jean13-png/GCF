<?php
declare(strict_types=1);

/**
 * Point d'entrée unique de la plateforme GCF.
 * Toute requête passe ici : routeur, session sécurisée, autoload.
 */

// ── Session sécurisée ──────────────────────────────────────────────
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']),
]);
session_name('GCF_SESSION');
session_start();

// ── Chargement de l'application ───────────────────────────────────
require dirname(__DIR__) . '/app/Core/Autoloader.php';
require dirname(__DIR__) . '/app/Core/Helpers.php';

use App\Controllers\ApprenantController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\FormationController;
use App\Controllers\HomeController;
use App\Controllers\InscriptionController;
use App\Controllers\MailLogController;
use App\Controllers\PlanningController;
use App\Controllers\ReservationController;
use App\Controllers\SalleController;
use App\Controllers\SessionController;

$router = new App\Core\Router();

// ── Accueil et pages publiques ─────────────────────────────────────
$router->get('/', [HomeController::class, 'accueil']);
$router->get('/inscription', [InscriptionController::class, 'publique']);
$router->post('/inscription', [InscriptionController::class, 'soumettre']);
$router->get('/inscription/verifier', [InscriptionController::class, 'verifier']);
$router->get('/reserver', [ReservationController::class, 'formPublique']);
$router->post('/reserver', [ReservationController::class, 'enregistrerPublique']);

// ── Authentification (e-mail OU matricule + mot de passe) ─────────
$router->get('/login', [AuthController::class, 'form']);
$router->post('/login', [AuthController::class, 'connexion']);
$router->post('/logout', [AuthController::class, 'deconnexion']);

// ── Tableau de bord (administration et responsable) ────────────────
$router->get('/dashboard', [DashboardController::class, 'index'], ['ADMIN', 'GESTIONNAIRE', 'RESPONSABLE', 'FORMATEUR']);

// ── Salles ─────────────────────────────────────────────────────────
$router->get('/salles', [SalleController::class, 'index'], ['ADMIN', 'GESTIONNAIRE', 'RESPONSABLE']);
$router->get('/salles/nouvelle', [SalleController::class, 'form'], ['ADMIN']);
$router->get('/salles/editer/{id}', [SalleController::class, 'form'], ['ADMIN']);
$router->post('/salles/enregistrer', [SalleController::class, 'enregistrer'], ['ADMIN']);
$router->post('/salles/maintenance/{id}', [SalleController::class, 'maintenance'], ['ADMIN', 'GESTIONNAIRE']);

// ── Réservations de salles (le workflow Libre / Réservé / Occupé) ──
$router->get('/reservations', [ReservationController::class, 'index'], ['ADMIN', 'GESTIONNAIRE', 'RESPONSABLE']);
$router->get('/reservations/nouvelle', [ReservationController::class, 'form'], ['ADMIN', 'GESTIONNAIRE', 'RESPONSABLE']);
$router->post('/reservations/enregistrer', [ReservationController::class, 'enregistrer'], ['ADMIN', 'GESTIONNAIRE', 'RESPONSABLE']);
$router->post('/reservations/statut/{id}', [ReservationController::class, 'changerStatut'], ['ADMIN', 'GESTIONNAIRE']);

// ── Planning hebdomadaire ─────────────────────────────────────────
$router->get('/planning', [PlanningController::class, 'index'], ['ADMIN', 'GESTIONNAIRE', 'RESPONSABLE', 'FORMATEUR']);
$router->get('/planning/jour', [PlanningController::class, 'jour'], ['ADMIN', 'GESTIONNAIRE', 'RESPONSABLE', 'FORMATEUR']);
$router->post('/planning/programmer', [PlanningController::class, 'programmer'], ['ADMIN', 'GESTIONNAIRE', 'RESPONSABLE']);

// ── Catalogue des formations ──────────────────────────────────────
$router->get('/formations', [FormationController::class, 'index'], ['ADMIN', 'GESTIONNAIRE', 'RESPONSABLE']);
$router->get('/formations/nouvelle', [FormationController::class, 'form'], ['ADMIN', 'RESPONSABLE']);
$router->get('/formations/editer/{id}', [FormationController::class, 'form'], ['ADMIN', 'RESPONSABLE']);
$router->post('/formations/enregistrer', [FormationController::class, 'enregistrer'], ['ADMIN', 'RESPONSABLE']);
$router->post('/formations/activer/{id}', [FormationController::class, 'basculerActif'], ['ADMIN', 'RESPONSABLE']);

// ── Sessions de formation (programmation) ─────────────────────────
$router->get('/sessions', [SessionController::class, 'index'], ['ADMIN', 'RESPONSABLE', 'FORMATEUR']);
$router->get('/sessions/nouvelle', [SessionController::class, 'form'], ['ADMIN', 'RESPONSABLE']);
$router->get('/sessions/editer/{id}', [SessionController::class, 'form'], ['ADMIN', 'RESPONSABLE']);
$router->post('/sessions/enregistrer', [SessionController::class, 'enregistrer'], ['ADMIN', 'RESPONSABLE']);
$router->post('/sessions/statut/{id}', [SessionController::class, 'changerStatut'], ['ADMIN', 'RESPONSABLE']);
$router->get('/sessions/{id}/inscrits', [SessionController::class, 'inscrits'], ['ADMIN', 'RESPONSABLE']);

// ── Inscriptions reçues (gestion) ─────────────────────────────────
$router->get('/inscriptions', [InscriptionController::class, 'index'], ['ADMIN', 'RESPONSABLE']);
$router->post('/inscriptions/annuler/{id}', [InscriptionController::class, 'annuler'], ['ADMIN', 'RESPONSABLE']);

// ── Espace apprenant ──────────────────────────────────────────────
$router->get('/mon-espace', [ApprenantController::class, 'dashboard'], ['APPRENANT']);
$router->get('/mon-profil', [ApprenantController::class, 'profil'], ['APPRENANT', 'ADMIN', 'GESTIONNAIRE', 'RESPONSABLE', 'FORMATEUR']);
$router->post('/mon-profil', [ApprenantController::class, 'enregistrerProfil'], ['APPRENANT', 'ADMIN', 'GESTIONNAIRE', 'RESPONSABLE', 'FORMATEUR']);

// ── Journal des e-mails (administration) ───────────────────────────
$router->get('/emails', [MailLogController::class, 'index'], ['ADMIN']);

// ── Dispatch ───────────────────────────────────────────────────────
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
