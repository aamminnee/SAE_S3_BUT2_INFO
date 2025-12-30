<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\UsersModel;
use App\Models\TokensModel;
use App\Models\TranslationModel;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

class UserController extends Controller {
    private $user_model;
    private $token_model;
    private $mail;
    private $translations;

    // constructeur : initialisation des modèles et du mailer
    public function __construct() {
        // on instancie les modèles
        $this->user_model = new UsersModel();
        $this->token_model = new TokensModel();
        $this->mail = new PHPMailer(true);
        
        // chargement des variables d'environnement
        $dotenv = Dotenv::createImmutable(ROOT);
        $dotenv->load();
        
        // gestion de la langue
        $lang = $_SESSION['lang'] ?? 'fr';
        $translation_model = new TranslationModel();
        $this->translations = $translation_model->getTranslations($lang);
    }

    // fonction utilitaire pour récupérer les traductions
    private function t($key, $default = '') {
        return $this->translations[$key] ?? $default;
    }

    // gestion de la connexion
    public function login() {
        // récupération de l'url de base pour les redirections
        $baseUrl = $_ENV['BASE_URL'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['username']) && !empty($_POST['password'])) {
            // validation du captcha
            $userCaptcha = trim($_POST['captcha'] ?? '');
            $token = trim($_POST['captcha_token'] ?? '');
            
            if (empty($token) || empty($userCaptcha) || strcasecmp($userCaptcha, $token) !== 0) {
                $message = $this->t('captcha_invalid', "Incorrect captcha. Please try again.");
                $this->render('login_views', ['message' => $message]);
                return;
            }

            // traitement de la connexion
            $username = trim($_POST['username']);
            $password = $_POST['password'];
            
            // appel au modèle qui fait la jointure customer/savecustomer
            $user = $this->user_model->getUserByUsername($username);

            // adaptation pour supporter tableau ou objet selon le retour pdo
            $userMdp = is_object($user) ? $user->mdp : ($user['mdp'] ?? null);
            $userId = is_object($user) ? $user->id_user : ($user['id_user'] ?? null);
            $userEtat = is_object($user) ? $user->etat : ($user['etat'] ?? null);
            $userMode = is_object($user) ? $user->mode : ($user['mode'] ?? null);
            $userEmail = is_object($user) ? $user->email : ($user['email'] ?? null);

            if ($user && password_verify($password, $userMdp)) {
                
                // gestion de la double authentification (2fa)
                if ($userMode === '2FA') {
                    // stockage temporaire pour la validation 2fa
                    $_SESSION['temp_2fa_user_id'] = $userId;
                    $_SESSION['temp_2fa_email']   = $userEmail;
                    
                    // génération et envoi du token
                    $token = $this->token_model->generateToken($userId, "2FA");
                    $this->sendVerificationEmail($userEmail, $token);
                    
                    // redirection vers la page de vérification (renommée verify)
                    header("Location: $baseUrl/user/verify");
                    exit;
                }

                // connexion classique sans 2fa
                $_SESSION['username'] = $username;
                $_SESSION['user_id']  = $userId;
                $_SESSION['email']    = $userEmail;
                $_SESSION['status']   = $userEtat;
                $_SESSION['mode']     = $userMode;
                
                // redirection vers la page d'accueil des images
                header("Location: $baseUrl/index.php"); 
                exit;
            } else {
                $message = $this->t('login_error', "Incorrect username or password.");
                $this->render('login_views', ['message' => $message]);
            }
        } else {
            // affichage simple du formulaire
            $this->render('login_views');
        }
    }

    // gestion de l'inscription
    public function register() {
        $baseUrl = $_ENV['BASE_URL'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['username'], $_POST['password'])) {
            $email = trim($_POST['email']);
            $username = trim($_POST['username']);
            $password = $_POST['password'];
            
            // validation de la complexité du mot de passe
            $passwordPattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
            if (!preg_match($passwordPattern, $password)) {
                $message = $this->t('password_invalid', 
                    "Le mot de passe doit contenir au moins 8 caractères, dont une majuscule, une minuscule, un chiffre et un caractère spécial."
                );
                $this->render('register_views', ['message' => $message]);
                return;
            }

            // tentative d'ajout de l'utilisateur (crée savecustomer + customer)
            $result = $this->user_model->addUser($email, $username, $password);
            
            if ($result === true) {
                // inscription réussie, on récupère l'user pour envoyer le token
                $user = $this->user_model->getUserByUsername($username);
                $userId = is_object($user) ? $user->id_user : $user['id_user'];
                
                $token = $this->token_model->generateToken($userId, "validation");
                $this->sendVerificationEmail($email, $token);
                
                // redirection vers verify
                header("Location: $baseUrl/user/verify");
                exit;
            } elseif ($result === "duplicate") {
                $_SESSION['register_message'] = $this->t('username_exists', "Ce nom d'utilisateur ou l'adresse email existe déjà.");
                header("Location: $baseUrl/user/register");
                exit;
            } else {
                 $_SESSION['register_message'] = $this->t('register_error', "L'inscription a échoué, veuillez réessayer.");
                 header("Location: $baseUrl/user/register");
                exit;
            }
        } else {
            // affichage du formulaire d'inscription
            $this->render('register_views');
        }
    }

    // formulaire de réinitialisation de mot de passe
    public function resetPasswordForm() {
        $baseUrl = $_ENV['BASE_URL'] ?? '';

        if (isset($_POST['reset_password'], $_POST['password'], $_POST['password_confirm'])) {
            $password = $_POST['password'];
            $password_confirm = $_POST['password_confirm'];

            // vérification de connexion
            if (!isset($_SESSION['user_id'])) {
                 header("Location: $baseUrl/user/login");
                 exit;
            }
            
            if ($password === $password_confirm) {
                $this->user_model->setPassword($_SESSION['user_id'], $password);
                $message = $this->t('password_reset_success', "Password reset successfully.");
                header("Location: $baseUrl/index.php");
                exit;
            } else {
                $message = $this->t('password_mismatch', "Passwords do not match.");
                $this->render('reset_password_views', ['message' => $message]);
            }
        } else {
            $this->render('reset_password_views');
        }
    }

    // demande de réinitialisation (envoi email)
    public function resetPassword() {
        $baseUrl = $_ENV['BASE_URL'] ?? '';

        if (!isset($_SESSION['user_id'])) {
            header("Location: $baseUrl/user/login");
            exit;
        }
        $token = $this->token_model->generateToken($_SESSION['user_id'], "reinitialisation");
        $this->sendVerificationEmail($_SESSION['email'], $token);
        
        header("Location: $baseUrl/user/verify");
        exit;
    }

    // méthode pour gérer la page de vérification de code
    public function verify() {
        // récupération de l'url de base
        $baseUrl = $_ENV['BASE_URL'] ?? '';

        // si le formulaire est soumis avec un token
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
            $token = $_POST['token'];
            
            // vérification du token via le modèle
            $token_data = $this->token_model->verifyToken($token);

            // gestion objet vs array pour token_data
            if ($token_data) {
                // suppression du token spécifique après usage (pour éviter le rejeu)
                $this->token_model->consumeToken($token);
                // nettoyage des vieux tokens expirés
                $this->token_model->deleteToken();
                
                // correction ici : on utilise id_Customer (nom de la colonne en bdd)
                $userId = is_object($token_data) ? $token_data->id_Customer : $token_data['id_Customer'];
                $types = is_object($token_data) ? $token_data->types : $token_data['types'];

                // cas 1 : validation de compte
                if ($types === 'validation') {
                    $this->user_model->activateUser($userId);
                    if(isset($_SESSION['user_id'])) {
                         $_SESSION['status'] = 'valide';
                    }
                    header("Location: $baseUrl/user/login");
                    exit;

                // cas 2 : réinitialisation de mot de passe
                } elseif ($types === 'reinitialisation') {
                    // connexion temporaire pour le reset
                    $_SESSION['user_id'] = $userId; 
                    header("Location: $baseUrl/user/resetPasswordForm"); 
                    exit;

                // cas 3 : authentification double facteur (2fa)
                } elseif ($types === '2FA') {
                    $userFull = $this->user_model->getUserById($userId); 
                    
                    // support array/objet pour userFull
                    if ($userFull) {
                        $idUser = is_object($userFull) ? $userFull->id_user : $userFull['id_user'];
                        $username = is_object($userFull) ? $userFull->username : $userFull['username'];
                        $email = is_object($userFull) ? $userFull->email : $userFull['email'];
                        $etat = is_object($userFull) ? $userFull->etat : $userFull['etat'];
                        $mode = is_object($userFull) ? $userFull->mode : $userFull['mode'];

                        // enregistrement des infos en session
                        $_SESSION['user_id']  = $idUser;
                        $_SESSION['username'] = $username;
                        $_SESSION['email']    = $email;
                        $_SESSION['status']   = $etat;
                        $_SESSION['mode']     = $mode;
                        
                        // nettoyage des variables temporaires
                        unset($_SESSION['temp_2fa_user_id']);
                        unset($_SESSION['temp_2fa_email']);
                        
                        header("Location: $baseUrl/index.php");
                        exit;
                    } else {
                        $message = "Erreur critique : utilisateur introuvable.";
                        $this->render('login_views', ['message' => $message]);
                        exit;
                    }
                }
            } else {
                // token invalide ou expiré
                $message = $this->t('token_invalid', "Code invalide ou expiré.");
                $this->render('verify_views', ['message' => $message]);
            }
        } else {
            // affichage simple du formulaire de vérification
            $this->render('verify_views');
        }
    }

    // envoi de l'email via phpmailer
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
            $this->mail->Subject = $this->t('verification_code_subject', "Verification code");
            
            $bodyTemplate = $this->t('verification_code_body', "Your verification code is: %TOKEN%");
            if (empty($bodyTemplate)) {
                $bodyTemplate = "Your verification code is: %TOKEN%";
            }
            $body = str_replace('%TOKEN%', $token, $bodyTemplate);
            
            $this->mail->Body = $body;
            $this->mail->send();
        } catch (Exception $e) {
            // logging d'erreur
            error_log("Mail error: " . $this->mail->ErrorInfo);
        }
    }

    // activation/désactivation 2fa
    public function toggle2FA() {
        $baseUrl = $_ENV['BASE_URL'] ?? '';

        if (!isset($_SESSION['user_id'])) {
            header("Location: $baseUrl/user/login");
            exit;
        }

        $id_user = $_SESSION['user_id'];
        $action = $_POST['mode'] ?? '';
        
        if ($action === 'enable') {
            $this->user_model->setModeById($id_user, '2FA');
            $_SESSION['mode'] = '2FA';
            $message = $this->t('2fa_enabled', "Two-factor authentication enabled.");
        } elseif ($action === 'disable') {
            $this->user_model->setModeById($id_user, null);
            $_SESSION['mode'] = null;
            $message = $this->t('2fa_disabled', "Two-factor authentication disabled.");
        } else {
            $message = $this->t('invalid_request', "Invalid request.");
        }
        
        $this->render('setting_views', ['message' => $message]);
    }

    // déconnexion
    public function logout() {
        $baseUrl = $_ENV['BASE_URL'] ?? '';
        session_unset();
        session_destroy();
        header("Location: $baseUrl/user/login");
        exit;
    }
}