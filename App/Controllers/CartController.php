<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\TranslationModel;
use App\Models\ImagesModel;

class CartController extends Controller {
    
    private $translations;

    public function __construct() {
        $lang = $_SESSION['lang'] ?? 'fr';
        $translation_model = new TranslationModel();
        $this->translations = $translation_model->getTranslations($lang);
        
        // Initialiser le panier s'il n'existe pas
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
    }

    // AFFICHER LE PANIER
    public function index() {
        if (!isset($_SESSION['user_id'])) { header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/user/login"); exit; }

        $items = $_SESSION['cart'];
        $subTotal = 0;
        foreach ($items as $item) {
            $subTotal += $item['price']; // Prix (70€ par ex)
        }

        $delivery = \App\Models\MosaicModel::DELIVERY_FEE; // 4.99€
        $total = $subTotal + $delivery; // 70 + 4.99 = 74.99€

        $this->render('cart_views', [
            't' => $this->translations,
            'items' => $items,
            'subTotal' => $subTotal,
            'delivery' => $delivery,
            'total' => $total,
            'css' => 'cart_views.css'
        ]);
    }

    // AJOUTER AU PANIER (Via Session)
    public function add() {
        if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/images");
            exit;
        }

        $imageId = $_POST['image_id'];
        $style = $_POST['choice']; 
        $size = $_SESSION['boardSize'] ?? 64; 

        // Récupérer le prix et le nombre de pièces depuis la session (sauvegardé dans ReviewImages)
        $sessionKeyPrice = 'mosaic_prices_' . $imageId;
        $sessionKeyCount = 'mosaic_counts_' . $imageId;
        
        $price = $_SESSION[$sessionKeyPrice][$style] ?? 0;
        $pieces = $_SESSION[$sessionKeyCount][$style] ?? 0;

        // Récupérer l'image pour l'affichage (facultatif ici mais utile pour le panier)
        $imagesModel = new ImagesModel();
        $image = $imagesModel->getImageById($imageId, $_SESSION['user_id']);
        
        // Création de l'item
        $newItem = [
            'id_unique' => uniqid(), // Identifiant unique temporaire pour la suppression
            'image_id' => $imageId,
            'style' => $style,
            'size' => $size,
            'price' => $price,
            'pieces_count' => $pieces,
            'image_data' => $image ? base64_encode($image->file) : '',
            'image_type' => $image ? $image->file_type : 'image/png'
        ];

        $_SESSION['cart'][] = $newItem;

        $_SESSION['success_message'] = "La mosaïque a été ajoutée au panier !";

        session_write_close();
        // 2. On redirige vers la page de review (on reste sur la même page)
        // On a besoin de l'ID de l'image pour recharger la page
        $redirectUrl = ($_ENV['BASE_URL'] ?? '') . "/reviewImages?img=" . $imageId;
        header("Location: " . $redirectUrl);
        exit;
    }

    // SUPPRIMER DU PANIER
    public function remove() {
        if (isset($_POST['cart_id'])) {
            $idToDelete = $_POST['cart_id'];
            
            // On cherche l'élément dans le tableau et on le vire
            foreach ($_SESSION['cart'] as $key => $item) {
                if ($item['id_unique'] === $idToDelete) {
                    unset($_SESSION['cart'][$key]);
                    break; 
                }
            }
            // Réindexer le tableau pour éviter les trous
            $_SESSION['cart'] = array_values($_SESSION['cart']);
        }
        header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/cart");
        exit;
    }
    
    // VIDER LE PANIER (Utile après paiement)
    public function clear() {
        $_SESSION['cart'] = [];
        header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/cart");
        exit;
    }
}