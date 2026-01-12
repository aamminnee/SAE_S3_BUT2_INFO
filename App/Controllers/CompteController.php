<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\UsersModel;
use App\Models\TokensModel;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

/**
 * CompteController
 * * Manages the User Dashboard ("Mon Compte").
 * * Displays user profile information and account status.
 */
class CompteController extends Controller {

    private $user_model;
    private $token_model;
    private $mail;

    public function __construct() {
        
        $this->user_model = new UsersModel();
        $this->token_model = new TokensModel();
        $this->mail = new PHPMailer(true);
        
        $dotenv = Dotenv::createImmutable(ROOT);
        $dotenv->load();
    }

    public function index() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . $_ENV['BASE_URL'] . '/user/login');
            exit;
        }

        $id_user = $_SESSION['user_id'];
        $user = $this->user_model->getUserById($id_user);

        $this->render('compte_views', ['user' => $user]);
    }

    public function activer() {

        if (session_status() === PHP_SESSION_NONE) session_start();
        $baseUrl = $_ENV['BASE_URL'] ?? '';

        if (!isset($_SESSION['user_id'])) {
            header("Location: $baseUrl/user/login");
            exit;
        }

        $id_user = $_SESSION['user_id'];
        $email = $_SESSION['email'];

        $token = $this->token_model->generateToken($id_user, "validation");
        
        $this->sendVerificationEmail($email, $token);

        header("Location: $baseUrl/user/verify");
        exit;
    }

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