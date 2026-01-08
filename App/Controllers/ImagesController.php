<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\ImagesModel;
use App\Models\TranslationModel;

/**
 * ImagesController
 * * Handles image uploads and retrieval.
 * * Serves image binaries from the database to the browser.
 */
class ImagesController extends Controller {
    private $translations;

    public function __construct() {
        $lang = $_SESSION['lang'] ?? 'fr';
        $translation_model = new TranslationModel();
        $this->translations = $translation_model->getTranslations($lang);
    }

    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/user/login");
            exit;
        }

        $this->render('images_views', [
            't' => $this->translations,
            'css' => 'images_views.css'

        ]);
    }

    public function upload() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Non connecté']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image_input'])) {
            $file = $_FILES['image_input'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['status' => 'error', 'message' => 'Erreur upload: ' . $file['error']]);
                exit;
            }

            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
            $fileType = mime_content_type($file['tmp_name']);
            
            if (!in_array($fileType, $allowed)) {
                echo json_encode(['status' => 'error', 'message' => 'Format invalide']);
                exit;
            }

            $imgData = file_get_contents($file['tmp_name']);
            $fileName = $file['name']; // correspond à votre colonne 'filename'

            try {
                $model = new ImagesModel();
                $imageId = $model->saveCustomerImage($_SESSION['user_id'], $imgData, $fileName, $fileType);

                echo json_encode([
                    'status' => 'success', 
                    'id_image' => $imageId,
                    'redirect' => ($_ENV['BASE_URL'] ?? '') . '/cropImages' 
                ]);
            } catch (\Exception $e) {
                echo json_encode(['status' => 'error', 'message' => 'Erreur BDD : ' . $e->getMessage()]);
            }

        } else {
            echo json_encode(['status' => 'error', 'message' => 'Aucun fichier']);
        }
        exit;
    }

    public function view($id) {
        $id = (int)$id;

        if ($id <= 0) {
            http_response_code(404);
            exit;
        }

        $model = new ImagesModel();
        $image = $model->getImageById($id);

        if (!$image || empty($image->file)) {
            http_response_code(404);
            exit;
        }
        if (ob_get_level()) {
            ob_end_clean();
        }

        header("Content-Type: " . $image->file_type);
        
        echo $image->file;
        exit;
    }
}