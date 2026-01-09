<?php
namespace App\Core;

/**
 * Base Model Class
 * * Provides generic database interaction methods.
 * * Uses the Db singleton to execute queries.
 */
class Model {
    protected $table;

    private $db;

    public function findAll() {
        $query = $this->requete('SELECT * FROM ' . $this->table);
        return $query->fetchAll();
    }

    public function find(int $id) {
        return $this->requete("SELECT * FROM {$this->table} WHERE id = ?", [$id])->fetch();
    }

    public function requete(string $sql, array $attributs = null) {
        $this->db = Db::getInstance();

        if ($attributs !== null) {
            $query = $this->db->prepare($sql);
            $query->execute($attributs);
            return $query;
        } else {
            return $this->db->query($sql);
        }
    }
}