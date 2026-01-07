<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\AdminModel;

class AdminController extends Controller {
    private $admin_model;

    // // constructeur
    public function __construct() {
        // // appel du constructeur parent (important pour initialiser la vue et les trads)
        parent::__construct();
        
        $this->admin_model = new AdminModel();
        
        // // vérification admin
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            $baseUrl = $_ENV['BASE_URL'];
            header("Location: $baseUrl/index.php");
            exit;
        }
    }

    // // méthode par défaut
    public function index() {
        $this->stats();
    }

    // // page stats : chargement du css spécifique stats
    public function stats() {
        // // ici on appelle 'admin_stats_views.css' au lieu de 'admin_views.css'
        $this->render('admin_stats_views', ['css' => 'admin_stats_views.css']);
    }

    // // page fournisseur : chargement du css spécifique fournisseur
    public function supplier() {
        $rawOrders = $this->admin_model->getFactoryOrdersWithDetails();
        
        $groupedOrders = [];
        if ($rawOrders) {
            foreach ($rawOrders as $row) {
                $id = $row->id_FactoryOrder;
                if (!isset($groupedOrders[$id])) {
                    $groupedOrders[$id] = [
                        'info' => $row,
                        'items' => []
                    ];
                }
                $groupedOrders[$id]['items'][] = $row;
            }
        }

        // // ici on appelle 'admin_supplier_views.css'
        $this->render('admin_supplier_views', [
            'orders' => $groupedOrders,
            'css' => 'admin_supplier_views.css'
        ]);
    }
}