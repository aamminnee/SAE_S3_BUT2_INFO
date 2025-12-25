<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\ImagesModel;

class ImagesController extends Controller
{
    private $images_model;

    public function __construct()
    {
        // on appelle le constructeur du parent pour charger les traductions et la session
        parent::__construct();
        $this->images_model = new ImagesModel();
    }

    // page principale pour uploader des images
    public function index()
    {
        // plus de redirection ici, tout le monde peut accéder au menu et à la page
        $this->render('images_views', ['css' => 'images_views.css']);
    }

    // méthode api pour l'upload d'image (ajax)
    public function upload()
    {
        // récupération de l'url de base
        $baseUrl = $_ENV['BASE_URL'] ?? '';

        // vérification de l'authentification
        // si l'utilisateur n'est pas connecté ou pas valide, on rejette l'action
        if (!isset($_SESSION['user_id']) || ($_SESSION['status'] ?? '') !== 'valide') {
            header("Content-Type: application/json");
            // on renvoie une instruction de redirection que le javascript devra gérer
            echo json_encode([
                "status" => "error", 
                "message" => "auth_required", 
                "redirect" => "$baseUrl/user/login"
            ]);
            exit;
        }

        header("Content-Type: application/json");

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image_input'])) {
            $image = $_FILES['image_input'];
            $allowedTypes = ["image/png", "image/jpeg", "image/webp"];

            if (!is_uploaded_file($image['tmp_name'])) {
                echo json_encode(["status" => "error", "message" => "fichier invalide."]);
                return;
            }

            $mimeType = mime_content_type($image['tmp_name']);
            if (!in_array($mimeType, $allowedTypes)) {
                echo json_encode(["status" => "error", "message" => "type non supporté."]);
                return;
            }

            // nettoyage du nom et création d'un nom unique
            $originalName = preg_replace("/[^a-z0-9_\.-]/", "_", strtolower(basename($image['name'])));
            $uniqueName = uniqid() . "_" . $originalName;
            $imageData = file_get_contents($image['tmp_name']);

            // sauvegarde en base de données
            $result = $this->images_model->saveImageBlob($uniqueName, $_SESSION['user_id'], $imageData);

            if ($result) {
                // on stocke le nom en session pour l'étape suivante
                $_SESSION['last_image'] = $uniqueName;
                echo json_encode(["status" => "success", "file" => $uniqueName]);
            } else {
                echo json_encode(["status" => "error", "message" => "erreur base de données."]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "aucune image sélectionnée."]);
        }
    }

    // méthode pour choisir le type de mosaique
    public function mosaic()
    {
        $baseUrl = $_ENV['BASE_URL'] ?? '';

        // vérification de l'authentification
        // si on essaie de valider le formulaire sans être connecté, redirection vers le login
        if (!isset($_SESSION['user_id']) || ($_SESSION['status'] ?? '') !== 'valide') {
            header("Location: $baseUrl/user/login");
            exit;
        }

        if (!isset($_SESSION['last_image'])) {
            // rediriger si pas d'image en cours
            header("Location: $baseUrl/images");
            exit;
        }

        $type = $_POST['choice'] ?? null;
        if (in_array($type, ['blue', 'red', 'bw'])) {
            $this->images_model->setImageType($_SESSION['last_image'], $type);
            // redirection vers le paiement
            header("Location: $baseUrl/payment");
            exit;
        }
        
        // si erreur, retour aux images
        header("Location: $baseUrl/images");
    }

    // méthode pour servir l'image binaire
    public function serve($image_name)
    {
        // protection de l'accès aux images privées
        if (!isset($_SESSION['user_id']) || ($_SESSION['status'] ?? '') !== 'valide') {
            http_response_code(403);
            exit('accès refusé');
        }

        $user_id = $_SESSION['user_id'];
        
        // on récupère le blob
        $row = $this->images_model->getLastImageBlobByUser($user_id, $image_name);

        if (!$row) {
            http_response_code(404);
            exit('image non trouvée');
        }

        // gestion de la récupération selon le mode fetch (objet ou tableau)
        $blob = is_object($row) ? $row->image : $row['image'];

        header('Content-Type: image/png');
        echo $blob;
    }
}