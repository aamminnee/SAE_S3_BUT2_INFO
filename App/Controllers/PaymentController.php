<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\FinancialModel;
use App\Models\TranslationModel;
use App\Models\MosaicModel;
use App\Models\CommandeModel;
use App\Models\UsersModel;
use App\Models\ImagesModel;
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
        if (!isset($_SESSION['user_id'])) { header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/user/login"); exit; }
        if (empty($_SESSION['cart'])) { header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/cart"); exit; }

        $totalPrice = 0;
        foreach ($_SESSION['cart'] as $item) { $item = (array)$item; $totalPrice += $item['price']; }

        $usersModel = new UsersModel();
        $clientInfo = (array) $usersModel->getUserById($_SESSION['user_id']);

        $this->render('payment_views', [
            't' => $this->translations,
            'total' => $totalPrice,
            'cart' => $_SESSION['cart'],
            'client' => $clientInfo,
            'css' => 'payment_views.css'
        ]);
    }

    public function process() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (empty($_SESSION['cart'])) { header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/cart"); exit; }

            $userId = $_SESSION['user_id'];
            $usersModel = new UsersModel();
            
            // Calcul montant total
            $totalAmount = 0;
            foreach ($_SESSION['cart'] as $item) { $item = (array)$item; $totalAmount += $item['price']; }

            // Infos facturation
            $userInfo = (array) $usersModel->getUserById($userId);
            $billingInfo = [
                'adress'     => $_POST['adress'] ?? 'Non fournie',
                'phone'      => $_POST['phone'] ?? '',
                'first_name' => $userInfo['username'] ?? 'Client', 
                'last_name'  => $userInfo['last_name'] ?? 'Inconnu',
                'email'      => $userInfo['email'] ?? 'email@test.com'
            ];
            $cardInfo = [
                'number' => $_POST['card_number'], 
                'expiry' => $_POST['card_expiry'] . '-01',
                'cvv'    => $_POST['card_cvv']
            ];

            // 1. Sauvegarde des Mosaïques en BDD (Table Mosaic)
            $mosaicModel = new MosaicModel();
            $imagesModel = new ImagesModel();
            $realMosaicIds = []; 
            
            foreach ($_SESSION['cart'] as $item) {
                $item = (array)$item;
                $imgId = $item['image_id'];
                $style = $item['style'];

                // On récupère l'image source pour régénérer le texte
                $imgDb = $imagesModel->getImageById($imgId, $userId);
                
                if ($imgDb) {
                    $ext = (strpos($imgDb->file_type, 'png') !== false) ? 'png' : 'jpg';
                    $genResults = $mosaicModel->generateTemporaryMosaics($imgId, $imgDb->file, $ext);
                    $pavageContent = $genResults[$style]['txt'] ?? null;

                    if ($pavageContent) {
                        // Création de la mosaïque (id_Order est NULL pour l'instant)
                        $newMosaicId = $mosaicModel->saveSelectedMosaic($imgId, $pavageContent, $style);
                        if ($newMosaicId) {
                            $realMosaicIds[] = $newMosaicId;
                        }
                    }
                }
            }

            if (empty($realMosaicIds)) {
                echo "Erreur : Impossible de créer les mosaïques.";
                exit;
            }

            // 2. Paiement et Création de la Commande (CustomerOrder)
            $financialModel = new FinancialModel();
            // On passe le 1er ID juste pour récupérer l'image de couverture dans le model
            $result = $financialModel->processOrder($userId, $realMosaicIds[0], $cardInfo, $totalAmount, $billingInfo);

            if (!is_numeric($result)) {
                // Erreur SQL renvoyée par le model
                $clientInfo = (array) $usersModel->getUserById($userId);
                $this->render('payment_views', [
                    't' => $this->translations, 'total' => $totalAmount, 'cart' => $_SESSION['cart'],
                    'client' => $clientInfo, 'css' => 'payment_views.css',
                    'error' => "Erreur paiement : " . $result
                ]);
                return;
            }
            
            $orderId = (int)$result;

            // 3. Liaison : On met à jour toutes les mosaïques avec l'ID de la commande
            foreach ($realMosaicIds as $idMosaic) {
                // C'est ICI que le lien 1 Commande -> N Mosaïques se fait
                $sqlLink = "UPDATE Mosaic SET id_Order = ? WHERE id_Mosaic = ?";
                $mosaicModel->requete($sqlLink, [$orderId, $idMosaic]);
                
                // On génère la liste des pièces (MosaicComposition)
                if (!$mosaicModel->hasComposition($idMosaic)) {
                    $mosaicModel->saveMosaicComposition($idMosaic);
                }
            }

            // 4. Finalisation
            $commandeModel = new CommandeModel(); 
            $orderDetails = $commandeModel->getOrderDetails($orderId);
            $orderDetails['total_amount'] = $totalAmount; 
            
            $this->sendInvoiceEmail($billingInfo['email'], $orderDetails);
            unset($_SESSION['cart']);

            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/payment/confirmation?id=" . $orderId);
            exit;
        }
    }

    public function confirmation() { /* Pas de changement ici */
        if (!isset($_GET['id'])) { header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/index.php"); exit; }
        $orderId = $_GET['id'];
        $commandeModel = new CommandeModel();
        $orderDetails = $commandeModel->getOrderDetails($orderId);
        $this->render('invoice_views', ['t' => $this->translations, 'order' => $orderDetails, 'css' => 'invoice_views.css']);
    }

    private function sendInvoiceEmail($email, $order) { /* Pas de changement ici */
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
            $mail->Body = "<div style='font-family: Arial;'><h1>Merci !</h1><p>Commande validée.</p><p>Total: $amount €</p></div>";
            $mail->send();
        } catch (Exception $e) { error_log("Mailer Error: " . $mail->ErrorInfo); }
    }
}