<?php
// fichier : SAE_S3_BUT2_INFO/Public/index.php

// activation de l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use App\Core\Main;
use Dotenv\Dotenv;

// on définit la racine du projet (le dossier parent de public)
define('ROOT', dirname(__DIR__));

// on charge l'autoloader de composer
require_once ROOT . '/vendor/autoload.php';

// on charge les variables d'environnement du fichier .env
// vérifie que le fichier .env existe pour éviter les erreurs
if (file_exists(ROOT . '/.env')) {
    $dotenv = Dotenv::createImmutable(ROOT);
    $dotenv->load();
}

// on instancie le routeur (la classe main du cœur de l'app)
$app = new Main();

// on démarre l'application
$app->start();