<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Affichage des erreurs (Pour le développement)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use App\Core\Main;
use Dotenv\Dotenv;

define('ROOT', dirname(__DIR__));

require_once ROOT . '/vendor/autoload.php';

if (file_exists(ROOT . '/.env')) {
    $dotenv = Dotenv::createImmutable(ROOT);
    $dotenv->load();
}

$app = new Main();
$app->start();