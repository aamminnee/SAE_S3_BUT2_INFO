<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\CommandeModel;
use App\Models\CordBanquaireModel;
use App\Models\ImagesModel;
use App\Models\TranslationModel;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class PaymentController extends Controller
{
    private $commande_model;
    private $cord_banquaire_model;
    private $image_model;
    private $translations;
    private $mail;

    public function __construct()
    {
        $this->commande_model = new CommandeModel();
        $this->cord_banquaire_model = new CordBanquaireModel();
        $this->image_model = new ImagesModel();
        $this->mail = new PHPMailer(true);

        // chargement des traductions
        $lang = $_SESSION['lang'] ?? 'fr';
        $translation_model = new TranslationModel();
        $this->translations = $translation_model->getTranslations($lang);
    }

    private function t($key, $default = '') {
        return $this->translations[$key] ?? $default;
    }

    // affiche le formulaire de paiement
    public function index()
    {
        if (!isset($_SESSION['user_id']) || ($_SESSION['status'] ?? '') !== 'valide') {
            header("Location: /user/login");
            exit;
        }

        $this->render('payment_views', [
            'css' => 'payment_views.css',
            'translations' => $this->translations // on passe les trads à la vue
        ]);
    }

    // traite le paiement
    public function process()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /payment");
            exit;
        }

        // ... récupération des variables post ...
        $address = $_POST['address']; 
        $postal = $_POST['postal'];
        $city = $_POST['city'];
        $country = $_POST['country'];
        $phone = $_POST['phone'];
        $card_number = $_POST['card_number'];
        $expiry = $_POST['expiry'];
        $cvc = $_POST['cvc'];
        
        $user_id = $_SESSION['user_id'];
        $prix = 12.99;

        // insertion carte bancaire
        $cord_id = $this->cord_banquaire_model->insertCordBanquaire($card_number, $expiry, $cvc);
        
        if (!$cord_id) {
            $this->render('payment_views', ['error' => $this->t('bank_info_error')]);
            return;
        }

        // récupération de l'image
        $imageName = $_SESSION['last_image'] ?? '';
        $imageData = $this->image_model->getIdImageByIdentifiant($imageName);
        
        // gestion objet/array
        $images_id = is_object($imageData) ? $imageData->id : ($imageData['id'] ?? null);

        if (!$images_id) {
            echo "Erreur : Image introuvable.";
            return;
        }

        // sauvegarde commande
        $id_commande = $this->commande_model->saveCommande($user_id, $images_id, $address, $postal, $city, $country, $phone, $prix, $cord_id);

        if ($id_commande) {
            // mise en session pour la confirmation
            $_SESSION['order_id'] = $id_commande;
            $_SESSION['address'] = $address;
            $_SESSION['postal'] = $postal;
            $_SESSION['city'] = $city;
            $_SESSION['country'] = $country;
            $_SESSION['price'] = $prix;

            // envoi email
            $this->sendMailCommande($_SESSION['email']);

            // redirection vers la page de confirmation
            header("Location: /payment/success");
            exit;
        } else {
            $this->render('payment_views', ['error' => $this->t('order_save_error')]);
        }
    }

    // page de confirmation
    public function success()
    {
        $this->render('confirm_views', ['css' => 'confirm_views.css']);
    }

    private function sendMailCommande($user_email)
    {
        // ... ta logique phpmailer existante ...
        // assure-toi que $_ENV est bien chargé (normalement ok via main.php)
        try {
            // configuration smtp
            $this->mail->isSMTP();
            $this->mail->Host       = $_ENV['MAILJET_HOST'];
            $this->mail->SMTPAuth   = true;
            $this->mail->Username   = $_ENV['MAILJET_USERNAME'];
            $this->mail->Password   = $_ENV['MAILJET_PASSWORD'];
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port       = $_ENV['MAILJET_PORT'];
            
            $this->mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
            $this->mail->addAddress($user_email);
            
            // contenu
            $lang = $_SESSION['lang'] ?? 'fr';
            $subject = $this->t('order_summary_subject', 'Résumé commande');
            // ... construction du body comme dans ton fichier ...
            
            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body    = "Merci pour votre commande n°" . ($_SESSION['order_id'] ?? ''); 
            // simplifie ici pour l'exemple, remets ton body html complet
            
            $this->mail->send();
        } catch (Exception $e) {
            // log l'erreur si besoin
        }
    }
}