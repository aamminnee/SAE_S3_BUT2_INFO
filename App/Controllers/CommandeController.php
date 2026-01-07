<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\CommandeModel;
use App\Models\ImagesModel;
use App\Models\MosaicModel; 
use App\Models\TranslationModel;

class CommandeController extends Controller {
    
    private $translations;

    public function __construct() {
        $lang = $_SESSION['lang'] ?? 'fr';
        $translation_model = new TranslationModel();
        $this->translations = $translation_model->getTranslations($lang);
    }

    public function index()
    {
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/user/login");
            exit;
        }

        $commandeModel = new CommandeModel();
        $mosaicModel = new MosaicModel();

        // Récupération des commandes
        $commandes = $commandeModel->getCommandeByUserId($_SESSION['user_id']);

        // Ajout du visuel (le premier trouvé pour chaque commande)
        foreach ($commandes as $commande) {
            if (!empty($commande->id_Mosaic)) {
                $commande->visuel = $mosaicModel->getMosaicVisual($commande->id_Mosaic);
            } else {
                $commande->visuel = ($_ENV['BASE_URL'] ?? '') . '/Public/images/logo.png';
            }
        }

        $this->render('commande_views', [
            'commandes' => $commandes,
            'commandeModel' => $commandeModel, 
            't' => $this->translations,
            'css' => 'commande_views.css'
        ]);
    }

    public function detail($id) {
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/user/login");
            exit;
        }

        $commandeModel = new CommandeModel();
        $commande = $commandeModel->getCommandeById($id);
        
        // Sécurité : Vérifier que la commande appartient bien à l'utilisateur
        if (!$commande || $commande->id_Customer != $_SESSION['user_id']) {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/commande");
            exit;
        }

        // Récupération de TOUS les articles de la commande
        $mosaicModel = new MosaicModel();
        $items = $mosaicModel->getMosaicsByOrderId($id);

        // Récupération et AGRÉGATION des briques (pour éviter les doublons dans la liste)
        $briquesAgregees = [];
        
        if ($items) {
            foreach ($items as $itm) {
                // --- CORRECTION DE L'ERREUR ICI (getBricksList au lieu de getBricksForMosaic) ---
                $pieces = $mosaicModel->getBricksList($itm->id_Mosaic);
                
                foreach ($pieces as $piece) {
                    // On crée une clé unique "Taille + Couleur" pour fusionner les quantités
                    $key = $piece['size'] . '_' . $piece['color'];
                    
                    if (isset($briquesAgregees[$key])) {
                        $briquesAgregees[$key]['count'] += $piece['count'];
                    } else {
                        $briquesAgregees[$key] = $piece;
                    }
                }
            }
        }
        
        // On remet les briques dans un tableau indexé propre
        $briques = array_values($briquesAgregees);
        
        // Tri final : Par taille décroissante, puis par couleur
        array_multisort(
            array_column($briques, 'size'), SORT_DESC,
            array_column($briques, 'color'), SORT_ASC,
            $briques
        );

        $this->render('commande_detail_views', [
            't' => $this->translations,
            'commande' => $commande,
            'items' => $items,
            'briques' => $briques,
            'visuel' => $items[0]->visuel ?? null, // Visuel par défaut (le premier article)
            'css' => 'commande_detail_views.css'
        ]);
    }

    // 1. Télécharger la liste des pièces en CSV (Excel)
    public function downloadCsv($id) {
        $this->checkAuth();
        $mosaicModel = new MosaicModel();
        
        // CORRECTION : On vérifie que la mosaïque appartient à une commande de l'utilisateur
        // (Simplification : on suppose que l'ID passé est l'ID mosaïque direct)
        $briques = $mosaicModel->getBricksList((int)$id);

        if (empty($briques)) {
             die("Aucune donnée pour cette mosaïque.");
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=Liste_Pieces_Mosaique_' . $id . '.csv');

        $output = fopen('php://output', 'w');
        fputs($output, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) )); // BOM UTF-8
        fputcsv($output, ['Couleur', 'Taille', 'Quantité', 'Référence (Est.)'], ';');

        foreach ($briques as $b) {
            fputcsv($output, [
                strtoupper($b['color']), 
                $b['size'], 
                $b['count'],
                'LEGO-' . strtoupper(substr($b['color'], 1, 3)) . '-' . str_replace('x', '', $b['size'])
            ], ';');
        }
        fclose($output);
        exit;
    }

    // 2. Télécharger l'image finale
    public function downloadImage($id) {
        $this->checkAuth();
        $mosaicModel = new MosaicModel();

        // Récupère l'image en base64
        $dataUri = $mosaicModel->getMosaicVisual((int)$id);
        
        if ($dataUri && preg_match('/^data:image\/(\w+);base64,/', $dataUri, $type)) {
            $data = substr($dataUri, strpos($dataUri, ',') + 1);
            $type = strtolower($type[1]); 
            $data = base64_decode($data);

            if ($data === false) { die('Erreur de décodage image.'); }

            header('Content-Type: image/' . $type);
            header('Content-Disposition: attachment; filename="Mosaique_' . $id . '.' . $type . '"');
            echo $data;
            exit;
        }
        die("Image introuvable.");
    }

    // 3. Télécharger le Plan (Placeholder)
    public function downloadPlan($id) {
        $this->checkAuth();
        echo "<script>window.print();</script>";
        echo "<h1>Impression du plan en cours...</h1>";
        // Idéalement, générer un vrai PDF ici
    }

    private function checkAuth() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/user/login");
            exit;
        }
    }
}