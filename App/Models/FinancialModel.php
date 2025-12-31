<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Db;
use PDO;
use Exception;

class FinancialModel extends Model {
    
    /**
     * Traite la commande complète : SaveCustomer -> BankDetails -> CustomerOrder -> Invoice
     * Retourne l'ID de la commande en cas de succès, ou un message d'erreur (string) en cas d'échec.
     */
    public function processOrder($userId, $mosaicId, $cardInfo, $amount, $billingInfo = []) {
        $db = Db::getInstance();
        
        try {
            // // Début de la transaction
            $db->beginTransaction();

            // // --- ETAPE 1 : Gestion des infos client (SaveCustomer) ---
            
            // // Découpage du nom (ex: "Jean Dupont" -> "Jean", "Dupont")
            $parts = explode(' ', $billingInfo['card_holder'] ?? 'Client Inconnu', 2);
            $firstName = $parts[0];
            $lastName = $parts[1] ?? '';
            
            // // Email par défaut si non trouvé en session
            $email = $_SESSION['user_email'] ?? 'client@legofactory.com';
            
            // // Valeurs par défaut pour adresse
            $address = $billingInfo['address'] ?? 'Adresse non fournie';
            $postalCode = '00000';
            $city = 'Inconnue';

            $sqlSave = "INSERT INTO SaveCustomer (first_name, last_name, email, adress, postal_code, city) 
                        VALUES (?, ?, ?, ?, ?, ?)";
            $stmtSave = $db->prepare($sqlSave);
            $stmtSave->execute([
                $firstName, 
                $lastName, 
                $email,
                $address, 
                $postalCode, 
                $city
            ]);
            $idSaveCustomer = $db->lastInsertId();

            // // Mise à jour du téléphone dans la table Customer
            if (!empty($billingInfo['phone'])) {
                // // Nettoyage : on ne garde que les chiffres pour éviter l'erreur "Data too long"
                $cleanPhone = preg_replace('/[^0-9]/', '', $billingInfo['phone']);
                
                // // On tronque à 10 ou 15 caractères selon la limite de votre BDD (souvent 10 ou 20)
                // // Ici on prend 10 pour être sûr (format 0600000000)
                if (strlen($cleanPhone) > 15) {
                    $cleanPhone = substr($cleanPhone, 0, 15);
                }

                $stmtPhone = $db->prepare("UPDATE Customer SET phone = ? WHERE id_Customer = ?");
                $stmtPhone->execute([$cleanPhone, $userId]);
            }

            // // --- ETAPE 2 : Enregistrement Bancaire (BankDetails) ---
            
            $sqlBank = "INSERT INTO BankDetails (id_Customer, bank_name, card_number, expire_at, cvc) 
                        VALUES (?, ?, ?, ?, ?)";
            $stmtBank = $db->prepare($sqlBank);
            
            // // Sécurité : On ne stocke que les 4 derniers chiffres ou on masque
            // // Mais pour votre exercice, on stocke tel quel si nécessaire, ou juste la fin.
            // // Attention : stocker le numéro complet en clair n'est pas recommandé en prod.
            $cardNumberSafe = substr(str_replace(' ', '', $cardInfo['number']), -16); 

            $stmtBank->execute([
                $userId, 
                'N/A', 
                $cardNumberSafe, 
                $cardInfo['expiry'], 
                $cardInfo['cvv']
            ]);
            $idBankDetails = $db->lastInsertId();

            // // --- ETAPE 3 : Création de la commande (CustomerOrder) ---
            
            // // On récupère l'id_Image parent associé à la mosaïque
            $stmtImg = $db->prepare("SELECT id_Image FROM Mosaic WHERE id_Mosaic = ?");
            $stmtImg->execute([$mosaicId]);
            $idImage = $stmtImg->fetchColumn();

            if (!$idImage) {
                throw new Exception("Image introuvable pour la mosaïque ID $mosaicId");
            }

            // // Insertion de la commande
            // // Note : Cela suppose que vous avez exécuté le ALTER TABLE pour changer la FK vers Image
            $sqlOrder = "INSERT INTO CustomerOrder (order_date, status, total_amount, id_Customer, id_Image, id_Mosaic) 
                         VALUES (NOW(), 'Payée', ?, ?, ?, ?)";
            $stmtOrder = $db->prepare($sqlOrder);
            $stmtOrder->execute([$amount, $userId, $idImage, $mosaicId]);
            $orderId = $db->lastInsertId();

            // // --- ETAPE 4 : Création de la facture (Invoice) ---
            
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

            // // Tout s'est bien passé, on valide
            $db->commit();
            return $orderId;

        } catch (Exception $e) {
            // // En cas d'erreur, on annule tout
            $db->rollBack();
            // // On retourne le message d'erreur pour affichage
            return "Erreur SQL : " . $e->getMessage();
        }
    }
}