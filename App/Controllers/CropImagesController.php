<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\TranslationModel;
use App\Models\ImagesModel;

/**
 * CropImagesController
 * * Handles the image resizing and cropping process.
 * * Uses an external Java JAR (legotools) to process the image according to the board size (e.g., 48x48).
 */
class CropImagesController extends Controller {
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

        $imagesModel = new ImagesModel();
        
        $lastImage = null;
        if (method_exists($imagesModel, 'getLastImageByUserId')) {
            $result = $imagesModel->getLastImageByUserId($_SESSION['user_id']);
            
            if ($result) {
                $lastImage = (array) $result;
            }
        }

        $this->render('crop_images_views', [
            't' => $this->translations,
            'image' => $lastImage,
            'css' => 'crop_images_views.css'
        ]);
    }

    public function process() {

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(["status" => "error", "message" => "access denied"]);
            exit;
        }

        $user_id = $_SESSION['user_id'];

        if (!isset($_FILES['cropped_image']) || !isset($_POST['size'])) {
            echo json_encode(["status" => "error", "message" => "missing parameters"]);
            exit;
        }

        $uploadedFile = $_FILES['cropped_image'];
        $boardSize = intval($_POST['size']); // // ex: 32, 48, 64
        $_SESSION['boardSize'] = $boardSize;

        $tempDir = sys_get_temp_dir();
        $inputPath = $tempDir . '/lego_in_' . uniqid() . '.png';
        $outputPath = $tempDir . '/lego_out_' . uniqid() . '.png';

        $jarPath = realpath(__DIR__ . '/../../bin/legotools-1.0-SNAPSHOT.jar');

        try {
            if (!move_uploaded_file($uploadedFile['tmp_name'], $inputPath)) {
                throw new \Exception("Impossible de sauvegarder l'image temporaire.");
            }

            $dimension = $boardSize . "x" . $boardSize;
            $strategy = "stepwise"; // // comme demandé
            
            if (!$jarPath || !file_exists($jarPath)) {
                throw new \Exception("Fichier JAR introuvable : " . $jarPath);
            }

            $command = "java -jar " . escapeshellarg($jarPath) . " resize " . escapeshellarg($inputPath) . " " . escapeshellarg($outputPath) . " " . escapeshellarg($dimension) . " " . escapeshellarg($strategy) . " 2>&1";

            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            if ($returnCode !== 0 || !file_exists($outputPath)) {
                error_log("Erreur Java: " . implode("\n", $output));
                throw new \Exception("Echec du traitement Java. Code: $returnCode");
            }

            $processedData = file_get_contents($outputPath);
            if ($processedData === false) {
                throw new \Exception("Impossible de lire l'image traitée.");
            }

            $model = new ImagesModel();
            
            $idToUpdate = null;
            if (isset($_POST['image_id'])) {
                $idToUpdate = $_POST['image_id'];
            } else {
                $lastResult = $model->getLastImageByUserId($user_id);
                if ($lastResult) {
                    $lastResult = (array)$lastResult; 
                    $idToUpdate = $lastResult['id_Image'];
                }
            }

            if (!$idToUpdate) {
                throw new \Exception("Aucune image trouvée à modifier.");
            }

            $success = $model->updateCustomerImageBlob($idToUpdate, $user_id, $processedData);

            @unlink($inputPath);
            @unlink($outputPath);

            if ($success) {
                echo json_encode(["status" => "success", "file" => $idToUpdate]);
            } else {
                echo json_encode(["status" => "error", "message" => "Erreur lors de la mise à jour en BDD."]);
            }

        } catch (\Exception $e) {
            if (file_exists($inputPath)) @unlink($inputPath);
            if (file_exists($outputPath)) @unlink($outputPath);
            
            echo json_encode(["status" => "error", "message" => "Exception: " . $e->getMessage()]);
        }
        exit;
    }
}