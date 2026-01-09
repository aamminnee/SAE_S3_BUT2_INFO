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

/**
 * PaymentController
 * * Handles the checkout process, payment simulation, and order finalization.
 * * Transforms temporary cart items into permanent database records (Orders & Mosaics).
 */
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

        $subTotal = 0;
        foreach ($_SESSION['cart'] as $item) { $item = (array)$item; $subTotal += $item['price']; }

        $delivery = 4.99;
        $totalPrice = $subTotal + $delivery;

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
            
            $subTotal = 0;
            foreach ($_SESSION['cart'] as $item) { $item = (array)$item; $subTotal += $item['price']; }
            
            $delivery = \App\Models\MosaicModel::DELIVERY_FEE;
            $totalAmount = $subTotal + $delivery;

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

            $mosaicModel = new MosaicModel();
            $imagesModel = new ImagesModel();
            $realMosaicIds = []; 
            
            foreach ($_SESSION['cart'] as $item) {
                $item = (array)$item;
                $imgId = $item['image_id'];
                $style = $item['style'];

                $imgDb = $imagesModel->getImageById($imgId, $userId);
                
                if ($imgDb) {
                    $ext = (strpos($imgDb->file_type, 'png') !== false) ? 'png' : 'jpg';
                    $genResults = $mosaicModel->generateTemporaryMosaics($imgId, $imgDb->file, $ext);
                    $pavageContent = $genResults[$style]['txt'] ?? null;

                    if ($pavageContent) {
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

            $financialModel = new FinancialModel();
            $result = $financialModel->processOrder($userId, $realMosaicIds[0], $cardInfo, $totalAmount, $billingInfo);

            if (!is_numeric($result)) {
                $clientInfo = (array) $usersModel->getUserById($userId);
                $this->render('payment_views', [
                    't' => $this->translations, 'total' => $totalAmount, 'cart' => $_SESSION['cart'],
                    'client' => $clientInfo, 'css' => 'payment_views.css',
                    'error' => "Erreur paiement : " . $result
                ]);
                return;
            }
            
            $orderId = (int)$result;

            foreach ($realMosaicIds as $idMosaic) {
                $sqlLink = "UPDATE Mosaic SET id_Order = ? WHERE id_Mosaic = ?";
                $mosaicModel->requete($sqlLink, [$orderId, $idMosaic]);
                
                if (!$mosaicModel->hasComposition($idMosaic)) {
                    $mosaicModel->saveMosaicComposition($idMosaic);
                }
            }

            $mosaicModel->deductStockFromMosaic($idMosaic);

            $commandeModel = new CommandeModel(); 
            $orderDetails = $commandeModel->getOrderDetails($orderId);
            $orderDetails['total_amount'] = $totalAmount; 
            
            $this->sendInvoiceEmail($billingInfo['email'], $orderDetails);
            unset($_SESSION['cart']);

            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/payment/confirmation?id=" . $orderId);
            exit;
        }
    }

    public function confirmation() {
        if (!isset($_GET['id'])) { header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/index.php"); exit; }
        
        $orderId = (int)$_GET['id'];
        $commandeModel = new CommandeModel();
        $mosaicModel = new MosaicModel();
        
        $orderDetails = $commandeModel->getOrderDetails($orderId);
        if (!$orderDetails) { header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/index.php"); exit; }
        $orderDetails = (array) $orderDetails; 

        $items = $mosaicModel->getMosaicsByOrderId($orderId);
        
        $totalHandling = 0;
        $itemsTotalTTC = 0;
        $handlingUnit = \App\Models\MosaicModel::HANDLING_FEE; 

        foreach ($items as $item) {
            $pavage = is_object($item) ? $item->pavage : $item['pavage'];
            
            $price = $mosaicModel->calculatePriceFromContent($pavage);
            $pieces = $mosaicModel->countPiecesFromContent($pavage);
            
            if (is_object($item)) {
                $item->price = $price;
                $item->pieces = $pieces;
            } else {
                $item['price'] = $price;
                $item['pieces'] = $pieces;
            }
            
            $totalHandling += $handlingUnit;
            $itemsTotalTTC += $price; 
        }

        $deliveryTTC = \App\Models\MosaicModel::DELIVERY_FEE;
        $totalTTC = $itemsTotalTTC + $deliveryTTC;

        $tvaRate = 0.20;
        $coeff = 1 + $tvaRate;

        $itemsHT = $itemsTotalTTC / $coeff;    
        $deliveryHT = $deliveryTTC / $coeff;      
        $totalHT = $totalTTC / $coeff;        

        $totalTVA = $totalTTC - $totalHT;

        $this->render('invoice_views', [
            't' => $this->translations, 
            'order' => $orderDetails,   
            'items' => $items,
            'totalHandling' => $totalHandling,
            'handlingUnit' => $handlingUnit,
            'itemsTotalTTC' => $itemsTotalTTC,
            'itemsHT' => $itemsHT,
            'deliveryTTC' => $deliveryTTC,
            'deliveryHT' => $deliveryHT,
            'totalHT' => $totalHT,
            'totalTVA' => $totalTVA,
            'totalTTC' => $totalTTC,
            'css' => 'invoice_views.css'
        ]);
    }

    private function sendInvoiceEmail($email, $order) {
        $mail = new PHPMailer(true);
        $mosaicModel = new MosaicModel();
        
        $items = $mosaicModel->getMosaicsByOrderId($order['id_Order']);
        $pieces = $mosaicModel->countPiecesFromContent($item->pavage); // AJOUT
        $handlingUnit = \App\Models\MosaicModel::HANDLING_FEE;

        $rowsHtml = '';
        foreach ($items as $item) {
            $price = $mosaicModel->calculatePriceFromContent($item->pavage);
            $rowsHtml .= '<tr>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                    Mosaïque LEGO®<br>
                    <small style="color:#666; font-size: 11px;">Dont '.$handlingUnit.'€ préparation inclus</small>
                </td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right;">1</td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right;">'.number_format($price, 2).' €</td>
            </tr>';
        }

        $delivery = \App\Models\MosaicModel::DELIVERY_FEE;
        $rowsHtml .= '
            <tr style="background-color: #fdfdfd;">
                <td colspan="2" style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right; color: #555;">Livraison</td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right;">'.number_format($delivery, 2).' €</td>
            </tr>';

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
            $mail->CharSet = 'UTF-8';
            
            $invoiceNum = $order['invoice_number'] ?? $order['id_Order'];
            $mail->Subject = "Votre facture LegoFactory - Commande #$invoiceNum";
            
            $total = number_format($order['total_amount'] ?? 0, 2);
            
            // Template Mail amélioré
            $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; color: #333;'>
                <h1 style='color: #006CB7;'>Merci pour votre commande !</h1>
                <p>Voici le récapitulatif de votre commande <strong>#$invoiceNum</strong>.</p>
                
                <table style='width: 100%; border-collapse: collapse; margin-top: 20px;'>
                    <thead>
                        <tr style='background-color: #f8f9fa;'>
                            <th style='padding: 10px; text-align: left;'>Article</th>
                            <th style='padding: 10px; text-align: right;'>Qté</th>
                            <th style='padding: 10px; text-align: right;'>Prix</th>
                        </tr>
                    </thead>
                    <tbody>
                        $rowsHtml
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan='2' style='padding: 10px; text-align: right; font-weight: bold;'>TOTAL</td>
                            <td style='padding: 10px; text-align: right; font-weight: bold; color: #D92328;'>$total €</td>
                        </tr>
                    </tfoot>
                </table>
            </div>";
            
            $mail->send();
        } catch (Exception $e) { error_log("Mailer Error: " . $mail->ErrorInfo); }
    }
}