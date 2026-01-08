<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Db;
use PDO;
use PDOException;

/**
 * ImagesModel
 * * Manages user-uploaded images stored as BLOBs in the database.
 */
class ImagesModel extends Model {
    protected $table = 'Image';

    public function saveCustomerImage($idCustomer, $imgData, $fileName, $mimeType) {
        $db = Db::getInstance();

        try {
            $db->beginTransaction();

            $sqlImage = "INSERT INTO Image (filename, id_Customer) VALUES (?, ?)";
            $stmt = $db->prepare($sqlImage);
            $stmt->execute([$fileName, $idCustomer]);

            $idImage = $db->lastInsertId();

            $sqlCustomer = "INSERT INTO CustomerImage (id_Image, file, file_type) VALUES (?, ?, ?)";
            $stmt2 = $db->prepare($sqlCustomer);
            
            $stmt2->bindValue(1, $idImage);
            $stmt2->bindValue(2, $imgData, PDO::PARAM_STR);
            $stmt2->bindValue(3, $mimeType);
            
            $stmt2->execute();

            $db->commit();

            return $idImage;

        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public function updateCustomerImageBlob($idImage, $idCustomer, $newData) {
        $db = Db::getInstance();
        
        $sql = "UPDATE CustomerImage c
                INNER JOIN Image i ON c.id_Image = i.id_Image
                SET c.file = ?
                WHERE c.id_Image = ? AND i.id_Customer = ?";
                
        $stmt = $db->prepare($sql);
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