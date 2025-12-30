<?php
namespace App\Models;

use App\Core\Model;

class TokensModel extends Model
{
    protected $table = 'Tokens';

    // crée un token pour un utilisateur
    public function generateToken($user_id, $type)
    {
        // génération d'un code à 6 chiffres
        $token = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 minutes'));
        
        $sql = "INSERT INTO {$this->table} (id_Customer, token, types, expires_at) VALUES (?, ?, ?, ?)";
        $this->requete($sql, [$user_id, $token, $type, $expires_at]);
        
        return $token;
    }

    // vérifie si un token est valide
    public function verifyToken($token)
    {
        $now = date('Y-m-d H:i:s');
        $sql = "SELECT * FROM {$this->table} WHERE token = ? AND expires_at > ?";
        
        // retourne l'objet token ou false
        return $this->requete($sql, [$token, $now])->fetch();
    }

    // méthode pour supprimer un token spécifique après usage
    public function consumeToken($token)
    {
        $sql = "DELETE FROM {$this->table} WHERE token = ?";
        $this->requete($sql, [$token]);
    }

    // supprime les tokens expirés (nettoyage global)
    public function deleteToken()
    {
        $now = date('Y-m-d H:i:s');
        $this->requete("DELETE FROM {$this->table} WHERE expires_at < ?", [$now]);
    }
}