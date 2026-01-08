<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\UsersModel;
use App\Models\TokensModel;
use App\Models\TranslationModel;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

/**
 * UserController
 * * Manages user authentication and account security.
 * Handles Login, Registration, Password Reset, and Two-Factor Authentication (2FA).
 */
class UserController extends Controller {
    private $user_model;
    private $token_model;
    private $mail;
    private $translations;

    public function __construct() {
        parent::__construct();
        
        $this->user_model = new UsersModel();
        $this->token_model = new TokensModel();
        $this->mail = new PHPMailer(true);
        
        $dotenv = Dotenv::createImmutable(ROOT);
        $dotenv->load();

        $this->translations = $this->trans;
    }

    private function t($key, $default = '') {
        return $this->translations[$key] ?? $default;
    }

    public function login() {
        $baseUrl = $_ENV['BASE_URL'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['username']) && !empty($_POST['password'])) {
            $userCaptcha = trim($_POST['captcha'] ?? '');
            $token = trim($_POST['captcha_token'] ?? '');
            
            if (empty($token) || empty($userCaptcha) || strcasecmp($userCaptcha, $token) !== 0) {
                $message = $this->t('captcha_invalid', "Incorrect captcha. Please try again.");
                $this->render('login_views', [
                    'message' => $message,
                    'css' => 'login_views.css'
                ]);
                return;
            }

            $username = trim($_POST['username']);
            $password = $_POST['password'];
            
            $user = $this->user_model->getUserByUsername($username);

            $userMdp = is_object($user) ? $user->mdp : ($user['mdp'] ?? null);
            $userId = is_object($user) ? $user->id_user : ($user['id_user'] ?? null);
            $userEtat = is_object($user) ? $user->etat : ($user['etat'] ?? null);
            $userMode = is_object($user) ? $user->mode : ($user['mode'] ?? null);
            $userEmail = is_object($user) ? $user->email : ($user['email'] ?? null);
            $userRole = is_object($user) ? ($user->role ?? 'user') : ($user['role'] ?? 'user');

            if ($user && password_verify($password, $userMdp)) {
                
                if ($userMode === '2FA') {
                    $_SESSION['temp_2fa_user_id'] = $userId;
                    $_SESSION['temp_2fa_email']   = $userEmail;
                    
                    $token = $this->token_model->generateToken($userId, "2FA");
                    $this->sendVerificationEmail($userEmail, $token);
                    
                    header("Location: $baseUrl/user/verify");
                    exit;
                }

                $_SESSION['username'] = $username;
                $_SESSION['user_id']  = $userId;
                $_SESSION['email']    = $userEmail;
                $_SESSION['status']   = $userEtat;
                $_SESSION['mode']     = $userMode;
                $_SESSION['role']     = $userRole;
                
                if ($userRole === 'admin') {
                    header("Location: $baseUrl/user/admin");
                } else {
                    header("Location: $baseUrl/index.php"); 
                }
                exit;
            } else {
                $message = $this->t('login_error', "Incorrect username or password.");
                $this->render('login_views', [
                    'message' => $message,
                    'css' => 'login_views.css'
                ]);
            }
        } else {
            $this->render('login_views', [
            'css' => 'login_views.css'
        ]);
        }
    }

    public function admin() {
        $baseUrl = $_ENV['BASE_URL'] ?? '';
        
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header("Location: $baseUrl/index.php");
            exit;
        }

        header("Location: $baseUrl/admin");
        exit;
    }

    public function register() {
        $baseUrl = $_ENV['BASE_URL'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['username'], $_POST['password'])) {
            $email = trim($_POST['email']);
            $username = trim($_POST['username']);
            $password = $_POST['password'];
            $lastname = $_POST['lastname'];
            
            $passwordPattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
            if (!preg_match($passwordPattern, $password)) {
                $message = $this->t('password_invalid', 
                    "Le mot de passe doit contenir au moins 8 caractères, dont une majuscule, une minuscule, un chiffre et un caractère spécial."
                );
                $this->render('register_views', [
                    'message' => $message,
                    'css' => 'register_views.css'
                ]);
                return;
            }

            if (empty($lastname)) {
                $error = "Le nom de famille est obligatoire.";
                require '../App/Views/register_views.php'; // // note : à terme utiliser render() ici aussi
                return;
            }

            $result = $this->user_model->addUser($email, $username, $password, $lastname);
            
            if ($result === true) {
                $user = $this->user_model->getUserByUsername($username);
                $userId = is_object($user) ? $user->id_user : $user['id_user'];
                
                $token = $this->token_model->generateToken($userId, "validation");
                $this->sendVerificationEmail($email, $token);
                
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
            $this->render('register_views', [
                'css' => 'register_views.css'
            ]);
        }
    }

    public function resetPasswordForm() {
        if (isset($_POST['reset_password'])) {
            $password = $_POST['password'];
            $password_confirm = $_POST['password_confirm'];

            if ($password !== $password_confirm) {
                $error = "Les mots de passe ne correspondent pas.";
                $this->render('reset_password_views', [
                    'error' => $error,
                    'css' => 'reset_password_views.css'
                ]);
                return;
            }

            $validation = $this->user_model->validateNewPassword($_SESSION['user_id'], $password);

            if ($validation !== true) {
                $this->render('reset_password_views', [
                    'error' => $validation,
                    'css' => 'reset_password_views.css'
                ]);
                return;
            }

            $this->user_model->updatePassword($_SESSION['user_id'], $password);
            
            $_SESSION['success_message'] = "Mot de passe modifié avec succès.";
            
            header('Location: ' . $_ENV['BASE_URL'] . '/setting');
            exit;

        } else {
            $this->render('reset_password_views', [
                'css' => 'reset_password_views.css'
            ]);
        }
    }

    public function resetPassword() {
        $baseUrl = $_ENV['BASE_URL'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email'])) {
            $email = trim($_POST['email']);
            
            $user = $this->user_model->getUserByEmail($email);

            if ($user) {
                $userId = is_object($user) ? $user->id_user : $user['id_user'];
                $userEmail = is_object($user) ? $user->email : $user['email'];

                $_SESSION['email'] = $userEmail; 

                $token = $this->token_model->generateToken($userId, "reinitialisation");
                $this->sendVerificationEmail($userEmail, $token);

                header("Location: $baseUrl/user/verify");
                exit;
            } else {
                $message = "Aucun compte associé à cet email.";
                $this->render('forgot_password_views', [
                    'message' => $message,
                    'css' => 'login_views.css'
                ]);
            }
        }
        elseif (isset($_SESSION['user_id'])) {
            $token = $this->token_model->generateToken($_SESSION['user_id'], "reinitialisation");
            $this->sendVerificationEmail($_SESSION['email'], $token);
            header("Location: $baseUrl/user/verify");
            exit;
        }
        else {
            $this->render('forgot_password_views', [
                'css' => 'login_views.css'
            ]);
        }
    }

    public function verify() {
        $baseUrl = $_ENV['BASE_URL'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
            $token = $_POST['token'];
            
            $token_data = $this->token_model->verifyToken($token);

            if ($token_data) {
                $this->token_model->consumeToken($token);
                $this->token_model->deleteToken();
                
                $userId = is_object($token_data) ? $token_data->id_Customer : $token_data['id_Customer'];
                $types = is_object($token_data) ? $token_data->types : $token_data['types'];

                if ($types === 'validation') {
                    $this->user_model->activateUser($userId);
                    if(isset($_SESSION['user_id'])) {
                        $_SESSION['status'] = 'valide';
                        header("Location: $baseUrl/index.php");
                        exit;
                    }
                    header("Location: $baseUrl/user/login");
                    exit;

                } elseif ($types === 'reinitialisation') {
                    $_SESSION['user_id'] = $userId; 
                    header("Location: $baseUrl/user/resetPasswordForm"); 
                    exit;

                } elseif ($types === '2FA') {
                    $userFull = $this->user_model->getUserById($userId); 
                    
                    if ($userFull) {
                        $idUser = is_object($userFull) ? $userFull->id_user : $userFull['id_user'];
                        $username = is_object($userFull) ? $userFull->username : $userFull['username'];
                        $email = is_object($userFull) ? $userFull->email : $userFull['email'];
                        $etat = is_object($userFull) ? $userFull->etat : $userFull['etat'];
                        $mode = is_object($userFull) ? $userFull->mode : $userFull['mode'];
                        $role = is_object($userFull) ? ($userFull->role ?? 'user') : ($userFull['role'] ?? 'user');
                        
                        $_SESSION['user_id']  = $idUser;
                        $_SESSION['username'] = $username;
                        $_SESSION['email']    = $email;
                        $_SESSION['status']   = $etat;
                        $_SESSION['mode']     = $mode;
                        $_SESSION['role']     = $role;
                        
                        unset($_SESSION['temp_2fa_user_id']);
                        unset($_SESSION['temp_2fa_email']);
                        
                        if ($role === 'admin') {
                            header("Location: $baseUrl/user/admin");
                        } else {
                            header("Location: $baseUrl/index.php");
                        }
                        exit;
                    } else {
                        $message = "Erreur critique : utilisateur introuvable.";
                        $this->render('login_views', [
                            'message' => $message,
                            'css' => 'login_views.css'
                        ]);
                        exit;
                    }
                }
            } else {
                $message = $this->t('token_invalid', "Code invalide ou expiré.");
                $this->render('verify_views', [
                    'message' => $message,
                    'css' => 'verify_views.css'
                ]); 
            }
        } else {
            $this->render('verify_views', [
                'css' => 'verify_views.css'
            ]);
        }
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
            $this->mail->Subject = $this->t('verification_code_subject', "Verification code");
            
            $bodyTemplate = $this->t('verification_code_body', "Your verification code is: %TOKEN%");
            if (empty($bodyTemplate)) {
                $bodyTemplate = "Your verification code is: %TOKEN%";
            }
            $body = str_replace('%TOKEN%', $token, $bodyTemplate);
            
            $this->mail->Body = $body;
            $this->mail->send();
        } catch (Exception $e) {
            error_log("Mail error: " . $this->mail->ErrorInfo);
        }
    }

    public function toggle2FA() {
        $baseUrl = $_ENV['BASE_URL'];

        if (!isset($_SESSION['user_id'])) {
            header("Location: $baseUrl/user/login");
            exit;
        }

        $id_user = $_SESSION['user_id'];
        $action = $_POST['mode'];
        
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
        
        $this->render('setting_views', [
            'message' => $message,
            'css' => 'setting_views.css',   
            'trans' => $this->translations 
        ]);
    }

    public function logout() {
        $baseUrl = $_ENV['BASE_URL'];
        session_unset();
        session_destroy();
        header("Location: $baseUrl/user/login");
        exit;
    }
}