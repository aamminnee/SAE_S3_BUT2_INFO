<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\UsersModel;
use App\Models\TokensModel;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

class CompteController extends Controller {
    private $user_model;
    private $token_model;
    private $mail;

    public function __construct() {
        // // instanciation des modèles
        $this->user_model = new UsersModel();
        $this->token_model = new TokensModel();
        $this->mail = new PHPMailer(true);
        
        // // chargement des variables d'environnement pour le mailer
        $dotenv = Dotenv::createImmutable(ROOT);
        $dotenv->load();
    }

    // // affiche la page mon compte
    public function index() {
        // // vérification de la session
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // // si pas connecté, redirection vers login
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . $_ENV['BASE_URL'] . '/user/login');
            exit;
        }

        // // récupération des infos utilisateur
        $id_user = $_SESSION['user_id'];
        $user = $this->user_model->getUserById($id_user);

        // // on passe les données à la vue
        $this->render('compte_views', ['user' => $user]);
    }

    // // lance la procédure d'activation
    public function activer() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $baseUrl = $_ENV['BASE_URL'] ?? '';

        if (!isset($_SESSION['user_id'])) {
            header("Location: $baseUrl/user/login");
            exit;
        }

        $id_user = $_SESSION['user_id'];
        $email = $_SESSION['email'];

        // // génération du token de validation
        $token = $this->token_model->generateToken($id_user, "validation");
        
        // // envoi de l'email (même logique que usercontroller)
        $this->sendVerificationEmail($email, $token);

        // // redirection vers le formulaire de vérification existant
        header("Location: $baseUrl/user/verify");
        exit;
    }

    // // fonction privée pour envoyer l'email
    private function sendVerificationEmail($email, $token) {
        try {
            $this->mail->isSMTP();
            $this->mail->Host       = $_ENV['MAILJET_HOST'];
            $this->mail->SMTPAuth   = true;
            $this->mail->Username   = $_ENV['MAILJET_USERNAME'];
            $this->mail->Password   = $_ENV['MAILJET_PASSWORD'];
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port       = $_ENV['MAILJET_PORT'];
            $this->mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
            $this->mail->addAddress($email);
            $this->mail->isHTML(true);
            $this->mail->Subject = "Code d'activation";
            $this->mail->Body = "Votre code d'activation est : " . $token;
            $this->mail->send();
        } catch (Exception $e) {
            error_log("Mail error: " . $this->mail->ErrorInfo);
        }
    }
}