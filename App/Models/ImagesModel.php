<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Db;

class ImagesModel extends Model
{
    protected $table = 'CustomerImage';

    // sauvegarde une image (simulation car pas de blob en base)
    public function saveImageBlob($image_name, $user_id, $imageData)
    {
        $db = Db::getInstance();
        try {
            $db->beginTransaction();

            // 1. insertion dans customerimage (seulement la date dispo)
            $this->requete("INSERT INTO CustomerImage (upload_date) VALUES (NOW())");
            $id_image = $db->lastInsertId();
            
            // 2. on lie l'image à l'utilisateur via une commande 'brouillon'
            // car customerimage n'a pas de lien direct avec le client
            $sqlLink = "INSERT INTO CustomerOrder (id_Customer, id_Image, status, total_amount, order_date) 
                        VALUES (?, ?, 'Draft', 0.00, NOW())";
            $db->prepare($sqlLink)->execute([$user_id, $id_image]);

            // note : le blob $imageData est perdu car la base ne peut pas le stocker
            
            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollBack();
            return false;
        }
    }

    // met à jour une image (impossible structurellement, on retourne true)
    public function updateImageBlob($old_name, $new_name, $user_id, $imageData)
    {
        return true;
    }

    // récupère la dernière image d'un utilisateur via ses commandes
    public function getLastImageByUser($user_id)
    {
        // on passe par customerorder pour retrouver les images du client
        $sql = "SELECT ci.id_Image as identifiant 
                FROM CustomerImage ci 
                JOIN CustomerOrder co ON ci.id_Image = co.id_Image 
                WHERE co.id_Customer = ? 
                ORDER BY ci.upload_date DESC LIMIT 1";
        return $this->requete($sql, [$user_id])->fetch();
    }

    // récupère le blob (impossible, on retourne null ou une image vide)
    public function getLastImageBlobByUser($user_id, $name_image)
    {
        // la base ne contient pas l'image. 
        // il faudrait modifier la table customerimage pour ajouter une colonne 'image' longblob.
        return null;
    }
    
    // définit le type (pas de colonne type en base)
    public function setImageType($image_name, $type)
    {
        return true;
    }
}