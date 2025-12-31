<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\FinancialModel;
use App\Models\TranslationModel;
use App\Models\MosaicModel;
use App\Models\CommandeModel;
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

            if (!isset($_SESSION['user_email'])) {
                $_SESSION['user_email'] = 'client@legofactory.com'; 
            }

            // // CORRECTION ICI : on cherche 'address' OU 'adress' pour éviter les erreurs
            $userAddress = $_POST['address'] ?? $_POST['adress'] ?? 'Adresse non fournie';

            $cardInfo = [
                'holder' => $_POST['card_holder'], 
                'number' => $_POST['card_number'], 
                'expiry' => $_POST['card_expiry'] . '-01',
                'cvv'    => $_POST['card_cvv']
            ];

            $billingInfo = [
                'address'     => $userAddress, // // On utilise la variable sécurisée
                'phone'       => $_POST['phone'] ?? '',
                'card_holder' => $_POST['card_holder']
            ];

            $amount = 12.99;

            $financialModel = new FinancialModel();
            $result = $financialModel->processOrder($userId, $mosaicId, $cardInfo, $amount, $billingInfo);

            if (is_numeric($result)) {
                $commandeModel = new CommandeModel();
                $orderDetails = $commandeModel->getOrderDetails($result);
                $emailToSend = $_SESSION['email'] ?? $_SESSION['user_email'];

                $this->sendInvoiceEmail($emailToSend, $orderDetails);

                unset($_SESSION['pending_payment_mosaic_id']);
                header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/payment/confirmation?id=" . $result);
                exit;
            } else {
                // ... (gestion erreur inchangée)
                $mosaicModel = new MosaicModel();
                $visualImage = $mosaicModel->getMosaicVisual($mosaicId);
                echo "<div style='background-color: #f8d7da; padding: 20px;'>Erreur : " . htmlspecialchars($result) . "</div>";
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

// envoi de la facture par email
    private function sendInvoiceEmail($email, $order) {
        // instanciation de phpmailer
        $mail = new PHPMailer(true);

        try {
            // configuration du serveur smtp
            $mail->isSMTP();
            $mail->Host       = $_ENV['MAILJET_HOST'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['MAILJET_USERNAME'];
            $mail->Password   = $_ENV['MAILJET_PASSWORD'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $_ENV['MAILJET_PORT'];

            // expéditeur et destinataire
            $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
            $mail->addAddress($email);

            // format html activé
            $mail->isHTML(true);
            $mail->Subject = "Votre facture LegoFactory - Commande #" . ($order['invoice_number'] ?? $order['id_Order']);

            // récupération des données pour l'affichage
            $firstName = htmlspecialchars($order['first_name'] ?? 'Client');
            $lastName = htmlspecialchars($order['last_name'] ?? '');
            $amount = number_format($order['total_amount'] ?? 0, 2);
            
            // // on récupère seulement l'adresse globale
            $address = htmlspecialchars($order['adress'] ?? '');
            $date = date('d/m/Y', strtotime($order['order_date'] ?? 'now'));

            // construction du corps de l'email en html
            // utilisation de styles inline pour la compatibilité avec les clients mail
            $body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #333;'>
                <div style='background-color: #f8f9fa; padding: 20px; text-align: center; border-bottom: 3px solid #0056b3;'>
                    <h1 style='color: #0056b3; margin: 0;'>Confirmation de commande</h1>
                </div>
                
                <div style='padding: 20px;'>
                    <p>Bonjour <strong>$firstName $lastName</strong>,</p>
                    <p>Merci pour votre achat ! Votre paiement a été validé avec succès.</p>
                    
                    <h3 style='border-bottom: 1px solid #ddd; padding-bottom: 10px; margin-top: 30px;'>Récapitulatif de la facture</h3>
                    
                    <table style='width: 100%; border-collapse: collapse; margin-top: 15px;'>
                        <tr style='background-color: #f2f2f2;'>
                            <th style='padding: 12px; border: 1px solid #ddd; text-align: left;'>Description</th>
                            <th style='padding: 12px; border: 1px solid #ddd; text-align: right;'>Montant</th>
                        </tr>
                        <tr>
                            <td style='padding: 12px; border: 1px solid #ddd;'>
                                Mosaïque LEGO (Commande du $date)<br>
                                <small style='color: #666;'>Réf: " . ($order['invoice_number'] ?? 'N/A') . "</small>
                            </td>
                            <td style='padding: 12px; border: 1px solid #ddd; text-align: right;'>$amount €</td>
                        </tr>
                        <tr style='font-weight: bold; background-color: #e9ecef;'>
                            <td style='padding: 12px; border: 1px solid #ddd; text-align: right;'>Total Payé</td>
                            <td style='padding: 12px; border: 1px solid #ddd; text-align: right;'>$amount €</td>
                        </tr>
                    </table>

                    <div style='margin-top: 30px; background-color: #f8f9fa; padding: 15px; border-radius: 5px;'>
                        <h4 style='margin-top: 0;'>Adresse de facturation :</h4>
                        <p style='margin-bottom: 0;'>
                            $firstName $lastName<br>
                            $address
                        </p>
                    </div>
                </div>

                <div style='background-color: #333; color: #fff; text-align: center; padding: 15px; font-size: 0.8em; margin-top: 30px;'>
                    <p>Ceci est un email automatique, merci de ne pas y répondre directement.</p>
                    <p>&copy; " . date('Y') . " LegoFactory. Tous droits réservés.</p>
                </div>
            </div>";

            $mail->Body = $body;
            // version texte brut pour les clients mail ne supportant pas le html
            $mail->AltBody = "Bonjour $firstName, votre commande de $amount € a bien été confirmée. Merci pour votre achat.";

            // envoi effectif
            $mail->send();

        } catch (Exception $e) {
            // log de l'erreur en cas d'échec d'envoi
            error_log("Erreur Mailer : " . $mail->ErrorInfo);
        }
    }
}