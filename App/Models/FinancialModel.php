<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Db;
use PDO;
use Exception;

class FinancialModel extends Model {
    
    /**
     * traite la commande complète : savecustomer -> bankdetails -> customerorder -> invoice
     * retourne l'id de la commande en cas de succès, ou un message d'erreur (string) en cas d'échec.
     */
    public function processOrder($userId, $mosaicId, $cardInfo, $amount, $billingInfo = []) {
        $db = Db::getInstance();
        
        try {
            // // début de la transaction
            $db->beginTransaction();

            // // --- etape 1 : gestion des infos client (savecustomer) ---
            
            // // découpage du nom (ex: "jean dupont" -> "jean", "dupont")
            $parts = explode(' ', $billingInfo['card_holder'] ?? 'Client Inconnu', 2);
            $firstName = $parts[0];
            $lastName = $parts[1] ?? '';
            
            // // email par défaut si non trouvé en session
            $email = $_SESSION['user_email'] ?? 'client@legofactory.com';
            
            // // valeurs pour adresse (tout est dans billinginfo['address'])
            $address = $billingInfo['address'] ?? 'Adresse non fournie';

            // // correction : on insère uniquement l'adresse complète dans le champ 'adress'
            // // on ne cherche plus à insérer postal_code ou city
            $sqlSave = "INSERT INTO SaveCustomer (first_name, last_name, email, adress) 
                        VALUES (?, ?, ?, ?)";
            $stmtSave = $db->prepare($sqlSave);
            $stmtSave->execute([
                $firstName, 
                $lastName, 
                $email,
                $address
            ]);
            $idSaveCustomer = $db->lastInsertId();

            // // mise à jour du téléphone dans la table customer (si fourni)
            if (!empty($billingInfo['phone'])) {
                // // nettoyage : on ne garde que les chiffres
                $cleanPhone = preg_replace('/[^0-9]/', '', $billingInfo['phone']);
                
                if (strlen($cleanPhone) > 15) {
                    $cleanPhone = substr($cleanPhone, 0, 15);
                }

                $stmtPhone = $db->prepare("UPDATE Customer SET phone = ? WHERE id_Customer = ?");
                $stmtPhone->execute([$cleanPhone, $userId]);
            }

            // // --- etape 2 : enregistrement bancaire (bankdetails) ---
            
            $sqlBank = "INSERT INTO BankDetails (id_Customer, bank_name, card_number, expire_at, cvc) 
                        VALUES (?, ?, ?, ?, ?)";
            $stmtBank = $db->prepare($sqlBank);
            
            // // sécurité : on ne stocke que les 4 derniers chiffres ou on masque
            $cardNumberSafe = substr(str_replace(' ', '', $cardInfo['number']), -16); 

            $stmtBank->execute([
                $userId, 
                'N/A', 
                $cardNumberSafe, 
                $cardInfo['expiry'], 
                $cardInfo['cvv']
            ]);
            $idBankDetails = $db->lastInsertId();

            // // --- etape 3 : création de la commande (customerorder) ---
            
            // // on récupère l'id_image parent associé à la mosaïque
            $stmtImg = $db->prepare("SELECT id_Image FROM Mosaic WHERE id_Mosaic = ?");
            $stmtImg->execute([$mosaicId]);
            $idImage = $stmtImg->fetchColumn();

            if (!$idImage) {
                throw new Exception("Image introuvable pour la mosaïque ID $mosaicId");
            }

            // // insertion de la commande
            $sqlOrder = "INSERT INTO CustomerOrder (order_date, status, total_amount, id_Customer, id_Image, id_Mosaic) 
                         VALUES (NOW(), 'Payée', ?, ?, ?, ?)";
            $stmtOrder = $db->prepare($sqlOrder);
            $stmtOrder->execute([$amount, $userId, $idImage, $mosaicId]);
            $orderId = $db->lastInsertId();

            // // --- etape 4 : création de la facture (invoice) ---
            
            $invoiceNumber = 'FAC-' . date('Ymd') . '-' . $orderId;
            
            $sqlInvoice = "INSERT INTO Invoice (invoice_number, issue_date, total_amount, id_Order, order_date, order_status, id_Bank_Details, id_SaveCustomer) 
                           VALUES (?, NOW(), ?, ?, NOW(), 'Payée', ?, ?)";
            $stmtInvoice = $db->prepare($sqlInvoice);
            $stmtInvoice->execute([
                $invoiceNumber,
                $amount,
                $orderId,
                $idBankDetails,
                $idSaveCustomer
            ]);

            // // tout s'est bien passé, on valide la transaction
            $db->commit();
            return $orderId;

        } catch (Exception $e) {
            // // en cas d'erreur, on annule tout
            $db->rollBack();
            // // on retourne le message d'erreur pour affichage
            return "Erreur SQL : " . $e->getMessage();
        }
    }
}