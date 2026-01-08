<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\TranslationModel;

class SettingController extends Controller {
    private $translation_model;

    public function __construct() {
        parent::__construct();
        $this->translation_model = new TranslationModel();
    }

    public function index() {
        // --- SUPPRESSION DU BLOC QUI REDIRIGEAIT VERS /SETTING ---
        // On ne garde que la gestion du thème si vous l'utilisez, 
        // sinon on peut aussi le déplacer dans une méthode à part.
        
        if (isset($_GET['action']) && $_GET['action'] === 'setTheme' && isset($_GET['theme'])) {
            $_SESSION['theme'] = $_GET['theme'];
            $baseUrl = $_ENV['BASE_URL'] ?? '';
            header("Location: $baseUrl/setting");
            exit;
        }

        // Récupération de la langue pour l'affichage de la vue
        $lang = $_SESSION['lang'] ?? 'fr';
        $translations = $this->translation_model->getTranslations($lang);

        $this->render('setting_views', [
            'css' => 'setting_views.css',
            'trans' => $translations,
            'success' => $_SESSION['success'] ?? null,
            'error'   => $_SESSION['error'] ?? null
        ]);
        
        unset($_SESSION['success'], $_SESSION['error']);
    }

    // C'est cette méthode qui sera appelée par le Header
    public function setLanguage() {
        if (isset($_GET['lang'])) {
            $lang = $_GET['lang'];
            // Petite sécurité pour ne pas mettre n'importe quoi en session
            if (in_array($lang, ['fr', 'en'])) {
                $_SESSION['lang'] = $lang;
            }
        }

        // C'EST ICI QUE LA MAGIE OPÈRE : 
        // On redirige vers la page d'où l'on vient (Login, Accueil, etc.)
        if (isset($_SERVER['HTTP_REFERER'])) {
            header('Location: ' . $_SERVER['HTTP_REFERER']);
        } else {
            // Au pire, retour à l'accueil
            header('Location: ' . ($_ENV['BASE_URL'] ?? '') . '/index.php');
        }
        exit;
    }
}