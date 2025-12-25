<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Db;
use PDOException;

class UsersModel extends Model
{
    // définition de la table principale
    protected $table = 'Customer';

    // récupère toutes les infos d'un utilisateur par son id
    public function getUserById($id_user)
    {
        // on joint les deux tables pour avoir le profil complet et le vrai état
        $sql = "SELECT c.id_Customer as id_user, c.password as mdp, s.first_name as username, s.email, c.etat, NULL as mode 
                FROM Customer c 
                JOIN SaveCustomer s ON c.id_SaveCustomer = s.id_SaveCustomer 
                WHERE c.id_Customer = ?";
        return $this->requete($sql, [$id_user])->fetch();
    }

    // récupère l'id et le mot de passe par le nom d'utilisateur (ici first_name)
    public function getUserByUsername($username)
    {
        // on récupère aussi l'état réel pour vérifier si le compte est actif
        $sql = "SELECT c.id_Customer as id_user, c.password as mdp, s.first_name as username, s.email, c.etat 
                FROM Customer c 
                JOIN SaveCustomer s ON c.id_SaveCustomer = s.id_SaveCustomer 
                WHERE s.first_name = ?";
        return $this->requete($sql, [$username])->fetch();
    }

    // récupère le nom d'utilisateur par son id
    public function getUsernameById($id_user)
    {
        $sql = "SELECT s.first_name as username 
                FROM Customer c 
                JOIN SaveCustomer s ON c.id_SaveCustomer = s.id_SaveCustomer 
                WHERE c.id_Customer = ?";
        return $this->requete($sql, [$id_user])->fetch();
    }

    // récupère le statut par id
    public function getStatusById($id_user)
    {
        // cette fois on lit la vraie valeur en base
        return $this->requete("SELECT etat FROM Customer WHERE id_Customer = ?", [$id_user])->fetch();
    }
    
    // ajoute un utilisateur dans la base de données
    public function addUser($email, $username, $password)
    {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $db = Db::getInstance();
        
        try {
            $db->beginTransaction();
            
            // 1. insertion des infos personnelles
            $sql1 = "INSERT INTO SaveCustomer (first_name, last_name, email, adress, postal_code, city) VALUES (?, 'Inconnu', ?, '', '', '')";
            $stmt1 = $db->prepare($sql1);
            $stmt1->execute([$username, $email]);
            $id_save = $db->lastInsertId();
            
            // 2. insertion des infos de connexion avec etat par défaut 'invalide'
            // note : la colonne etat a une valeur par défaut en sql, pas besoin de l'écrire ici
            $sql2 = "INSERT INTO Customer (password, id_SaveCustomer, etat) VALUES (?, ?, 'invalide')";
            $stmt2 = $db->prepare($sql2);
            $stmt2->execute([$hashed, $id_save]);
            
            $db->commit();
            return true;
        } catch (PDOException $e) {
            $db->rollBack();
            // code erreur standard pour violation d'unicité
            if ($e->getCode() == '23000') {
                return "duplicate";
            }
            return false;
        }
    }

    // active un utilisateur
    public function activateUser($id_user)
    {
        // mise à jour réelle de l'état
        return $this->requete("UPDATE Customer SET etat = 'valide' WHERE id_Customer = ?", [$id_user]);
    }

    // met à jour le mot de passe
    public function setPassword($id_user, $password)
    {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        return $this->requete("UPDATE Customer SET password = ? WHERE id_Customer = ?", [$hashed, $id_user]);
    }

    // récupère l'email par id
    public function getEmailById($id_user)
    {
        $sql = "SELECT s.email 
                FROM Customer c 
                JOIN SaveCustomer s ON c.id_SaveCustomer = s.id_SaveCustomer 
                WHERE c.id_Customer = ?";
        return $this->requete($sql, [$id_user])->fetch();
    }

    // récupère le mode 2fa (toujours simulé car colonne absente pour l'instant)
    public function getModeById($id_user)
    {
        return null; 
    }

    // définit le mode (toujours simulé)
    public function setModeById($id_user, $mode)
    {
        return true;
    }
}