<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Db;
use PDO;
use PDOException;

class ImagesModel extends Model {
    protected $table = 'Image';

    // // save initial image from upload
    public function saveCustomerImage($idCustomer, $imgData, $fileName, $mimeType) {
        $db = Db::getInstance();

        try {
            // // start transaction
            $db->beginTransaction();

            // // insert into parent table 'image'
            $sqlImage = "INSERT INTO Image (filename, id_Customer) VALUES (?, ?)";
            $stmt = $db->prepare($sqlImage);
            $stmt->execute([$fileName, $idCustomer]);

            // // get generated id
            $idImage = $db->lastInsertId();

            // // insert into child table 'customerimage' with blob
            $sqlCustomer = "INSERT INTO CustomerImage (id_Image, file, file_type) VALUES (?, ?, ?)";
            $stmt2 = $db->prepare($sqlCustomer);
            
            $stmt2->bindParam(1, $idImage);
            $stmt2->bindParam(2, $imgData, PDO::PARAM_LOB);
            $stmt2->bindParam(3, $mimeType);
            
            $stmt2->execute();

            // // commit transaction
            $db->commit();

            return $idImage;

        } catch (PDOException $e) {
            // // rollback on error
            $db->rollBack();
            throw $e;
        }
    }

    // // update existing image blob (for crop feature)
    public function updateCustomerImageBlob($idImage, $idCustomer, $newData) {
        $db = Db::getInstance();
        
        // // update blob only if image belongs to customer
        $sql = "UPDATE CustomerImage c
                INNER JOIN Image i ON c.id_Image = i.id_Image
                SET c.file = ?
                WHERE c.id_Image = ? AND i.id_Customer = ?";
                
        $stmt = $db->prepare($sql);
        $stmt->bindParam(1, $newData, PDO::PARAM_LOB);
        $stmt->bindParam(2, $idImage, PDO::PARAM_INT);
        $stmt->bindParam(3, $idCustomer, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    // // get image by id with optional user security check
    public function getImageById($id, $userId = null) {
        // // join tables to get file content and metadata
        $sql = "SELECT i.id_Image, i.filename, i.id_Customer, c.file, c.file_type 
                FROM Image i
                JOIN CustomerImage c ON i.id_Image = c.id_Image
                WHERE i.id_Image = ?";
        
        $params = [$id];

        // // if userid provided, add security check
        if ($userId !== null) {
            $sql .= " AND i.id_Customer = ?";
            $params[] = $userId;
        }
        
        return $this->requete($sql, $params)->fetch();
    }

    // // get last uploaded image for a specific user
    public function getLastImageByUserId($userId) {
        // // get the most recent image for user
        $sql = "SELECT i.id_Image, i.filename, i.id_Customer, c.file, c.file_type 
                FROM Image i
                JOIN CustomerImage c ON i.id_Image = c.id_Image
                WHERE i.id_Customer = ? 
                ORDER BY i.id_Image DESC 
                LIMIT 1";
        
        return $this->requete($sql, [$userId])->fetch();
    }
}