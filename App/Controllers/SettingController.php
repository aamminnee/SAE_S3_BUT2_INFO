<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\TranslationModel;

class SettingController extends Controller {
    private $translation_model;

    public function __construct() {
        $this->translation_model = new TranslationModel();
    }

    public function index() {
        // gestion du changement de langue via les paramètres get
        if (isset($_GET['action']) && $_GET['action'] === 'setLanguage' && isset($_GET['lang'])) {
            $lang = $_GET['lang'];
            
            // on vérifie que la langue est bien 'fr' ou 'en' avant de changer la session
            if (in_array($lang, ['fr', 'en'])) {
                $_SESSION['lang'] = $lang;
            }

            // on redirige vers la page des paramètres pour rafraîchir l'affichage
            $baseUrl = $_ENV['BASE_URL'] ?? '';
            header("Location: $baseUrl/setting");
            exit;
        }

        // gestion du thème (existant)
        if (isset($_GET['action']) && $_GET['action'] === 'setTheme' && isset($_GET['theme'])) {
            $_SESSION['theme'] = $_GET['theme'];
            $baseUrl = $_ENV['BASE_URL'] ?? '';
            header("Location: $baseUrl/setting");
            exit;
        }

        // récupération de la langue depuis la session (par défaut fr)
        $lang = $_SESSION['lang'] ?? 'fr';
        $translations = $this->translation_model->getTranslations($lang);

        $this->render('setting_views', [
            'css' => 'setting_views.css',
            'trans' => $translations
        ]);
    }
}