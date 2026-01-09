<?php
namespace App\Models;

use App\Core\Model;

/**
 * TranslationModel
 * * Fetch translation strings from the database.
 * * Supports dynamic switching between languages (FR/EN).
 */
class TranslationModel extends Model {
    public function getTranslations($lang) {
        $sql = "SELECT key_name, texte FROM Translations WHERE lang = ?";
        $results = $this->requete($sql, [$lang])->fetchAll();
        $translations = [];
        foreach ($results as $row) {
            $translations[$row->key_name] = $row->texte;
        }
        return $translations;
    }
}