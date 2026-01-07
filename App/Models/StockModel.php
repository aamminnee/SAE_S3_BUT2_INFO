<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Db;

class StockModel extends Model
{
    // definition of the main table
    protected $table = 'Item';

    // constructor
    public function __construct()
    {
        // nothing specific here
    }

    // method to retrieve the entire calculated stock
    public function getAllStockItems()
    {
        // query inspired by the get_all_items_stock procedure
        // we calculate inputs - outputs to get the real stock
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
                ORDER BY s.name, c.name";

        return Db::getInstance()->query($sql);
    }

    // method to retrieve a simple list of items for the form
    public function getItemList()
    {
        // fetching id and name for the dropdown menu
        $sql = "SELECT 
                    i.id_Item, 
                    CONCAT(s.name, ' - ', c.name) AS label 
                FROM Item i
                JOIN Shapes s ON i.shape_id = s.id_shape
                JOIN Colors c ON i.color_id = c.id_color
                ORDER BY s.name, c.name";
        
        return Db::getInstance()->query($sql);
    }

    // method to add or remove stock
    public function updateStock($itemId, $quantity)
    {
        // // modification : on retire entry_date car la colonne n'existe pas en bdd
        // // on insere uniquement l'id de l'item et la quantite
        $sql = "INSERT INTO StockEntry (id_Item, quantity) VALUES (?, ?)";
        
        // // execution de la requete via la methode du parent
        return $this->requete($sql, [$itemId, $quantity]);
    }
}