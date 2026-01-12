<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Db;
use PDOException;

/**
 * UsersModel
 * * Manages user accounts in the database (Table: Customer).
 * * Handles registration, login verification, password updates, and 2FA settings.
 */
class UsersModel extends Model {
    protected $table = 'Customer';

    public function getUserById($id_user) {
        $sql = "SELECT 
                    c.id_Customer as id_user, 
                    c.password as mdp, 
                    c.etat, 
                    c.mode,
                    c.role,
                    s.first_name as username, 
                    s.last_name, 
                    s.email 
                FROM Customer c 
                JOIN SaveCustomer s ON c.id_SaveCustomer = s.id_SaveCustomer 
                WHERE c.id_Customer = ?";
        
        return $this->requete($sql, [$id_user])->fetch();
    }

    public function getUserByUsername($username){
        $sql = "SELECT 
                    c.id_Customer as id_user, 
                    c.password as mdp, 
                    c.etat,
                    c.mode, 
                    c.role,
                    s.first_name as username, 
                    s.email 
                FROM Customer c 
                JOIN SaveCustomer s ON c.id_SaveCustomer = s.id_SaveCustomer 
                WHERE s.first_name = ?";
        
        return $this->requete($sql, [$username])->fetch();
    }

    public function getEmailById($id_user) {
        $sql = "SELECT s.email 
                FROM Customer c 
                JOIN SaveCustomer s ON c.id_SaveCustomer = s.id_SaveCustomer 
                WHERE c.id_Customer = ?";
        
        return $this->requete($sql, [$id_user])->fetch();
    }

    public function getStatusById($id_user) {
        return $this->requete("SELECT etat FROM Customer WHERE id_Customer = ?", [$id_user])->fetch();
    }
    
    public function getModeById($id_user) {
        $result = $this->requete("SELECT mode FROM Customer WHERE id_Customer = ?", [$id_user])->fetch();
        return is_object($result) ? $result->mode : ($result['mode'] ?? null);
    }

    public function setModeById($id_user, $mode) {
        return $this->requete("UPDATE Customer SET mode = ? WHERE id_Customer = ?", [$mode, $id_user]);
    }

    public function addUser($email, $username, $password, $lastname) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $db = Db::getInstance();
        
        try {
            $db->beginTransaction();
            
            $sql1 = "INSERT INTO SaveCustomer (first_name, last_name, email) 
                     VALUES (?, ?, ?)";
            $stmt1 = $db->prepare($sql1);
            $stmt1->execute([$username, $lastname, $email]);
            
            $id_save = $db->lastInsertId();
            
            $sql2 = "INSERT INTO Customer (password, id_SaveCustomer, etat, mode, role) VALUES (?, ?, 'invalide', NULL, 'user')";
            $stmt2 = $db->prepare($sql2);
            $stmt2->execute([$hashed, $id_save]);
            
            $db->commit();
            return true;
        } catch (PDOException $e) {
            $db->rollBack();
            if ($e->getCode() == '23000') {
                return "duplicate";
            }
            return false;
        }
    }

    public function activateUser($id_user) {
        return $this->requete("UPDATE Customer SET etat = 'valide' WHERE id_Customer = ?", [$id_user]);
    }

    public function validateNewPassword($userId, $plainPassword) {
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{12,}$/', $plainPassword)) {
            return "Le mot de passe doit contenir 12 caractères min, majuscule, minuscule, chiffre, caractère spécial.";
        }

        $sql = "SELECT password FROM Customer WHERE id_Customer = ?";
        $stmt = $this->requete($sql, [$userId]);
        $currentHash = $stmt->fetchColumn();

        if ($currentHash && password_verify($plainPassword, $currentHash)) {
            return "Le nouveau mot de passe doit être différent de l'ancien.";
        }

        return true;
    }

    public function updatePassword($userId, $plainPassword) {
        $newHash = password_hash($plainPassword, PASSWORD_DEFAULT);
        $sql = "UPDATE Customer SET password = ? WHERE id_Customer = ?";
        $this->requete($sql, [$newHash, $userId]);
    }

    public function getUserByEmail($email) {
        $sql = "SELECT c.id_Customer as id_user, s.email 
                FROM Customer c 
                JOIN SaveCustomer s ON c.id_SaveCustomer = s.id_SaveCustomer 
                WHERE s.email = ?";
        
        return $this->requete($sql, [$email])->fetch();
    }

    public function countUsers() {
        $sql = "SELECT COUNT(*) as total FROM Customer WHERE role = 'user'";
        
        $res = \App\Core\Db::getInstance()->query($sql)->fetch();
        return $res->total ?? 0;
    }
}