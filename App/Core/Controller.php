<?php
namespace App\Core;

use App\Models\TranslationModel;

abstract class Controller
{
    protected $trans = [];

    public function __construct()
    {
        // gestion de la session si pas démarrée
        if (session_status() === PHP_SESSION_NONE) session_start();

        // définition de la langue (défaut : fr)
        $lang = $_SESSION['lang'] ?? 'fr';

        // chargement des traductions si le modèle existe
        if (class_exists('\\App\\Models\\TranslationModel')) {
            $translationModel = new TranslationModel();
            $this->trans = $translationModel->getTranslations($lang);
        }
    }

    public function render(string $file, array $data = [], string $template = 'default')
    {
        // on ajoute les traductions aux données envoyées à la vue
        $data['trans'] = $this->trans;

        // on extrait le contenu des données
        extract($data);

        // on démarre le buffer de sortie
        ob_start();

        // on crée le chemin vers la vue
        require_once ROOT . '/App/Views/' . $file . '.php';

        // on stocke le contenu dans une variable
        $content = ob_get_clean();

        // on appelle le template de page
        require_once ROOT . '/App/Views/' . $template . '.php';
    }
}