<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Db;
use PDO;
use PDOException;

class ImagesModel extends Model {
    protected $table = 'Image';

    // Sauvegarde de l'image (upload initial ou crop)
    public function saveCustomerImage($idCustomer, $imgData, $fileName, $mimeType) {
        $db = Db::getInstance();

        try {
            // Démarrer la transaction
            $db->beginTransaction();

            // 1. Insertion dans la table parente 'Image'
            $sqlImage = "INSERT INTO Image (filename, id_Customer) VALUES (?, ?)";
            $stmt = $db->prepare($sqlImage);
            $stmt->execute([$fileName, $idCustomer]);

            // Récupération de l'ID généré
            $idImage = $db->lastInsertId();

            // 2. Insertion dans la table enfant 'CustomerImage' avec le contenu binaire
            $sqlCustomer = "INSERT INTO CustomerImage (id_Image, file, file_type) VALUES (?, ?, ?)";
            $stmt2 = $db->prepare($sqlCustomer);
            
            // Utilisation de bindValue au lieu de bindParam pour éviter les problèmes de référence
            $stmt2->bindValue(1, $idImage);
            // Pour MySQL, PARAM_STR fonctionne très bien pour les BLOBs binaires.
            // PARAM_LOB peut causer des erreurs si $imgData est une string et non un stream.
            $stmt2->bindValue(2, $imgData, PDO::PARAM_STR);
            $stmt2->bindValue(3, $mimeType);
            
            $stmt2->execute();

            // Valider la transaction
            $db->commit();

            return $idImage;

        } catch (PDOException $e) {
            // Annuler en cas d'erreur si une transaction est active
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    // Mise à jour de l'image existante (si utilisé)
    public function updateCustomerImageBlob($idImage, $idCustomer, $newData) {
        $db = Db::getInstance();
        
        $sql = "UPDATE CustomerImage c
                INNER JOIN Image i ON c.id_Image = i.id_Image
                SET c.file = ?
                WHERE c.id_Image = ? AND i.id_Customer = ?";
                
        $stmt = $db->prepare($sql);
        // Ici aussi, préférez bindValue ou PARAM_STR
        $stmt->bindValue(1, $newData, PDO::PARAM_STR);
        $stmt->bindValue(2, $idImage, PDO::PARAM_INT);
        $stmt->bindValue(3, $idCustomer, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    public function getImageById($id, $userId = null) {
        $sql = "SELECT i.id_Image, i.filename, i.id_Customer, c.file, c.file_type 
                FROM Image i
                JOIN CustomerImage c ON i.id_Image = c.id_Image
                WHERE i.id_Image = ?";
        
        $params = [$id];

        if ($userId !== null) {
            $sql .= " AND i.id_Customer = ?";
            $params[] = $userId;
        }
        
        return $this->requete($sql, $params)->fetch();
    }

    public function getLastImageByUserId($userId) {
        $sql = "SELECT i.id_Image, i.filename, i.id_Customer, c.file, c.file_type 
                FROM Image i
                JOIN CustomerImage c ON i.id_Image = c.id_Image
                WHERE i.id_Customer = ? 
                ORDER BY i.id_Image DESC 
                LIMIT 1";
        
        return $this->requete($sql, [$userId])->fetch();
    }
}