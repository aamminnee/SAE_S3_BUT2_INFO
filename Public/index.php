<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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