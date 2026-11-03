<?php
/**
 * Autoloading PSR-4 simplifié : App\Core\X => app/Core/X.php
 */
spl_autoload_register(static function (string $classe): void {
    if (!str_starts_with($classe, 'App\\')) {
        return;
    }
    $relatif = str_replace('\\', '/', substr($classe, 4)); // retire "App\"
    $chemin  = dirname(__DIR__) . '/' . $relatif . '.php';
    if (is_file($chemin)) {
        require $chemin;
    }
});
