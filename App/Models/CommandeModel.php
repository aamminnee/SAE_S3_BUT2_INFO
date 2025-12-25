<?php
namespace App\Models;

use App\Core\Model;
use DateTime;

class CommandeModel extends Model
{
    // définition de la table principale
    protected $table = 'commande';

    // enregistre les détails d'une nouvelle commande dans la base de données
    public function saveCommande($user_id, $images_id, $adresse, $code_postal, $ville, $pays, $telephone, $montant, $id_cord)
    {
        $sql = "INSERT INTO {$this->table} 
                (id_user, id_images, adresse, code_postal, ville, pays, telephone, montant, id_cord) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        // exécution de la requête via la méthode du parent
        $this->requete($sql, [
            $user_id,
            $images_id,
            $adresse,
            $code_postal,
            $ville,
            $pays,
            $telephone,
            $montant,
            $id_cord
        ]);

        // on retourne l'id de la commande insérée (pdo)
        return $this->db->lastInsertId();
    }

    // récupère les détails d'une commande par id, incluant les données de l'image associée
    public function getCommandeById($commande_id)
    {
        $sql = "SELECT commande.*, images.identifiant as image_identifiant, images.type as image_type 
                FROM {$this->table} 
                INNER JOIN images ON commande.id_images = images.id 
                WHERE id_commande = ?";

        // retourne un objet (fetch)
        return $this->requete($sql, [$commande_id])->fetch();
    }

    // récupère toutes les commandes d'un utilisateur spécifique triées par date
    public function getCommandeByUserId($user_id)
    {
        $sql = "SELECT commande.*, images.identifiant as image_identifiant, images.type as image_type
                FROM {$this->table} 
                INNER JOIN images ON commande.id_images = images.id 
                WHERE commande.id_user = ?
                ORDER BY commande.date_commande DESC";

        // retourne un tableau d'objets (fetchall)
        return $this->requete($sql, [$user_id])->fetchAll();
    }

    // calcule le statut de la commande en fonction du temps écoulé depuis la date de commande
    public function getCommandeStatusById($id_commande)
    {
        $sql = "SELECT date_commande FROM {$this->table} WHERE id_commande = ?";
        
        // exécution de la requête
        $commande = $this->requete($sql, [$id_commande])->fetch();

        // vérification si la commande existe et a une date
        if (!$commande || empty($commande->date_commande)) {
            return "Inconnue";
        }

        // calcul de la différence entre la date de commande et la date actuelle
        $dateCommande = new DateTime($commande->date_commande);
        $now = new DateTime();
        $interval = $now->diff($dateCommande);
        $days = (int)$interval->format('%a');

        // logique métier pour le statut
        if ($days < 2) {
            return "En attente";
        } elseif ($days < 7) {
            return "Expédiée";
        } else {
            return "Livrée";
        }
    }
}