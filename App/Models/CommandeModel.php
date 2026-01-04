<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Db;
use PDO;

class CommandeModel extends Model {
    protected $table = 'CustomerOrder';

    public function updateStatus($id, $status) {
        $db = Db::getInstance();
        $sql = "UPDATE " . $this->table . " SET status = ? WHERE id_Order = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$status, $id]);
    }
    
    // récupère les détails pour la facture (utilisé par paymentcontroller)
    public function getOrderDetails($orderId) {
        $db = Db::getInstance();
        
        $sql = "SELECT 
                    co.id_Order, 
                    co.total_amount, 
                    co.order_date,
                    i.invoice_number, 
                    i.issue_date,
                    i.adress, 
                    sc.first_name, 
                    sc.last_name, 
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

    // récupère toutes les commandes d'un utilisateur
    public function getCommandeByUserId($userId) {
        $db = Db::getInstance();
        
        // correction : on récupère aussi id_Mosaic pour retrouver l'image générée
        $sql = "SELECT 
                    co.id_Order as id_commande,
                    co.order_date as date_commande,
                    co.total_amount as montant,
                    co.status,
                    co.id_Mosaic,
                    img.filename as image_identifiant, 
                    'original' as image_type 
                FROM CustomerOrder co
                LEFT JOIN Image img ON co.id_Image = img.id_Image
                WHERE co.id_Customer = ?
                ORDER BY co.order_date DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
        
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getCommandeById($id) {
        $db = Db::getInstance();
        
        // // on joint invoice pour récupérer l'adresse de livraison
        $sql = "SELECT co.*, co.id_Image as id_images, i.adress 
                FROM CustomerOrder co
                LEFT JOIN Invoice i ON co.id_Order = i.id_Order
                WHERE co.id_Order = ?";
                
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // récupère juste le statut (utilisé dans la boucle de la vue)
    public function getCommandeStatusById($id) {
        $db = Db::getInstance();
        $stmt = $db->prepare("SELECT status FROM CustomerOrder WHERE id_Order = ?");
        $stmt->execute([$id]);
        return $stmt->fetchColumn() ?: 'Inconnu';
    }
}