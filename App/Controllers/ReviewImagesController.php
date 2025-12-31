<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\TranslationModel;
use App\Models\ImagesModel;
use App\Models\MosaicModel;

class ReviewImagesController extends Controller {
    private $translations;

    public function __construct() {
        // ... (votre code constructeur existant) ...
        $lang = $_SESSION['lang'] ?? 'fr';
        $translation_model = new TranslationModel();
        $this->translations = $translation_model->getTranslations($lang);
    }

    // method to display previews
    public function index() {
        if (!isset($_SESSION['user_id']) || !isset($_GET['img'])) {
            header("Location: /"); // redirection si accès direct
            exit;
        }

        $imageId = $_GET['img'];
        $userId = $_SESSION['user_id'];

        $imagesModel = new ImagesModel();
        $image = $imagesModel->getImageById($imageId, $userId);
        $image = (array) $image;

        $previews = [];
        
        // on génère seulement si on ne l'a pas déjà fait pour cette image dans cette session
        // (pour éviter de recharger java si on rafraichit la page)
        $sessionKey = 'mosaics_' . $imageId;
        
        if (!isset($_SESSION[$sessionKey])) {
            $mosaicModel = new MosaicModel();
            try {
                $extension = ($image['file_type'] === 'image/png') ? 'png' : 'jpg';
                // récupération des données (txt + img)
                $results = $mosaicModel->generateTemporaryMosaics($image['id_Image'], $image['file'], $extension);
                
                // stockage temporaire en session pour l'étape suivante
                $_SESSION[$sessionKey] = $results;
            } catch (\Exception $e) {
                error_log($e->getMessage());
            }
        }

        // préparation des images pour la vue
        if (isset($_SESSION[$sessionKey])) {
            foreach ($_SESSION[$sessionKey] as $type => $data) {
                if (isset($data['img'])) {
                    $previews[$type] = $data['img'];
                }
            }
        }

        $this->render('review_images_views', [
            't' => $this->translations,
            'image' => $image,
            'previews' => $previews,
            'css' => 'review_images_views.css'
        ]);
    }

    public function save() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['choice'], $_POST['image_id'])) {
            $choice = $_POST['choice'];
            $imageId = $_POST['image_id'];
            $sessionKey = 'mosaics_' . $imageId;

            if (isset($_SESSION[$sessionKey][$choice]['txt'])) {
                $contentToSave = $_SESSION[$sessionKey][$choice]['txt'];
                
                $mosaicModel = new MosaicModel();
                // // on récupère l'id de la mosaïque créée
                $mosaicId = $mosaicModel->saveSelectedMosaic($imageId, $contentToSave, $choice);

                if ($mosaicId) {
                    // // on sauvegarde l'id en session pour le paiement
                    $_SESSION['pending_payment_mosaic_id'] = $mosaicId;
                    
                    // // nettoyage de la session image
                    unset($_SESSION[$sessionKey]);

                    // // redirection vers la page de paiement
                    header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/payment");
                    exit;
                }
            }
        }
        header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/images");
    }
}