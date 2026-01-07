<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Db;
use PDO;
use Exception;

class FinancialModel extends Model {
    
    // On garde $refMosaicId juste pour récupérer l'id_Image (pour avoir une image de couverture)
    public function processOrder($userId, $refMosaicId, $cardInfo, $amount, $billingInfo = []) {
        $db = Db::getInstance();
        
        try {
            $db->beginTransaction();

            // --- ETAPE 1 : Infos Client ---
            $firstName = $billingInfo['first_name'];
            $lastName = $billingInfo['last_name'];
            $email = $billingInfo['email'];
            
            $sqlSave = "INSERT INTO SaveCustomer (first_name, last_name, email) VALUES (?, ?, ?)";
            $stmtSave = $db->prepare($sqlSave);
            $stmtSave->execute([$firstName, $lastName, $email]);
            $idSaveCustomer = $db->lastInsertId();

            // Mise à jour téléphone
            if (!empty($billingInfo['phone'])) {
                $cleanPhone = substr(preg_replace('/[^0-9]/', '', $billingInfo['phone']), 0, 15);
                $stmtPhone = $db->prepare("UPDATE Customer SET phone = ? WHERE id_Customer = ?");
                $stmtPhone->execute([$cleanPhone, $userId]);
            }

            // --- ETAPE 2 : Banque ---
            $sqlBank = "INSERT INTO BankDetails (id_Customer, bank_name, card_number, expire_at, cvc) VALUES (?, ?, ?, ?, ?)";
            $stmtBank = $db->prepare($sqlBank);
            $cardNumberSafe = substr(str_replace(' ', '', $cardInfo['number']), -16); 
            $stmtBank->execute([$userId, 'N/A', $cardNumberSafe, $cardInfo['expiry'], $cardInfo['cvv']]);
            $idBankDetails = $db->lastInsertId();

            // --- ETAPE 3 : Commande (CORRECTION ICI) ---
            // On récupère l'ID image de la mosaïque de référence
            $stmtImg = $db->prepare("SELECT id_Image FROM Mosaic WHERE id_Mosaic = ?");
            $stmtImg->execute([$refMosaicId]);
            $idImage = $stmtImg->fetchColumn(); // Peut être null, ce n'est pas grave

            // ON N'INSÈRE PLUS id_Mosaic ICI car la relation est maintenant dans l'autre sens
            $sqlOrder = "INSERT INTO CustomerOrder (order_date, status, total_amount, id_Customer, id_Image) 
                         VALUES (NOW(), 'Payée', ?, ?, ?)";
            $stmtOrder = $db->prepare($sqlOrder);
            $stmtOrder->execute([$amount, $userId, $idImage]);
            $orderId = $db->lastInsertId();

            // --- ETAPE 4 : Facture ---
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
            // On retourne l'erreur pour l'afficher dans le contrôleur
            return "Erreur SQL : " . $e->getMessage();
        }
    }
}