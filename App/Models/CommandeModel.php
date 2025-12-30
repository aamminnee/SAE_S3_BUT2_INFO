<?php
namespace App\Models;

use App\Core\Model;
use DateTime;

class CommandeModel extends Model
{
    // définition de la table principale (nom réel dans la bdd)
    protected $table = 'CustomerOrder';

    // enregistre les détails d'une nouvelle commande dans la base de données
    // note : les champs adresse/code_postal/etc ne sont pas stockés dans CustomerOrder
    // ils sont gérés via la table SaveCustomer liée au Customer
    public function saveCommande($user_id, $images_id, $adresse, $code_postal, $ville, $pays, $telephone, $montant, $id_cord)
    {
        // insertion adaptée à la structure réelle de la table CustomerOrder
        // on définit le status par défaut à 'Paid' car la commande arrive ici après paiement
        $sql = "INSERT INTO {$this->table} 
                (id_Customer, id_Mosaic, total_amount, status, order_date) 
                VALUES (?, ?, ?, 'Paid', NOW())";

        // exécution de la requête via la méthode du parent
        // on ignore l'adresse et id_cord ici car la structure bdd ne les supporte pas dans cette table
        $this->requete($sql, [
            $user_id,
            $images_id, // correspond à id_Mosaic
            $montant
        ]);

        // on retourne l'id de la commande insérée (pdo)
        return $this->db->lastInsertId();
    }

    // récupère les détails d'une commande par id
    public function getCommandeById($commande_id)
    {
        // utilisation d'alias pour maintenir la compatibilité avec vos vues
        $sql = "SELECT 
                    co.id_Order as id_commande,
                    co.order_date as date_commande,
                    co.total_amount as montant,
                    co.status,
                    co.id_Customer as id_user,
                    co.id_Mosaic as id_images,
                    m.pavage as image_identifiant, -- récupération des données de la mosaïque
                    'mosaic' as image_type
                FROM {$this->table} co
                LEFT JOIN Mosaic m ON co.id_Mosaic = m.id_Mosaic 
                WHERE co.id_Order = ?";

        // retourne un objet (fetch)
        return $this->requete($sql, [$commande_id])->fetch();
    }

    // récupère toutes les commandes d'un utilisateur spécifique triées par date
    public function getCommandeByUserId($user_id)
    {
        // adaptation des colonnes pour correspondre à CustomerOrder
        $sql = "SELECT 
                    co.id_Order as id_commande,
                    co.order_date as date_commande,
                    co.total_amount as montant,
                    co.status,
                    co.id_Mosaic as id_images,
                    'Mosaic' as image_identifiant,
                    'standard' as image_type
                FROM {$this->table} co
                WHERE co.id_Customer = ?
                ORDER BY co.order_date DESC";

        // retourne un tableau d'objets (fetchall)
        return $this->requete($sql, [$user_id])->fetchAll();
    }

    // calcule le statut de la commande en fonction du temps écoulé depuis la date de commande
    public function getCommandeStatusById($id_commande)
    {
        // correction du nom de colonne : order_date au lieu de date_commande
        $sql = "SELECT order_date FROM {$this->table} WHERE id_Order = ?";
        
        // exécution de la requête
        $commande = $this->requete($sql, [$id_commande])->fetch();

        // vérification si la commande existe et a une date
        if (!$commande || empty($commande->order_date)) {
            return "Inconnue";
        }

        // calcul de la différence entre la date de commande et la date actuelle
        $dateCommande = new DateTime($commande->order_date);
        $now = new DateTime();
        $interval = $now->diff($dateCommande);
        $days = (int)$interval->format('%a');

        // logique métier pour le statut (livraison simulée)
        if ($days < 2) {
            return "En attente";
        } elseif ($days < 7) {
            return "Expédiée";
        } else {
            return "Livrée";
        }
    }
}