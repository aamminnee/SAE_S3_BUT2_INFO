<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Db;
use PDO;
use PDOException;

class ImagesModel extends Model {
    // définition de la table principale
    protected $table = 'Image';

    /**
     * Sauvegarde une image en suivant votre structure spécifique :
     * 1. Table 'Image' : on stocke le nom du fichier (filename) et le propriétaire (id_Customer).
     * 2. Table 'CustomerImage' : on stocke le contenu binaire et le type, liés par id_Image.
     */
    public function saveCustomerImage($idCustomer, $imgData, $fileName, $mimeType) {
        $db = Db::getInstance();

        try {
            // début de la transaction
            $db->beginTransaction();

            // ---------------------------------------------------------
            // ÉTAPE 1 : Insertion dans la table parente 'Image'
            // ---------------------------------------------------------
            // on insère ici le filename et l'id_Customer comme demandé
            $sqlImage = "INSERT INTO Image (filename, id_Customer) VALUES (?, ?)";
            $stmt = $db->prepare($sqlImage);
            $stmt->execute([$fileName, $idCustomer]);

            // récupération de l'id de l'image qui vient d'être créée
            $idImage = $db->lastInsertId();

            // ---------------------------------------------------------
            // ÉTAPE 2 : Insertion dans la table enfant 'CustomerImage'
            // ---------------------------------------------------------
            // on ne met PAS id_Customer ici, seulement le lien vers l'image et les données
            $sqlCustomer = "INSERT INTO CustomerImage (id_Image, file, file_type) VALUES (?, ?, ?)";
            $stmt2 = $db->prepare($sqlCustomer);
            
            // liaison des paramètres
            $stmt2->bindParam(1, $idImage);
            $stmt2->bindParam(2, $imgData, PDO::PARAM_LOB); // gestion du blob
            $stmt2->bindParam(3, $mimeType);
            
            $stmt2->execute();

            // validation de la transaction
            $db->commit();

            return $idImage;

        } catch (PDOException $e) {
            // annulation en cas d'erreur
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Récupère l'image complète
     */
    public function getImageById($id) {
        // jointure adaptée à vos noms de colonnes
        $sql = "SELECT i.id_Image, i.filename, i.id_Customer, c.file, c.file_type 
                FROM Image i
                JOIN CustomerImage c ON i.id_Image = c.id_Image
                WHERE i.id_Image = ?";
        
        return $this->requete($sql, [$id])->fetch();
    }

    // méthode pour récupérer la dernière image de l'utilisateur
    public function getLastImageByUserId($userId) {
        // sélectionne l'image la plus récente pour cet utilisateur
        $sql = "SELECT * FROM Image WHERE id_Customer = ? ORDER BY id_Image DESC LIMIT 1";
        
        // exécute la requête et retourne le résultat
        return $this->requete($sql, [$userId])->fetch();
    }
}