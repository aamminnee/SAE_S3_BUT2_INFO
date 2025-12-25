<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\TranslationModel;

class SettingController extends Controller
{
    private $translation_model;

    public function __construct()
    {
        $this->translation_model = new TranslationModel();
    }

    public function index()
    {
        // gestion du changement de langue
        if (isset($_GET['action']) && $_GET['action'] === 'setLanguage' && isset($_GET['lang'])) {
            $_SESSION['lang'] = $_GET['lang'];
            // on recharge la page pour appliquer
            header("Location: /setting");
            exit;
        }

        // gestion du thème
        if (isset($_GET['action']) && $_GET['action'] === 'setTheme' && isset($_GET['theme'])) {
            $_SESSION['theme'] = $_GET['theme'];
            header("Location: /setting");
            exit;
        }

        // récupération des traductions pour la vue
        $lang = $_SESSION['lang'] ?? 'fr';
        $translations = $this->translation_model->getTranslations($lang);

        $this->render('setting_views', [
            'css' => 'setting_views_style.css',
            'translations' => $translations // pour pouvoir utiliser $translations['key'] dans la vue
        ]);
    }
}