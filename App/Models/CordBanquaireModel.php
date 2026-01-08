<?php
namespace App\Models;

use App\Core\Model;

class CordBanquaireModel extends Model
{
    protected $table = 'cord_banquaire';

    public function insertCordBanquaire($card_number, $expiry, $cvc) {
        $card_hash = password_hash($card_number, PASSWORD_DEFAULT);
        $cvc_hash = password_hash($cvc, PASSWORD_DEFAULT);
        $sql = "INSERT INTO {$this->table} (card_number, expiry, cvc) VALUES (?, ?, ?)";
        $this->requete($sql, [$card_hash, $expiry, $cvc_hash]);
        return $this->db->lastInsertId();
    }

    public function deleteCordBanquaire($id_cord) {
        return $this->requete("DELETE FROM {$this->table} WHERE id_cord = ?", [$id_cord]);
    }
}