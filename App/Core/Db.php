<?php
namespace App\Core;

use PDO;
use PDOException;

class Db extends PDO
{
    // instance unique de la classe
    private static $instance;

    private function __construct()
    {
        // récupération des variables du .env
        $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
        $dbName = $_ENV['DB_NAME'] ?? 'SAE_S3_BUT2_INFO';
        $dbUser = $_ENV['DB_USER'] ?? 'admin';
        $dbPass = $_ENV['DB_PASS'] ?? 'Pokemon.v.5';

        // dsn de connexion
        $_dsn = 'mysql:dbname=' . $dbName . ';host=' . $dbHost;

        // on appelle le constructeur de la classe pdo
        try {
            parent::__construct($_dsn, $dbUser, $dbPass);
            $this->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, 'SET NAMES utf8');
            $this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
            $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

    public static function getInstance(): self
    {
        // si l'instance n'existe pas, on la crée
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}