<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Db;
use PDO;

/**
 * StockModel
 * * Manages the inventory of LEGO parts.
 * * Provides methods to query stock levels, filter items, and update quantities.
 */
class StockModel extends Model {
    protected $table = 'Item';

    public function getPaginatedStock($limit, $page, $shapeFilter = null, $colorFilter = null) {
        $offset = ($page - 1) * $limit;
        $params = [];
        $whereClause = "";

        if (!empty($shapeFilter)) {
            $whereClause .= " AND s.name = :shape";
            $params[':shape'] = $shapeFilter;
        }
        if (!empty($colorFilter)) {
            $whereClause .= " AND c.name = :color";
            $params[':color'] = $colorFilter;
        }

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


    public function countStockItems($shapeFilter = null, $colorFilter = null) {
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

        $res = $this->requete($sql, $params)->fetch();
        return $res->total;
    }

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

    public function getAllShapes() {
        return Db::getInstance()->query("SELECT DISTINCT name FROM Shapes ORDER BY name")->fetchAll();
    }

    public function getAllColors() {
        return Db::getInstance()->query("SELECT DISTINCT name FROM Colors ORDER BY name")->fetchAll();
    }

    public function updateStock($itemId, $quantity){
        $sql = "INSERT INTO StockEntry (id_Item, quantity) VALUES (?, ?)";
        return $this->requete($sql, [$itemId, $quantity]);
    }

    public function countLowStockItems($threshold = 10) {
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

    public function getFullStockDetails() {
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