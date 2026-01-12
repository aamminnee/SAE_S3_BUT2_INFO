<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Db;
use PDO;
use Exception;

/**
 * FinancialModel
 * * Manages payment processing and financial records.
 * * Handles the transaction logic: saving card info (mock), creating orders, and linking payments.
 */
class FinancialModel extends Model {
    
    public function processOrder($userId, $refMosaicId, $cardInfo, $amount, $billingInfo = []) {
        $db = Db::getInstance();
        
        try {
            $db->beginTransaction();

            // 1. Sauvegarde des infos client (Facturation)
            $firstName = $billingInfo['first_name'];
            $lastName = $billingInfo['last_name'];
            $email = $billingInfo['email'];
            
            $sqlSave = "INSERT INTO SaveCustomer (first_name, last_name, email) VALUES (?, ?, ?)";
            $stmtSave = $db->prepare($sqlSave);
            $stmtSave->execute([$firstName, $lastName, $email]);
            $idSaveCustomer = $db->lastInsertId();

            // 2. Mise à jour du téléphone si présent
            if (!empty($billingInfo['phone'])) {
                $cleanPhone = substr(preg_replace('/[^0-9]/', '', $billingInfo['phone']), 0, 15);
                $stmtPhone = $db->prepare("UPDATE Customer SET phone = ? WHERE id_Customer = ?");
                $stmtPhone->execute([$cleanPhone, $userId]);
            }

            // 3. STOCKAGE SÉCURISÉ DE LA CARTE (Modifié)
            // On nettoie le numéro pour le traitement
            $cleanNumber = str_replace(' ', '', $cardInfo['number']);

            // A. On ne garde que les 4 derniers chiffres (Règle PCI-DSS)
            $lastFour = substr($cleanNumber, -4);
            
            // B. On génère un "Token" fictif (Simulation du retour banque/Stripe/PayPal)
            $fakeToken = 'tok_' . bin2hex(random_bytes(10));

            // C. On devine la marque (Optionnel, pour le style)
            $brand = (isset($cleanNumber[0]) && $cleanNumber[0] == '4') ? 'Visa' : 'MasterCard';

            // D. Insertion : On n'insère PLUS le numéro complet NI le CVC
            $sqlBank = "INSERT INTO BankDetails (id_Customer, bank_name, last_four, expire_at, payment_token, card_brand) 
                        VALUES (?, ?, ?, ?, ?, ?)";
            $stmtBank = $db->prepare($sqlBank);
            // 'N/A' pour le nom de banque car on ne peut pas le deviner sans API réelle
            $stmtBank->execute([$userId, 'N/A', $lastFour, $cardInfo['expiry'], $fakeToken, $brand]);
            $idBankDetails = $db->lastInsertId();

            // 4. Suite de la commande (Inchangé)
            $stmtImg = $db->prepare("SELECT id_Image FROM Mosaic WHERE id_Mosaic = ?");
            $stmtImg->execute([$refMosaicId]);
            $idImage = $stmtImg->fetchColumn();

            $sqlOrder = "INSERT INTO CustomerOrder (order_date, status, total_amount, id_Customer, id_Image) 
                         VALUES (NOW(), 'Payée', ?, ?, ?)";
            $stmtOrder = $db->prepare($sqlOrder);
            $stmtOrder->execute([$amount, $userId, $idImage]);
            $orderId = $db->lastInsertId();

            $invoiceNumber = 'FAC-' . date('Ymd') . '-' . $orderId;
            $adress = $billingInfo['adress'] ?? ''; 

            $sqlInvoice = "INSERT INTO Invoice (invoice_number, issue_date, total_amount, id_Order, order_date, order_status, id_Bank_Details, id_SaveCustomer, adress) 
                           VALUES (?, NOW(), ?, ?, NOW(), 'Payée', ?, ?, ?)";
            $stmtInvoice = $db->prepare($sqlInvoice);
            $stmtInvoice->execute([$invoiceNumber, $amount, $orderId, $idBankDetails, $idSaveCustomer, $adress]);

            $db->commit();
            return $orderId;

        } catch (Exception $e) {
            $db->rollBack();
            return "Erreur SQL : " . $e->getMessage();
        }
    }

    public function getTotalRevenue() {
        $sql = "SELECT SUM(total_amount) as total FROM CustomerOrder WHERE status != 'Annulée'";
        $res = \App\Core\Db::getInstance()->query($sql)->fetch();
        return $res->total ?? 0;
    }

    public function countOrders() {
        $sql = "SELECT COUNT(*) as total FROM CustomerOrder";
        $res = \App\Core\Db::getInstance()->query($sql)->fetch();
        return $res->total ?? 0;
    }

    public function getLastOrders($limit = 5) {
        $sql = "SELECT 
                    o.id_Order as id, 
                    o.order_date as date, 
                    o.total_amount as amount, 
                    o.status,
                    CONCAT(c.first_name, ' ', c.last_name) as user
                FROM CustomerOrder o
                JOIN Customer cust ON o.id_Customer = cust.id_Customer
                JOIN SaveCustomer c ON cust.id_Customer = c.id_SaveCustomer 
                ORDER BY o.order_date DESC 
                LIMIT $limit";
        
        return \App\Core\Db::getInstance()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }
}