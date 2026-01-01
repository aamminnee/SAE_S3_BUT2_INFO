<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Db;
use PDOException;

class UsersModel extends Model {
    // définition de la table principale
    protected $table = 'Customer';

    // récupère toutes les infos d'un utilisateur par son id
    // on joint saveCustomer pour avoir les infos personnelles correspondant à la dernière version liée
    public function getUserById($id_user) {
        $sql = "SELECT 
                    c.id_Customer as id_user, 
                    c.password as mdp, 
                    c.etat, 
                    c.mode,
                    s.first_name as username, 
                    s.last_name, 
                    s.email 
                FROM Customer c 
                JOIN SaveCustomer s ON c.id_SaveCustomer = s.id_SaveCustomer 
                WHERE c.id_Customer = ?";
        
        return $this->requete($sql, [$id_user])->fetch();
    }

    // récupère l'utilisateur par son nom d'utilisateur (first_name dans savecustomer)
    public function getUserByUsername($username){
        $sql = "SELECT 
                    c.id_Customer as id_user, 
                    c.password as mdp, 
                    c.etat,
                    c.mode, 
                    s.first_name as username, 
                    s.email 
                FROM Customer c 
                JOIN SaveCustomer s ON c.id_SaveCustomer = s.id_SaveCustomer 
                WHERE s.first_name = ?";
        
        return $this->requete($sql, [$username])->fetch();
    }

    // récupère uniquement l'email par id utilisateur
    public function getEmailById($id_user) {
        $sql = "SELECT s.email 
                FROM Customer c 
                JOIN SaveCustomer s ON c.id_SaveCustomer = s.id_SaveCustomer 
                WHERE c.id_Customer = ?";
        
        return $this->requete($sql, [$id_user])->fetch();
    }

    // récupère le statut du compte directement depuis la table customer
    public function getStatusById($id_user) {
        return $this->requete("SELECT etat FROM Customer WHERE id_Customer = ?", [$id_user])->fetch();
    }
    
    // récupère le mode de sécurité (ex: 2fa) depuis la table customer
    public function getModeById($id_user) {
        $result = $this->requete("SELECT mode FROM Customer WHERE id_Customer = ?", [$id_user])->fetch();
        return is_object($result) ? $result->mode : ($result['mode'] ?? null);
    }

    // met à jour le mode de sécurité
    public function setModeById($id_user, $mode) {
        return $this->requete("UPDATE Customer SET mode = ? WHERE id_Customer = ?", [$mode, $id_user]);
    }

    // ajoute un nouvel utilisateur (inscription)
    public function addUser($email, $username, $password, $lastname) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $db = Db::getInstance();
        
        try {
            $db->beginTransaction();
            
            // 1. insertion des informations personnelles dans savecustomer
            // on initialise avec des chaînes vides pour respecter la structure not null si besoin
            $sql1 = "INSERT INTO SaveCustomer (first_name, last_name, email) 
                     VALUES (?, ?, ?)";
            $stmt1 = $db->prepare($sql1);
            $stmt1->execute([$username, $lastname, $email]);
            
            // récupération de l'id généré (le plus élevé pour cet utilisateur à cet instant)
            $id_save = $db->lastInsertId();
            
            // 2. création du compte customer lié à ce profil savecustomer
            // on définit l'état par défaut à 'invalide' (en attente de mail)
            $sql2 = "INSERT INTO Customer (password, id_SaveCustomer, etat, mode) VALUES (?, ?, 'invalide', NULL)";
            $stmt2 = $db->prepare($sql2);
            $stmt2->execute([$hashed, $id_save]);
            
            $db->commit();
            return true;
        } catch (PDOException $e) {
            $db->rollBack();
            // gestion des doublons (code sql state 23000)
            if ($e->getCode() == '23000') {
                return "duplicate";
            }
            return false;
        }
    }

    // active le compte utilisateur (après validation email)
    public function activateUser($id_user) {
        return $this->requete("UPDATE Customer SET etat = 'valide' WHERE id_Customer = ?", [$id_user]);
    }

    // met à jour le mot de passe
    public function setPassword($id_user, $password) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        return $this->requete("UPDATE Customer SET password = ? WHERE id_Customer = ?", [$hashed, $id_user]);
    }
}