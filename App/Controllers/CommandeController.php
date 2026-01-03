<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\CommandeModel;
use App\Models\ImagesModel;
use App\Models\MosaicModel; 
use App\Models\TranslationModel;

class CommandeController extends Controller {
    
    private $translations;

    public function __construct() {
        $lang = $_SESSION['lang'] ?? 'fr';
        $translation_model = new TranslationModel();
        $this->translations = $translation_model->getTranslations($lang);
    }

    public function index()
    {
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/user/login");
            exit;
        }

        $commandeModel = new CommandeModel();
        $mosaicModel = new MosaicModel();

        // récupération des commandes brutes
        $commandes = $commandeModel->getCommandeByUserId($_SESSION['user_id']);

        // traitement pour ajouter l'url de l'image à chaque commande
        foreach ($commandes as $commande) {
            if (!empty($commande->id_Mosaic)) {
                // on récupère le visuel via le modèle mosaïque
                $commande->visuel = $mosaicModel->getMosaicVisual($commande->id_Mosaic);
            } else {
                // image par défaut si pas de mosaïque
                $commande->visuel = ($_ENV['BASE_URL'] ?? '') . '/Public/images/logo.png';
            }
        }

        $this->render('commande_views', [
            'commandes' => $commandes,
            'commandeModel' => $commandeModel, 
            't' => $this->translations,
            'css' => 'commande_views.css'
        ]);
    }

    public function detail($id) {
        // // vérification de sécurité
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/user/login");
            exit;
        }

        $id = (int)$id;
        $commandeModel = new CommandeModel();
        $mosaicModel = new MosaicModel(); 

        // // récupération de la commande
        $commande = $commandeModel->getCommandeById($id);

        if (!$commande) {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/commande");
            exit;
        }

        // // récupération du visuel du pavage
        $visuel = null;
        $briques = [];

        if (!empty($commande->id_Mosaic)) {
            $visuel = $mosaicModel->getMosaicVisual($commande->id_Mosaic);
            $briques = $mosaicModel->getBricksList($commande->id_Mosaic);
        }

        // // on passe tout à la vue (plus besoin de require le modèle dans la vue)
        $this->render('commande_detail_views', [
            'commande' => $commande,
            'visuel' => $visuel,
            'briques' => $briques,
            't' => $this->translations,
            'css' => 'commande_detail_views.css'
        ]);
    }
}