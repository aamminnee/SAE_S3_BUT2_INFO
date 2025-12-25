<?php
namespace App\Core;

// la classe model ne doit pas étendre db, elle l'utilise via getinstance
class Model
{
    // table de la base de données
    protected $table;

    // instance de db
    private $db;

    public function findAll()
    {
        // on utilise la méthode requete définie plus bas
        $query = $this->requete('SELECT * FROM ' . $this->table);
        return $query->fetchAll();
    }

    public function find(int $id)
    {
        return $this->requete("SELECT * FROM {$this->table} WHERE id = ?", [$id])->fetch();
    }

    public function requete(string $sql, array $attributs = null)
    {
        // on récupère l'instance de db (singleton)
        $this->db = Db::getInstance();

        // on vérifie si on a des attributs
        if ($attributs !== null) {
            // requête préparée
            $query = $this->db->prepare($sql);
            $query->execute($attributs);
            return $query;
        } else {
            // requête simple
            return $this->db->query($sql);
        }
    }
}