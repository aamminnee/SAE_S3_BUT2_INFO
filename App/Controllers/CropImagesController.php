<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\ImagesModel;

class CropImagesController extends Controller
{
    private $model;

    public function __construct()
    {
        $this->model = new ImagesModel();
    }

    // méthode appelée via ajax pour le crop
    public function process()
    {
        // suppression des erreurs visuelles pour ne pas casser le json
        error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
        ini_set('display_errors', 0);
        header("Content-Type: application/json");

        if (!isset($_SESSION['user_id']) || ($_SESSION['status'] ?? '') !== 'valide') {
            echo json_encode(["status" => "error", "message" => "access denied"]);
            exit;
        }

        $user_id = $_SESSION['user_id'];

        if (!isset($_FILES['cropped_image']) || !isset($_POST['original_name']) || !isset($_POST['size'])) {
            echo json_encode(["status" => "error", "message" => "missing parameters"]);
            exit;
        }

        // ... logique de traitement d'image gd ...
        $originalName = basename($_POST['original_name']);
        $cropped = $_FILES['cropped_image'];
        $boardSize = intval($_POST['size']);
        $_SESSION['boardSize'] = $boardSize;

        $source = @imagecreatefrompng($cropped['tmp_name']);
        if (!$source) {
            echo json_encode(["status" => "error", "message" => "invalid image"]);
            exit;
        }

        // création de l'image redimensionnée
        $width = imagesx($source);
        $height = imagesy($source);
        $resized = imagecreatetruecolor($boardSize, $boardSize);
        
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
        imagefilledrectangle($resized, 0, 0, $boardSize, $boardSize, $transparent);
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $boardSize, $boardSize, $width, $height);

        // capture du binaire
        ob_start();
        imagepng($resized);
        $imageData = ob_get_clean();
        
        $newName = uniqid('img_crop_', true) . '.png';

        // mise à jour via le modèle
        $updateResult = $this->model->updateImageBlob($originalName, $newName, $user_id, $imageData);

        // nettoyage mémoire
        imagedestroy($source);
        imagedestroy($resized);

        if ($updateResult) {
            echo json_encode(["status" => "success", "file" => $newName]);
        } else {
            echo json_encode(["status" => "error", "message" => "db error"]);
        }
        exit;
    }
    
    // affichage de la vue de crop
    public function index() {
         $this->render('crop_images_views', ['css' => 'crop_images_views_style.css']);
    }
}