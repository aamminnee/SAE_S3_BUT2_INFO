<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Db;
use PDO;

class CommandeModel extends Model {
    // // Pas strictement utilisé ici mais bonne pratique
    protected $table = 'CustomerOrder';

    public function getOrderDetails($orderId) {
        $db = Db::getInstance();
        
        $sql = "SELECT 
                    co.id_Order, 
                    co.total_amount, 
                    co.order_date,
                    i.invoice_number, 
                    i.issue_date,
                    sc.first_name, 
                    sc.last_name, 
                    sc.adress,  -- // C'est ici que l'adresse est récupérée
                    sc.email,
                    c.phone
                FROM CustomerOrder co
                LEFT JOIN Invoice i ON co.id_Order = i.id_Order
                LEFT JOIN SaveCustomer sc ON i.id_SaveCustomer = sc.id_SaveCustomer
                LEFT JOIN Customer c ON co.id_Customer = c.id_Customer
                WHERE co.id_Order = ?";
                
        $stmt = $db->prepare($sql);
        $stmt->execute([$orderId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}