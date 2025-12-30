<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\CommandeModel;
use App\Models\ImagesModel;
use App\Models\TranslationModel;

class CommandeController extends Controller {
    
    private $translations;

    // constructeur pour charger les traductions (comme dans usercontroller)
    public function __construct() {
        $lang = $_SESSION['lang'] ?? 'fr';
        $translation_model = new TranslationModel();
        $this->translations = $translation_model->getTranslations($lang);
    }

    // affiche la liste des commandes de l'utilisateur
    public function index()
    {
        // vérification de connexion
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/user/login");
            exit;
        }

        $commandeModel = new CommandeModel();
        // récupération des commandes via le modèle
        $commandes = $commandeModel->getCommandeByUserId($_SESSION['user_id']);

        // affichage de la vue
        // on passe 'commandeModel' pour pouvoir utiliser ses méthodes dans la vue
        // on passe 't' pour les traductions
        $this->render('commande_views', [
            'commandes' => $commandes,
            'commandeModel' => $commandeModel, 
            't' => $this->translations,
            'css' => 'commande_views.css'
        ]);
    }

    // affiche le détail d'une commande spécifique
    public function detail($id)
    {
        // vérification de connexion
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/user/login");
            exit;
        }

        $id = (int)$id;
        $commandeModel = new CommandeModel();
        $imagesModel = new ImagesModel();

        // récupération de la commande
        $commande = $commandeModel->getCommandeById($id);

        // si la commande n'existe pas ou n'appartient pas à l'utilisateur
        // note : idéalement il faudrait vérifier que l'id_user correspond bien
        if (!$commande) {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/commande");
            exit;
        }

        // on récupère l'image associée (support objet/array selon pdo)
        $id_image = is_object($commande) ? $commande->id_images : ($commande['id_images'] ?? null);
        
        $mosaic = null;
        if ($id_image) {
             $mosaic = $imagesModel->getImageById($id_image);
        }

        // affichage de la vue détail
        $this->render('commande_detail_views', [
            'commande' => $commande,
            'mosaic' => $mosaic,
            't' => $this->translations,
            'css' => 'commande_detail_views.css'
        ]);
    }
}