<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\TranslationModel;

/**
 * SettingController
 * * Manages user preferences such as Language and Security settings.
 */
class SettingController extends Controller {
    private $translation_model;

    public function __construct() {
        parent::__construct();
        $this->translation_model = new TranslationModel();
    }

    public function index() {
        if (isset($_GET['action']) && $_GET['action'] === 'setTheme' && isset($_GET['theme'])) {
            $_SESSION['theme'] = $_GET['theme'];
            $baseUrl = $_ENV['BASE_URL'] ?? '';
            header("Location: $baseUrl/setting");
            exit;
        }

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

    public function setLanguage() {
        if (isset($_GET['lang'])) {
            $lang = $_GET['lang'];
            if (in_array($lang, ['fr', 'en'])) {
                $_SESSION['lang'] = $lang;
            }
        }

        if (isset($_SERVER['HTTP_REFERER'])) {
            header('Location: ' . $_SERVER['HTTP_REFERER']);
        } else {
            header('Location: ' . ($_ENV['BASE_URL'] ?? '') . '/index.php');
        }
        exit;
    }
}