<?php
namespace App\Models;

use App\Core\Model;

class TranslationModel extends Model {
    // récupération des traductions pour une langue donnée
    public function getTranslations($lang) {
        $sql = "SELECT key_name, texte FROM Translations WHERE lang = ?";
        
        // exécution de la requête via le parent (pdo)
        // fetchAll() retourne un tableau d'objets par défaut dans notre configuration
        $results = $this->requete($sql, [$lang])->fetchAll();
        
        $translations = [];
        
        // on transforme le résultat pour garder le format [clé => valeur] attendu par ton contrôleur
        foreach ($results as $row) {
            // avec pdo en mode objet, on utilise -> au lieu de ['']
            $translations[$row->key_name] = $row->texte;
        }
        
        return $translations;
    }
}