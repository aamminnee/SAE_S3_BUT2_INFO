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
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/images");
            exit;
        }

        $imageId = $_GET['img'];
        $userId = $_SESSION['user_id'];

        $imagesModel = new ImagesModel();
        $image = $imagesModel->getImageById($imageId, $userId);
        
        if (!$image) {
            die("Image introuvable.");
        }
        $image = (array) $image;

        $previews = [];
        $counts = [];
        $error = null; // Variable pour stocker l'erreur
        
        $sessionKey = 'mosaics_' . $imageId;
        
        if (!isset($_SESSION[$sessionKey]) || empty($_SESSION[$sessionKey])) {
            $mosaicModel = new MosaicModel();
            try {
                $extension = ($image['file_type'] === 'image/png') ? 'png' : 'jpg';
                $results = $mosaicModel->generateTemporaryMosaics($image['id_Image'], $image['file'], $extension);
                
                // VÉRIFICATION : Si aucun résultat n'est retourné, c'est une erreur
                if (empty($results)) {
                    $error = "La génération a échoué. Vérifiez les logs serveur et les permissions.";
                } else {
                    $_SESSION[$sessionKey] = $results;
                }
            } catch (\Exception $e) {
                $error = "Erreur : " . $e->getMessage();
                error_log($e->getMessage());
            }
        }

        // Récupération des prévisualisations si elles existent
        if (isset($_SESSION[$sessionKey])) {
            foreach ($_SESSION[$sessionKey] as $type => $data) {
                if (isset($data['img'])) {
                    $previews[$type] = $data['img'];
                }
            }
        }

        if (isset($_SESSION[$sessionKey])) {
            foreach ($_SESSION[$sessionKey] as $type => $data) {
                if (isset($data['img'])) {
                    $previews[$type] = $data['img'];
                }
                // Récupération du nombre de briques
                if (isset($data['count'])) {
                    $counts[$type] = $data['count'];
                }
            }
        }

        $this->render('review_images_views', [
            't' => $this->translations,
            'image' => $image,
            'previews' => $previews,
            'counts' => $counts,
            'css' => 'review_images_views.css',
            'error_msg' => $error // On passe l'erreur à la vue
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