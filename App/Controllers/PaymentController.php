<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\FinancialModel;
use App\Models\TranslationModel;
use App\Models\MosaicModel;
use App\Models\CommandeModel;
use App\Models\UsersModel;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class PaymentController extends Controller {
    private $translations;

    public function __construct() {
        $lang = $_SESSION['lang'] ?? 'fr';
        $translation_model = new TranslationModel();
        $this->translations = $translation_model->getTranslations($lang);
    }

    public function index() {
        // // vérification connexion
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/user/login");
            exit;
        }

        // // vérification qu'une image est en attente
        if (!isset($_SESSION['pending_payment_mosaic_id'])) {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/images");
            exit;
        }

        $mosaicId = $_SESSION['pending_payment_mosaic_id'];
        
        $mosaicModel = new MosaicModel();
        $visualImage = $mosaicModel->getMosaicVisual($mosaicId);

        $usersModel = new UsersModel();
        // // conversion en tableau car le model peut renvoyer un objet
        $clientInfo = (array) $usersModel->getUserById($_SESSION['user_id']);

        $this->render('payment_views', [
            't' => $this->translations,
            'price' => 12.99,
            'css' => 'payment_views.css',
            'mosaicImage' => $visualImage,
            'client' => $clientInfo 
        ]);
    }

    public function process() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $_SESSION['user_id'];
            $mosaicId = $_SESSION['pending_payment_mosaic_id'] ?? null;
            
            if (!$mosaicId) {
                header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/index.php");
                exit;
            }

            // // récupération des infos utilisateur
            $usersModel = new UsersModel();
            
            // // correction ici : on convertit le résultat (objet stdclass) en tableau
            $userInfoRaw = $usersModel->getUserById($userId);
            $userInfo = (array) $userInfoRaw;

            // // récupération de l'adresse depuis le formulaire
            $userAddress = $_POST['adress'] ?? 'Adresse non fournie';

            $cardInfo = [
                'number' => $_POST['card_number'], 
                'expiry' => $_POST['card_expiry'] . '-01',
                'cvv'    => $_POST['card_cvv']
            ];

            // // construction des infos de facturation
            // // note : dans usersmodel, first_name est aliasé en 'username'
            $billingInfo = [
                'adress'     => $userAddress,
                'phone'      => $_POST['phone'] ?? '',
                'first_name' => $userInfo['username'] ?? 'Client', 
                'last_name'  => $userInfo['last_name'] ?? 'Inconnu',
                'email'      => $userInfo['email'] ?? ($_SESSION['user_email'] ?? 'client@legofactory.com')
            ];

            $amount = 12.99;

            $financialModel = new FinancialModel();
            $result = $financialModel->processOrder($userId, $mosaicId, $cardInfo, $amount, $billingInfo);

            if (is_numeric($result)) {
                // // succès : on récupère les détails pour le mail
                $commandeModel = new CommandeModel();
                $orderDetails = $commandeModel->getOrderDetails($result);
                $emailToSend = $billingInfo['email'];

                $this->sendInvoiceEmail($emailToSend, $orderDetails);

                unset($_SESSION['pending_payment_mosaic_id']);
                header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/payment/confirmation?id=" . $result);
                exit;
            } else {
                $mosaicModel = new MosaicModel();
                $visualImage = $mosaicModel->getMosaicVisual($mosaicId);
                echo "<div style='background-color: #f8d7da; padding: 20px;'>Erreur : " . htmlspecialchars($result) . "</div>";
            }
        }
    }

    public function confirmation() {
        if (!isset($_GET['id'])) {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/index.php");
            exit;
        }

        $orderId = $_GET['id'];
        $commandeModel = new CommandeModel();
        $orderDetails = $commandeModel->getOrderDetails($orderId);

        if (!$orderDetails) {
            echo "Commande introuvable.";
            exit;
        }

        $this->render('invoice_views', [
            't' => $this->translations,
            'order' => $orderDetails,
            'css' => 'invoice_views.css'
        ]);
    }

    private function sendInvoiceEmail($email, $order) {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $_ENV['MAILJET_HOST'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['MAILJET_USERNAME'];
            $mail->Password   = $_ENV['MAILJET_PASSWORD'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $_ENV['MAILJET_PORT'];

            $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = "Votre facture LegoFactory - Commande #" . ($order['invoice_number'] ?? $order['id_Order']);

            $amount = number_format($order['total_amount'] ?? 0, 2);
            $address = htmlspecialchars($order['adress'] ?? '');
            
            $body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #333;'>
                <h1>Confirmation de commande</h1>
                <p>Merci pour votre achat !</p>
                <p>Montant : $amount €</p>
                <p>Adresse de facturation : $address</p>
            </div>";

            $mail->Body = $body;
            $mail->send();

        } catch (Exception $e) {
            error_log("Erreur Mailer : " . $mail->ErrorInfo);
        }
    }
}