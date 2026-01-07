<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Db;
use PDO;
use Exception;

class FinancialModel extends Model {
    
    // // on garde $refmosaicid juste pour récupérer l'id_image (pour avoir une image de couverture)
    public function processOrder($userId, $refMosaicId, $cardInfo, $amount, $billingInfo = []) {
        $db = Db::getInstance();
        
        try {
            $db->beginTransaction();

            // // --- etape 1 : infos client ---
            $firstName = $billingInfo['first_name'];
            $lastName = $billingInfo['last_name'];
            $email = $billingInfo['email'];
            
            $sqlSave = "INSERT INTO SaveCustomer (first_name, last_name, email) VALUES (?, ?, ?)";
            $stmtSave = $db->prepare($sqlSave);
            $stmtSave->execute([$firstName, $lastName, $email]);
            $idSaveCustomer = $db->lastInsertId();

            // // mise à jour téléphone
            if (!empty($billingInfo['phone'])) {
                $cleanPhone = substr(preg_replace('/[^0-9]/', '', $billingInfo['phone']), 0, 15);
                $stmtPhone = $db->prepare("UPDATE Customer SET phone = ? WHERE id_Customer = ?");
                $stmtPhone->execute([$cleanPhone, $userId]);
            }

            // // --- etape 2 : banque ---
            // // nettoyage du numéro de carte avant hachage
            $rawCardNumber = str_replace(' ', '', $cardInfo['number']);
            
            // // hachage du numéro de carte et du cvc pour la sécurité
            $hashedCard = password_hash($rawCardNumber, PASSWORD_DEFAULT);
            $hashedCvc = password_hash($cardInfo['cvv'], PASSWORD_DEFAULT);

            $sqlBank = "INSERT INTO BankDetails (id_Customer, card_number, expire_at, cvc) VALUES (?, ?, ?, ?)";
            $stmtBank = $db->prepare($sqlBank);
            
            // // insertion des données hachées
            $stmtBank->execute([$userId, $hashedCard, $cardInfo['expiry'], $hashedCvc]);
            $idBankDetails = $db->lastInsertId();

            // // --- etape 3 : commande (correction ici) ---
            // // on récupère l'id image de la mosaïque de référence
            $stmtImg = $db->prepare("SELECT id_Image FROM Mosaic WHERE id_Mosaic = ?");
            $stmtImg->execute([$refMosaicId]);
            $idImage = $stmtImg->fetchColumn(); // // peut être null, ce n'est pas grave

            // // on n'insère plus id_mosaic ici car la relation est maintenant dans l'autre sens
            $sqlOrder = "INSERT INTO CustomerOrder (order_date, status, total_amount, id_Customer, id_Image) 
                         VALUES (NOW(), 'Payée', ?, ?, ?)";
            $stmtOrder = $db->prepare($sqlOrder);
            $stmtOrder->execute([$amount, $userId, $idImage]);
            $orderId = $db->lastInsertId();

            // // --- etape 4 : facture ---
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
            // // on retourne l'erreur pour l'afficher dans le contrôleur
            return "Erreur SQL : " . $e->getMessage();
        }
    }
}