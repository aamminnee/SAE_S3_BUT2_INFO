<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\TranslationModel;
use App\Models\ImagesModel;
use App\Models\MosaicModel;

/**
 * ReviewImagesController
 * * Manages the preview generation of LEGO mosaics.
 * * Bridges the gap between the uploaded image and the Java processing engine.
 */
class ReviewImagesController extends Controller {
    private $translations;

    public function __construct() {
        $lang = $_SESSION['lang'] ?? 'fr';
        $translation_model = new TranslationModel();
        $this->translations = $translation_model->getTranslations($lang);
    }

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
        $prices = [];
        $error = null;
        
        $sessionKey = 'mosaics_' . $imageId;
        $mosaicModel = new MosaicModel();

        if (!isset($_SESSION[$sessionKey]) || empty($_SESSION[$sessionKey])) {
            try {
                $extension = ($image['file_type'] === 'image/png') ? 'png' : 'jpg';
                $results = $mosaicModel->generateTemporaryMosaics($image['id_Image'], $image['file'], $extension);
                
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

        if (isset($_SESSION[$sessionKey])) {
            foreach ($_SESSION[$sessionKey] as $type => $data) {
                if (isset($data['img'])) {
                    $previews[$type] = $data['img'];
                }
                
                if (isset($data['txt'])) {
                    $prices[$type] = $mosaicModel->calculatePriceFromContent($data['txt']);
                    $counts[$type] = $mosaicModel->countPiecesFromContent($data['txt']);
                } else {
                    $prices[$type] = 0;
                    $counts[$type] = isset($data['count']) ? $data['count'] : 0;
                }
            }
        }

        $_SESSION['mosaic_prices_' . $imageId] = $prices;
        $_SESSION['mosaic_counts_' . $imageId] = $counts;

        $this->render('review_images_views', [
            't' => $this->translations,
            'image' => $image,
            'previews' => $previews,
            'counts' => $counts,
            'prices' => $prices,
            'css' => 'review_images_views.css',
            'error_msg' => $error
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
                $mosaicId = $mosaicModel->saveSelectedMosaic($imageId, $contentToSave, $choice);

                if ($mosaicId) {
                    $_SESSION['pending_payment_mosaic_id'] = $mosaicId;
                    
                    unset($_SESSION[$sessionKey]);

                    header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/payment");
                    exit;
                }
            }
        }
        header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/images");
    }
}