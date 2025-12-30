<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\TranslationModel;
use App\Models\ImagesModel;

class ReviewImagesController extends Controller {
    private $translations;

    // constructeur pour charger les traductions
    public function __construct() {
        $lang = $_SESSION['lang'] ?? 'fr';
        $translation_model = new TranslationModel();
        $this->translations = $translation_model->getTranslations($lang);
    }

    // méthode principale (route /reviewImages)
    public function index() {
        // 1. vérification de la connexion
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/user/login");
            exit;
        }

        // 2. vérification du paramètre img
        if (!isset($_GET['img']) || empty($_GET['img'])) {
            // redirection si pas d'image spécifiée
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/cropImages");
            exit;
        }

        $imageId = $_GET['img'];
        $userId = $_SESSION['user_id'];

        // 3. récupération de l'image
        $imagesModel = new ImagesModel();
        
        // on utilise une méthode sécurisée pour s'assurer que l'image appartient au user
        $image = $imagesModel->getImageById($imageId, $userId);

        if (!$image) {
            // gestion d'erreur simple si l'image n'existe pas ou n'est pas à lui
            echo "Image introuvable ou accès non autorisé.";
            exit;
        }

        // 4. envoi des données à la vue
        $this->render('review_images_views', [
            't' => $this->translations,
            'image' => $image,
            // on suppose que vous avez peut-être un css générique ou spécifique
            'css' => 'style.css' 
        ]);
    }
}