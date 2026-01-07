<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Db;
use PDO;

class AdminModel extends Model {
    protected $db;

    // // constructeur pour la connexion bdd
    public function __construct() {
        if (method_exists(parent::class, '__construct')) {
            parent::__construct();
        }
        if (!isset($this->db) || $this->db === null) {
            $this->db = Db::getInstance();
        }
    }

    // // récupère les commandes usine avec les détails des articles
    public function getFactoryOrdersWithDetails() {
        // // on joint les tables factoryorder, details, item, shapes et colors
        $sql = "
            SELECT 
                fo.id_FactoryOrder,
                fo.order_date,
                fo.total_price,
                fod.quantity,
                i.id_Item,
                s.name AS shape_name,
                c.name AS color_name
            FROM FactoryOrder fo
            JOIN FactoryOrderDetails fod ON fo.id_FactoryOrder = fod.id_FactoryOrder
            JOIN Item i ON fod.id_Item = i.id_Item
            JOIN Shapes s ON i.shape_id = s.id_shape
            JOIN Colors c ON i.color_id = c.id_color
            ORDER BY fo.order_date DESC, fo.id_FactoryOrder
        ";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // // garde cette méthode si utilisée ailleurs (stats, etc)
    public function getAllStock() {
        $sql = "SELECT * FROM View_StockStatus"; // // ou votre requête habituelle
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}