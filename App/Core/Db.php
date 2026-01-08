<?php
namespace App\Core;

use PDO;
use PDOException;

/**
 * Database Connection Class
 * * Implements the Singleton Pattern to ensure a single database connection.
 * * Extends PDO for direct database manipulation.
 */
class Db extends PDO {
    private static $instance;

    private function __construct() {
        $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
        $dbName = $_ENV['DB_NAME'] ?? 'SAE_S3_BUT2_INFO';
        $dbUser = $_ENV['DB_USER'] ?? 'admin';
        $dbPass = $_ENV['DB_PASS'] ?? 'Pokemon.v.5';

        $_dsn = 'mysql:dbname=' . $dbName . ';host=' . $dbHost;

        try {
            parent::__construct($_dsn, $dbUser, $dbPass);
            $this->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, 'SET NAMES utf8');
            $this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
            $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}