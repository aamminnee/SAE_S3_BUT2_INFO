<?php
namespace App\Core;

use App\Models\TranslationModel;

/**
 * Abstract Base Controller
 * * All application controllers must extend this class.
 * * Handles common initialization tasks like Session management and Translation loading.
 * * Provides the 'render' method to display views.
 */
abstract class Controller {
    protected $trans = [];

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $lang = $_SESSION['lang'] ?? 'fr';

        if (class_exists('\\App\\Models\\TranslationModel')) {
            $translationModel = new TranslationModel();
            $this->trans = $translationModel->getTranslations($lang);
        }
    }

    public function render(string $file, array $data = [], string $template = 'default') {

        if (!isset($data['t'])) {
            $data['t'] = $this->trans;
        }

        $data['trans'] = $this->trans;

        extract($data);
        ob_start();

        require_once ROOT . '/App/Views/' . $file . '.php';

        $content = ob_get_clean();

        require_once ROOT . '/App/Views/' . $template . '.php';
    }
}