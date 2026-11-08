<?php
namespace App\Core;

/**
 * Routeur simple : URI + verbe HTTP vers un contrôleur,
 * avec contrôle de rôle systématique côté serveur.
 */
final class Router
{
    /** @var array<string, array{routes: array[]}> */
    private array $routes = [];

    public function get(string $uri, array $action, ?array $roles = null): void
    {
        $this->routes[] = ['GET', $uri, $action, $roles];
    }

    public function post(string $uri, array $action, ?array $roles = null): void
    {
        $this->routes[] = ['POST', $uri, $action, $roles];
    }

    public function dispatch(string $methode, string $uri): void
    {
        $uri = parse_url($uri, PHP_URL_PATH) ?? '/';
        
        // Enlever le base_path si présent (pour supporter /GCF/login par exemple)
        $basePath = base_path();
        if ($basePath !== '' && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }
        
        $uri = '/' . trim($uri, '/');

        foreach ($this->routes as [$verbe, $route, $action, $roles]) {
            if ($verbe !== $methode) {
                continue;
            }
            $motif = '#^' . preg_replace('#\{[a-zA-Z_]+\}#', '([a-zA-Z0-9_-]+)', $route) . '/?$#';
            if (preg_match($motif, $uri === '/' ? '/' : rtrim($uri, '/'), $matches)) {
                array_shift($matches);

                if ($roles !== null) {
                    if (!Auth::check()) {
                        Flash::set('info', 'Veuillez vous connecter pour continuer.');
                        redirect('/login');
                    }
                    if (!Auth::is($roles)) {
                        self::pageErreur(403, 'Accès refusé', 'Vous n\'avez pas les droits nécessaires pour accéder à cette page.');
                    }
                }

                [$classe, $methodeControleur] = $action;
                (new $classe())->$methodeControleur(...$matches);
                return;
            }
        }

        self::pageErreur(404, 'Page introuvable', 'La page demandée n\'existe pas ou a été déplacée.');
    }

    public static function pageErreur(int $code, string $titre, string $message): never
    {
        http_response_code($code);
        $couleur = $code === 403 ? '#b3121a' : '#1c2b6b';
        echo "<!DOCTYPE html><html lang=\"fr\"><head><meta charset=\"utf-8\">"
            . "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">"
            . "<title>$titre · Centre de formation</title>"
            . "<style>body{font-family:system-ui,sans-serif;background:#f4f6fb;margin:0;display:grid;place-items:center;min-height:100vh;color:#222}"
            . ".box{background:#fff;border-radius:16px;box-shadow:0 10px 30px rgba(20,30,80,.08);padding:48px 40px;max-width:420px;text-align:center}"
            . ".code{font-size:56px;font-weight:800;color:$couleur}h1{font-size:20px;margin:8px 0 4px}p{color:#667;line-height:1.5}"
            . "a{display:inline-block;margin-top:18px;background:$couleur;color:#fff;padding:10px 22px;border-radius:10px;text-decoration:none}</style></head>"
            . "<body><div class=\"box\"><div class=\"code\">$code</div><h1>$titre</h1><p>"
            . e($message)
            . "</p><a href=\"" . url('/') . "\">Retour à l'accueil</a></div></body></html>";
        exit;
    }
}
