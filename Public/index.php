<?php
// 1. DÉMARRAGE DE SESSION OBLIGATOIRE (Tout en haut)
// Sans ça, le panier est vidé à chaque changement de page !
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Affichage des erreurs (Pour le développement)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use App\Core\Main;
use Dotenv\Dotenv;

// 3. Définition des chemins
define('ROOT', dirname(__DIR__));

// 4. Chargement des librairies
require_once ROOT . '/vendor/autoload.php';

// 5. Chargement de la configuration (.env)
if (file_exists(ROOT . '/.env')) {
    $dotenv = Dotenv::createImmutable(ROOT);
    $dotenv->load();
}

// 6. Lancement du site
$app = new Main();
$app->start();