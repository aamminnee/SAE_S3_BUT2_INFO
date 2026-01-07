<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\ImagesModel;
use App\Models\TranslationModel;

class ImagesController extends Controller {
    private $translations;

    // constructeur pour charger les traductions
    public function __construct() {
        // on vérifie si une langue est définie en session, sinon fr par défaut
        $lang = $_SESSION['lang'] ?? 'fr';
        $translation_model = new TranslationModel();
        $this->translations = $translation_model->getTranslations($lang);
    }

    // méthode d'affichage de la page principale (accessible à tous)
    public function index() {
        // on a supprimé la redirection vers login ici pour laisser l'accès public
        
        $this->render('images_views', [
            't' => $this->translations,
            // on passe l'état de connexion à la vue si besoin, bien que $_SESSION soit accessible directement
            'is_logged_in' => isset($_SESSION['user_id'])
        ]);
    }

    // // méthode pour traiter l'upload (accès restreint via vérification session et statut)
    public function upload() {
        header('Content-Type: application/json');

        // // vérification de la connexion
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Non connecté']);
            exit;
        }

        // // vérification du statut du compte (doit être valide)
        $statut_compte = $_SESSION['status'] ?? 'invalide';
        if ($statut_compte !== 'valide') {
            echo json_encode([
                'status' => 'error', 
                'message' => 'Compte non activé. Veuillez activer votre compte.',
                'redirect_account' => true // indicateur pour le js
            ]);
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

            // // lecture binaire du fichier
            $imgData = file_get_contents($file['tmp_name']);
            $fileName = $file['name']; 

            try {
                $model = new ImagesModel();
                // // sauvegarde de l'image en base de données
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

    // méthode pour afficher l'image brute depuis la bdd
    public function view($id) {
        // nettoyage de l'id pour la sécurité
        $id = (int)$id;

        if ($id <= 0) {
            http_response_code(404);
            exit;
        }

        $model = new ImagesModel();
        // récupération de l'image
        $image = $model->getImageById($id);

        // si l'image n'existe pas ou est vide
        if (!$image || empty($image->file)) {
            http_response_code(404);
            exit;
        }
        
        // on vide le tampon de sortie pour éviter la corruption de l'image
        if (ob_get_level()) {
            ob_end_clean();
        }

        // définition du header content-type correct
        header("Content-Type: " . $image->file_type);
        
        // affichage des données binaires
        echo $image->file;
        exit;
    }
}