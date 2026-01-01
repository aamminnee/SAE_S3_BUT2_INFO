<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Db;
use PDO;
use Exception;

class FinancialModel extends Model {
    
    public function processOrder($userId, $mosaicId, $cardInfo, $amount, $billingInfo = []) {
        $db = Db::getInstance();
        
        try {
            // // début de la transaction
            $db->beginTransaction();

            // // --- etape 1 : savecustomer (infos personnelles sans adresse) ---
            $firstName = $billingInfo['first_name'];
            $lastName = $billingInfo['last_name'];
            $email = $billingInfo['email'];
            
            $sqlSave = "INSERT INTO SaveCustomer (first_name, last_name, email) 
                        VALUES (?, ?, ?)";
            $stmtSave = $db->prepare($sqlSave);
            $stmtSave->execute([
                $firstName, 
                $lastName, 
                $email
            ]);
            $idSaveCustomer = $db->lastInsertId();

            // // mise à jour téléphone dans customer
            if (!empty($billingInfo['phone'])) {
                $cleanPhone = preg_replace('/[^0-9]/', '', $billingInfo['phone']);
                if (strlen($cleanPhone) > 15) $cleanPhone = substr($cleanPhone, 0, 15);
                $stmtPhone = $db->prepare("UPDATE Customer SET phone = ? WHERE id_Customer = ?");
                $stmtPhone->execute([$cleanPhone, $userId]);
            }

            // // --- etape 2 : banque ---
            $sqlBank = "INSERT INTO BankDetails (id_Customer, bank_name, card_number, expire_at, cvc) 
                        VALUES (?, ?, ?, ?, ?)";
            $stmtBank = $db->prepare($sqlBank);
            $cardNumberSafe = substr(str_replace(' ', '', $cardInfo['number']), -16); 

            $stmtBank->execute([
                $userId, 
                'N/A', 
                $cardNumberSafe, 
                $cardInfo['expiry'], 
                $cardInfo['cvv']
            ]);
            $idBankDetails = $db->lastInsertId();

            // // --- etape 3 : commande ---
            $stmtImg = $db->prepare("SELECT id_Image FROM Mosaic WHERE id_Mosaic = ?");
            $stmtImg->execute([$mosaicId]);
            $idImage = $stmtImg->fetchColumn();

            if (!$idImage) throw new Exception("Image introuvable");

            $sqlOrder = "INSERT INTO CustomerOrder (order_date, status, total_amount, id_Customer, id_Image, id_Mosaic) 
                         VALUES (NOW(), 'Payée', ?, ?, ?, ?)";
            $stmtOrder = $db->prepare($sqlOrder);
            $stmtOrder->execute([$amount, $userId, $idImage, $mosaicId]);
            $orderId = $db->lastInsertId();

            // // --- etape 4 : facture (invoice) avec l'adresse complète ---
            $invoiceNumber = 'FAC-' . date('Ymd') . '-' . $orderId;
            $adress = $billingInfo['adress'] ?? ''; // on récupère l'adresse complète

            // // insertion dans invoice qui contient la colonne 'adress'
            $sqlInvoice = "INSERT INTO Invoice (invoice_number, issue_date, total_amount, id_Order, order_date, order_status, id_Bank_Details, id_SaveCustomer, adress) 
                           VALUES (?, NOW(), ?, ?, NOW(), 'Payée', ?, ?, ?)";
            $stmtInvoice = $db->prepare($sqlInvoice);
            $stmtInvoice->execute([
                $invoiceNumber,
                $amount,
                $orderId,
                $idBankDetails,
                $idSaveCustomer,
                $adress
            ]);

            $db->commit();
            return $orderId;

        } catch (Exception $e) {
            $db->rollBack();
            return "Erreur SQL : " . $e->getMessage();
        }
    }
}