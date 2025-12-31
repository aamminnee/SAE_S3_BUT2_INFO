<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\TranslationModel;
use App\Models\ImagesModel;

class CropImagesController extends Controller
{
    private $translations;

    // // constructeur pour charger les traductions
    public function __construct() {
        $lang = $_SESSION['lang'] ?? 'fr';
        $translation_model = new TranslationModel();
        $this->translations = $translation_model->getTranslations($lang);
    }

    // // méthode principale (route /cropImages)
    public function index() {
        // 1. vérification de la connexion
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/user/login");
            exit;
        }

        // 2. récupération de la dernière image de l'utilisateur
        $imagesModel = new ImagesModel();
        
        $lastImage = null;
        if (method_exists($imagesModel, 'getLastImageByUserId')) {
            $result = $imagesModel->getLastImageByUserId($_SESSION['user_id']);
            
            // // CORRECTION ICI : Conversion explicite en tableau
            if ($result) {
                $lastImage = (array) $result;
            }
        }

        // 3. envoi des données à la vue
        $this->render('crop_images_views', [
            't' => $this->translations,
            'image' => $lastImage,
            // // on passe le css spécifique
            'css' => 'crop_images_views.css' 
        ]);
    }

    // // méthode appelée via ajax pour le traitement java
    public function process() {
        // // suppression des erreurs visuelles pour ne pas casser le json
        error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
        ini_set('display_errors', 0);
        header("Content-Type: application/json");

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(["status" => "error", "message" => "access denied"]);
            exit;
        }

        $user_id = $_SESSION['user_id'];

        // // vérification des paramètres envoyés par js
        if (!isset($_FILES['cropped_image']) || !isset($_POST['size'])) {
            echo json_encode(["status" => "error", "message" => "missing parameters"]);
            exit;
        }

        $uploadedFile = $_FILES['cropped_image'];
        $boardSize = intval($_POST['size']); // // ex: 32, 48, 64
        $_SESSION['boardSize'] = $boardSize;

        // // chemins des fichiers temporaires
        $tempDir = sys_get_temp_dir();
        $inputPath = $tempDir . '/lego_in_' . uniqid() . '.png';
        $outputPath = $tempDir . '/lego_out_' . uniqid() . '.png';

        // // chemin vers le jar (relatif à la racine du projet ou absolu)
        // // __dir__ est dans app/controllers, donc on remonte deux fois pour aller à la racine puis bin
        $jarPath = realpath(__DIR__ . '/../../bin/legotools-1.0-SNAPSHOT.jar');

        try {
            // 1. déplacer l'image uploadée vers un fichier temporaire
            if (!move_uploaded_file($uploadedFile['tmp_name'], $inputPath)) {
                throw new \Exception("Impossible de sauvegarder l'image temporaire.");
            }

            // 2. construction de la commande java
            // // format : java -jar legotools.jar resize <input> <output> <wxh> [strategy]
            $dimension = $boardSize . "x" . $boardSize;
            $strategy = "stepwise"; // // comme demandé
            
            if (!$jarPath || !file_exists($jarPath)) {
                throw new \Exception("Fichier JAR introuvable : " . $jarPath);
            }

            // // on redirige stderr vers stdout pour capturer les erreurs java éventuelles
            $command = "java -jar " . escapeshellarg($jarPath) . " resize " . escapeshellarg($inputPath) . " " . escapeshellarg($outputPath) . " " . escapeshellarg($dimension) . " " . escapeshellarg($strategy) . " 2>&1";

            // 3. exécution
            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            // // vérification du succès (java peut renvoyer 0 mais ne pas créer le fichier si exception interne)
            if ($returnCode !== 0 || !file_exists($outputPath)) {
                // // on log l'erreur pour le debug
                error_log("Erreur Java: " . implode("\n", $output));
                throw new \Exception("Echec du traitement Java. Code: $returnCode");
            }

            // 4. lecture du résultat
            $processedData = file_get_contents($outputPath);
            if ($processedData === false) {
                throw new \Exception("Impossible de lire l'image traitée.");
            }

            // 5. mise à jour en bdd (modification au lieu de création)
            $model = new ImagesModel();
            
            // // récupération de l'image cible. idéalement passé en POST['image_id'], sinon on prend la dernière
            $idToUpdate = null;
            if (isset($_POST['image_id'])) {
                $idToUpdate = $_POST['image_id'];
            } else {
                $lastResult = $model->getLastImageByUserId($user_id);
                // // attention ici aussi au format objet/tableau si on réutilise
                if ($lastResult) {
                    $lastResult = (array)$lastResult; 
                    $idToUpdate = $lastResult['id_Image'];
                }
            }

            if (!$idToUpdate) {
                throw new \Exception("Aucune image trouvée à modifier.");
            }

            // // appel de la méthode update pour écraser le blob sans changer le nom de fichier
            $success = $model->updateCustomerImageBlob($idToUpdate, $user_id, $processedData);

            // 6. nettoyage
            @unlink($inputPath);
            @unlink($outputPath);

            if ($success) {
                // // on renvoie l'id de l'image mise à jour
                echo json_encode(["status" => "success", "file" => $idToUpdate]);
            } else {
                echo json_encode(["status" => "error", "message" => "Erreur lors de la mise à jour en BDD."]);
            }

        } catch (\Exception $e) {
            // // nettoyage en cas d'erreur
            if (file_exists($inputPath)) @unlink($inputPath);
            if (file_exists($outputPath)) @unlink($outputPath);
            
            echo json_encode(["status" => "error", "message" => "Exception: " . $e->getMessage()]);
        }
        exit;
    }
}