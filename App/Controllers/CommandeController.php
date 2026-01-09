<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\CommandeModel;
use App\Models\ImagesModel;
use App\Models\MosaicModel; 
use App\Models\TranslationModel;

/**
 * CommandeController
 * * Manages the user's order history and downloads.
 * * Allows users to view past orders, download assembly plans, and parts lists.
 */
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

        $commandes = $commandeModel->getCommandeByUserId($_SESSION['user_id']);

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
        
        if (!$commande || $commande->id_Customer != $_SESSION['user_id']) {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/commande");
            exit;
        }

        $mosaicModel = new MosaicModel();
        $items = $mosaicModel->getMosaicsByOrderId($id);

        $briquesAgregees = [];
        
        if ($items) {
            foreach ($items as $itm) {
                $pieces = $mosaicModel->getBricksList($itm->id_Mosaic);
                
                foreach ($pieces as $piece) {
                    $key = $piece['size'] . '_' . $piece['color'];
                    
                    if (isset($briquesAgregees[$key])) {
                        $briquesAgregees[$key]['count'] += $piece['count'];
                    } else {
                        $briquesAgregees[$key] = $piece;
                    }
                }
            }
        }
        
        $briques = array_values($briquesAgregees);
        
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
            'visuel' => $items[0]->visuel ?? null,
            'css' => 'commande_detail_views.css'
        ]);
    }

    public function downloadCsv($id) {
        $this->checkAuth();
        $mosaicModel = new MosaicModel();
        
        $briques = $mosaicModel->getBricksList((int)$id);

        if (empty($briques)) {
             die("Aucune donnée pour cette mosaïque.");
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=Liste_Pieces_Mosaique_' . $id . '.csv');

        $output = fopen('php://output', 'w');
        fputs($output, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) )); // BOM UTF-8
        fputcsv($output, ['Couleur', 'Taille', 'Quantité'], ';');

        foreach ($briques as $b) {
            fputcsv($output, [
                strtoupper($b['color']), 
                $b['size'], 
                $b['count'],
            ], ';');
        }
        fclose($output);
        exit;
    }

    public function downloadImage($idMosaic) {
        $mosaicModel = new \App\Models\MosaicModel();
        
        $imageDataBase64 = $mosaicModel->getMosaicVisual($idMosaic); 
        
        if ($imageDataBase64) {
            $parts = explode(',', $imageDataBase64);
            $binary = base64_decode($parts[1]);
            
            header('Content-Type: image/png');
            header('Content-Disposition: attachment; filename="mosaique_lego_'.$idMosaic.'.png"');
            header('Content-Length: ' . strlen($binary));
            
            echo $binary;
            exit;
        } else {
            $_SESSION['error_message'] = "Impossible de générer l'image pour le téléchargement.";
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }
    }

    public function downloadPlan($id) {
        if (!isset($_SESSION['user_id'])) { header("Location: /user/login"); exit; }

        $mosaicModel = new \App\Models\MosaicModel();
        $planData = $mosaicModel->getMosaicPlanData((int)$id);

        if (!$planData) {
            header("Location: " . $_ENV['BASE_URL'] . "/commande");
            exit;
        }

        $this->render('plan_views', [
            'id' => $id,
            'plan' => $planData
        ], 'empty'); 
    }

    private function checkAuth() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/user/login");
            exit;
        }
    }
}