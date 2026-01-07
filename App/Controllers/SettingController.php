<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\TranslationModel;

class SettingController extends Controller {
    private $translation_model;

    public function __construct() {
        // // appel du constructeur parent pour la session
        parent::__construct();
        $this->translation_model = new TranslationModel();
    }

    public function index() {
        // // gestion du changement de langue
        if (isset($_GET['action']) && $_GET['action'] === 'setLanguage' && isset($_GET['lang'])) {
            $lang = $_GET['lang'];
            if (in_array($lang, ['fr', 'en'])) {
                $_SESSION['lang'] = $lang;
            }
            $baseUrl = $_ENV['BASE_URL'] ?? '';
            header("Location: $baseUrl/setting");
            exit;
        }

        // // gestion du thème
        if (isset($_GET['action']) && $_GET['action'] === 'setTheme' && isset($_GET['theme'])) {
            $_SESSION['theme'] = $_GET['theme'];
            $baseUrl = $_ENV['BASE_URL'] ?? '';
            header("Location: $baseUrl/setting");
            exit;
        }

        // // récupération de la langue
        $lang = $_SESSION['lang'] ?? 'fr';
        $translations = $this->translation_model->getTranslations($lang);

        // // on affiche simplement la vue
        $this->render('setting_views', [
            'css' => 'setting_views.css',
            'trans' => $translations,
            // // on passe les messages de succès/erreur s'ils viennent d'une redirection
            'success' => $_SESSION['success'] ?? null,
            'error'   => $_SESSION['error'] ?? null
        ]);
        
        // // nettoyage des messages flash après affichage
        unset($_SESSION['success'], $_SESSION['error']);
    }
}