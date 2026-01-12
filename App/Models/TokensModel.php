<?php
namespace App\Models;

use App\Core\Model;

/**
 * TokensModel
 * * Manages secure tokens used for critical actions.
 * * Types: 'validation' (account activation), 'reinitialisation' (password reset), '2FA'.
 */
class TokensModel extends Model {
    protected $table = 'Tokens';

    public function generateToken($user_id, $type) {
        $token = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 minutes'));
        
        $sql = "INSERT INTO {$this->table} (id_Customer, token, types, expires_at) VALUES (?, ?, ?, ?)";
        $this->requete($sql, [$user_id, $token, $type, $expires_at]);
        
        return $token;
    }

    public function verifyToken($token) {
        $now = date('Y-m-d H:i:s');
        $sql = "SELECT * FROM {$this->table} WHERE token = ? AND expires_at > ?";
        
        return $this->requete($sql, [$token, $now])->fetch();
    }

    public function consumeToken($token) {
        $sql = "DELETE FROM {$this->table} WHERE token = ?";
        $this->requete($sql, [$token]);
    }

    public function deleteToken() {
        $now = date('Y-m-d H:i:s');
        $this->requete("DELETE FROM {$this->table} WHERE expires_at < ?", [$now]);
    }
}