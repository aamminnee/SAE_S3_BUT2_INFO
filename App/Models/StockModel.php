<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Db;
use PDO;

class StockModel extends Model
{
    protected $table = 'Item';

    /**
     * Récupère le stock avec Pagination et Filtres
     * C'est la méthode principale pour ton tableau Admin
     */
    public function getPaginatedStock($limit, $page, $shapeFilter = null, $colorFilter = null)
    {
        $offset = ($page - 1) * $limit;
        $params = [];
        $whereClause = "";

        // Construction dynamique des filtres
        if (!empty($shapeFilter)) {
            $whereClause .= " AND s.name = :shape";
            $params[':shape'] = $shapeFilter;
        }
        if (!empty($colorFilter)) {
            $whereClause .= " AND c.name = :color";
            $params[':color'] = $colorFilter;
        }

        // La grosse requête optimisée
        $sql = "SELECT 
                    i.id_Item, 
                    s.name AS shape_name, 
                    c.name AS color_name,
                    c.hex_color,
                    i.price,
                    (IFNULL(e.total_entries, 0) - IFNULL(v.total_sales, 0)) AS current_stock
                FROM Item i
                JOIN Shapes s ON i.shape_id = s.id_shape
                JOIN Colors c ON i.color_id = c.id_color
                LEFT JOIN (
                    SELECT id_Item, SUM(quantity) AS total_entries 
                    FROM StockEntry 
                    GROUP BY id_Item
                ) e ON i.id_Item = e.id_Item
                LEFT JOIN (
                    SELECT id_Item, SUM(quantity) AS total_sales 
                    FROM OrderItem 
                    GROUP BY id_Item
                ) v ON i.id_Item = v.id_Item
                WHERE 1=1 $whereClause
                ORDER BY s.name, c.name
                LIMIT :limit OFFSET :offset";

        // On utilise PDO directement pour pouvoir binder LIMIT et OFFSET en INT (crucial)
        $db = Db::getInstance();
        $stmt = $db->prepare($sql);

        // Bind des filtres
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }

        // Bind de la pagination (impératif en INT pour MySQL)
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Compte le nombre total de résultats (pour calculer le nombre de pages)
     * Prend en compte les filtres actifs !
     */
    public function countStockItems($shapeFilter = null, $colorFilter = null)
    {
        $params = [];
        $whereClause = "";

        if (!empty($shapeFilter)) {
            $whereClause .= " AND s.name = ?";
            $params[] = $shapeFilter;
        }
        if (!empty($colorFilter)) {
            $whereClause .= " AND c.name = ?";
            $params[] = $colorFilter;
        }

        $sql = "SELECT COUNT(*) as total
                FROM Item i
                JOIN Shapes s ON i.shape_id = s.id_shape
                JOIN Colors c ON i.color_id = c.id_color
                WHERE 1=1 $whereClause";

        // Ici on peut utiliser la méthode requete du parent car pas de LIMIT
        $res = $this->requete($sql, $params)->fetch();
        return $res->total;
    }

    /**
     * Récupère la liste complète pour la Datalist (Recherche rapide)
     * Optimisé pour ne récupérer que l'essentiel
     */
    public function getAllItemsForSearch()
    {
        $sql = "SELECT 
                    i.id_Item, 
                    CONCAT(s.name, ' - ', c.name) AS label 
                FROM Item i
                JOIN Shapes s ON i.shape_id = s.id_shape
                JOIN Colors c ON i.color_id = c.id_color
                ORDER BY s.name, c.name";
        
        return Db::getInstance()->query($sql)->fetchAll();
    }

    // Pour remplir le select de filtres "Formes"
    public function getAllShapes() {
        return Db::getInstance()->query("SELECT DISTINCT name FROM Shapes ORDER BY name")->fetchAll();
    }

    // Pour remplir le select de filtres "Couleurs"
    public function getAllColors() {
        return Db::getInstance()->query("SELECT DISTINCT name FROM Colors ORDER BY name")->fetchAll();
    }

    // Ta méthode d'update inchangée
    public function updateStock($itemId, $quantity){
        $sql = "INSERT INTO StockEntry (id_Item, quantity) VALUES (?, ?)";
        return $this->requete($sql, [$itemId, $quantity]);
    }

    public function countLowStockItems($threshold = 10) {
        // On reprend la logique entrées - sorties
        $sql = "SELECT COUNT(*) as total FROM (
                    SELECT 
                        (IFNULL(e.total_entries, 0) - IFNULL(v.total_sales, 0)) AS current_stock
                    FROM Item i
                    LEFT JOIN (SELECT id_Item, SUM(quantity) AS total_entries FROM StockEntry GROUP BY id_Item) e ON i.id_Item = e.id_Item
                    LEFT JOIN (SELECT id_Item, SUM(quantity) AS total_sales FROM OrderItem GROUP BY id_Item) v ON i.id_Item = v.id_Item
                ) as real_stock
                WHERE current_stock < ?";
                
        $stmt = \App\Core\Db::getInstance()->prepare($sql);
        $stmt->execute([$threshold]);
        $res = $stmt->fetch();
        return $res->total ?? 0;
    }

    /**
     * Récupère toutes les données nécessaires pour générer briques.txt
     * (Formes, Couleurs, Prix, Stock Réel)
     */
    /**
     * Récupère les données géométriques précises (width, length, hole)
     * pour la génération du fichier briques.txt
     */
    public function getFullStockDetails()
    {
        // AJOUT DE c.id_color
        $sql = "SELECT 
                    s.width, 
                    s.length, 
                    s.hole,
                    c.id_color,     
                    c.hex_color,
                    i.price,
                    (IFNULL(e.total_entries, 0) - IFNULL(v.total_sales, 0)) AS current_stock
                FROM Item i
                JOIN Shapes s ON i.shape_id = s.id_shape
                JOIN Colors c ON i.color_id = c.id_color
                LEFT JOIN (SELECT id_Item, SUM(quantity) AS total_entries FROM StockEntry GROUP BY id_Item) e ON i.id_Item = e.id_Item
                LEFT JOIN (SELECT id_Item, SUM(quantity) AS total_sales FROM OrderItem GROUP BY id_Item) v ON i.id_Item = v.id_Item";
        
        return \App\Core\Db::getInstance()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }


}