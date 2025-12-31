<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\FinancialModel;
use App\Models\TranslationModel;
use App\Models\MosaicModel;
use App\Models\CommandeModel;

class PaymentController extends Controller {
    private $translations;

    public function __construct() {
        $lang = $_SESSION['lang'] ?? 'fr';
        $translation_model = new TranslationModel();
        $this->translations = $translation_model->getTranslations($lang);
    }

    public function index() {
        // // Vérification connexion
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/user/login");
            exit;
        }

        // // Vérification qu'il y a une mosaïque à payer
        if (!isset($_SESSION['pending_payment_mosaic_id'])) {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/images");
            exit;
        }

        $mosaicId = $_SESSION['pending_payment_mosaic_id'];
        
        // // Récupération de l'aperçu visuel
        $mosaicModel = new MosaicModel();
        $visualImage = $mosaicModel->getMosaicVisual($mosaicId);

        $this->render('payment_views', [
            't' => $this->translations,
            'price' => 12.99,
            'css' => 'payment_views.css',
            'mosaicImage' => $visualImage
        ]);
    }

    public function process() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $_SESSION['user_id'];
            $mosaicId = $_SESSION['pending_payment_mosaic_id'] ?? null;
            
            if (!$mosaicId) {
                header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/images");
                exit;
            }

            // // Email fallback
            if (!isset($_SESSION['user_email'])) {
                $_SESSION['user_email'] = 'client@legofactory.com'; 
            }

            // // Récupération des données du formulaire
            $cardInfo = [
                'holder' => $_POST['card_holder'], 
                'number' => $_POST['card_number'], 
                'expiry' => $_POST['card_expiry'] . '-01', // Ajout du jour pour format DATE SQL valide
                'cvv'    => $_POST['card_cvv']
            ];

            $billingInfo = [
                'address' => $_POST['address'],
                'phone' => $_POST['phone'],
                'card_holder' => $_POST['card_holder']
            ];

            $amount = 12.99;

            $financialModel = new FinancialModel();
            
            // // Exécution de la commande
            $result = $financialModel->processOrder($userId, $mosaicId, $cardInfo, $amount, $billingInfo);

            // // VERIFICATION CRITIQUE : Est-ce un ID (succès) ou un message (échec) ?
            if (is_numeric($result)) {
                // // Succès : on vide la session et on redirige
                unset($_SESSION['pending_payment_mosaic_id']);
                header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/payment/confirmation?id=" . $result);
                exit;
            } else {
                // // Échec : On affiche l'erreur proprement
                // // On ré-affiche la vue de paiement avec l'erreur incluse
                // // (Ou une page d'erreur dédiée simple pour l'instant)
                
                $mosaicModel = new MosaicModel();
                $visualImage = $mosaicModel->getMosaicVisual($mosaicId);

                echo "<div style='background-color: #f8d7da; color: #721c24; padding: 20px; text-align: center; border-bottom: 2px solid #f5c6cb;'>";
                echo "<h3>Erreur lors du paiement</h3>";
                echo "<p>" . htmlspecialchars($result) . "</p>";
                echo "<p><a href='" . ($_ENV['BASE_URL'] ?? '') . "/payment'>Réessayer</a></p>";
                echo "</div>";
                
                // // Optionnel : ré-afficher le formulaire en dessous pour éviter un clic
                // $this->index(); // Attention aux boucles infinies si index() redirige
            }
        }
    }

    public function confirmation() {
        if (!isset($_GET['id'])) {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/images");
            exit;
        }

        $orderId = $_GET['id'];
        $commandeModel = new CommandeModel();
        
        // // Récupération des détails via la méthode mise à jour du modèle
        $orderDetails = $commandeModel->getOrderDetails($orderId);

        if (!$orderDetails) {
            echo "Commande introuvable ou erreur de récupération.";
            exit;
        }

        $this->render('invoice_views', [
            't' => $this->translations,
            'order' => $orderDetails,
            'css' => 'invoice_views.css'
        ]);
    }
}