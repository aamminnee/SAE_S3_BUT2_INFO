<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Db;
use PDO;

/**
 * StockModel
 * * Manages the inventory of LEGO parts.
 * * Uses ONLY StockEntry to calculate levels (Sum of imports and sales).
 */
class StockModel extends Model {
    protected $table = 'Item';

    // Récupération paginée avec filtres
    public function getPaginatedStock($limit, $page, $shapeFilter = null, $colorFilter = null, $statusFilter = 'all') {
        $offset = ($page - 1) * $limit;
        $params = [];
        $whereClause = "";

        // Filtres Forme et Couleur
        if (!empty($shapeFilter)) {
            $whereClause .= " AND s.name = :shape";
            $params[':shape'] = $shapeFilter;
        }
        if (!empty($colorFilter)) {
            $whereClause .= " AND c.name = :color";
            $params[':color'] = $colorFilter;
        }

        // Filtre par statut (basé uniquement sur la somme de StockEntry)
        if ($statusFilter === 'low') {
            $whereClause .= " AND IFNULL(e.current_stock, 0) < 50";
        } elseif ($statusFilter === 'critical') {
            $whereClause .= " AND IFNULL(e.current_stock, 0) < 0";
        }

        // Requête simplifiée : On ne joint plus OrderItem
        $sql = "SELECT 
                    i.id_Item, 
                    s.name AS shape_name, 
                    c.name AS color_name,
                    c.hex_color,
                    i.price,
                    IFNULL(e.current_stock, 0) AS current_stock
                FROM Item i
                JOIN Shapes s ON i.shape_id = s.id_shape
                JOIN Colors c ON i.color_id = c.id_color
                LEFT JOIN (
                    SELECT id_Item, SUM(quantity) AS current_stock 
                    FROM StockEntry 
                    GROUP BY id_Item
                ) e ON i.id_Item = e.id_Item
                WHERE 1=1 $whereClause
                ORDER BY s.name, c.name
                LIMIT :limit OFFSET :offset";

        $db = Db::getInstance();
        $stmt = $db->prepare($sql);

        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }

        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Comptage pour la pagination (adapté sans OrderItem)
    public function countStockItems($shapeFilter = null, $colorFilter = null, $statusFilter = 'all') {
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

        if ($statusFilter === 'low') {
            $whereClause .= " AND IFNULL(e.current_stock, 0) < 50";
        } elseif ($statusFilter === 'critical') {
            $whereClause .= " AND IFNULL(e.current_stock, 0) < 0";
        }

        $sql = "SELECT COUNT(*) as total
                FROM Item i
                JOIN Shapes s ON i.shape_id = s.id_shape
                JOIN Colors c ON i.color_id = c.id_color
                LEFT JOIN (
                    SELECT id_Item, SUM(quantity) AS current_stock 
                    FROM StockEntry 
                    GROUP BY id_Item
                ) e ON i.id_Item = e.id_Item
                WHERE 1=1 $whereClause";

        $res = $this->requete($sql, $params)->fetch();
        return $res->total;
    }

    // Recherche (Inchangé)
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

    // Listes déroulantes (Inchangé)
    public function getAllShapes() {
        return Db::getInstance()->query("SELECT DISTINCT name FROM Shapes ORDER BY name")->fetchAll();
    }

    public function getAllColors() {
        return Db::getInstance()->query("SELECT DISTINCT name FROM Colors ORDER BY name")->fetchAll();
    }

    // Mise à jour manuelle (Inchangé)
    public function updateStock($itemId, $quantity){
        $sql = "INSERT INTO StockEntry (id_Item, quantity) VALUES (?, ?)";
        return $this->requete($sql, [$itemId, $quantity]);
    }

    // Widget Dashboard (Adapté sans OrderItem)
    public function countLowStockItems($threshold = 50) {
        $sql = "SELECT COUNT(*) as total FROM (
                    SELECT 
                        IFNULL(e.current_stock, 0) AS current_stock
                    FROM Item i
                    LEFT JOIN (
                        SELECT id_Item, SUM(quantity) AS current_stock 
                        FROM StockEntry 
                        GROUP BY id_Item
                    ) e ON i.id_Item = e.id_Item
                ) as real_stock
                WHERE current_stock < ?";
                
        $stmt = \App\Core\Db::getInstance()->prepare($sql);
        $stmt->execute([$threshold]);
        $res = $stmt->fetch();
        return $res->total ?? 0;
    }

    // Export complet (Adapté sans OrderItem)
    public function getFullStockDetails() {
        $sql = "SELECT 
                    s.width, 
                    s.length, 
                    s.hole,
                    c.id_color,     
                    c.hex_color,
                    i.price,
                    IFNULL(e.current_stock, 0) AS current_stock
                FROM Item i
                JOIN Shapes s ON i.shape_id = s.id_shape
                JOIN Colors c ON i.color_id = c.id_color
                LEFT JOIN (
                    SELECT id_Item, SUM(quantity) AS current_stock 
                    FROM StockEntry 
                    GROUP BY id_Item
                ) e ON i.id_Item = e.id_Item";
        
        return \App\Core\Db::getInstance()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }
}