<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\AdminModel;
use App\Models\UsersModel;
use App\Models\FinancialModel;
use App\Models\StockModel;

class AdminController extends Controller {
    private $admin_model;
    private $user_model;
    private $financial_model;
    private $stock_model;

    public function __construct() {
        parent::__construct();
        
        // 1. Initialisation des modèles
        $this->admin_model = new AdminModel();
        $this->user_model = new UsersModel();
        $this->financial_model = new FinancialModel();
        $this->stock_model = new StockModel();
        
        // 2. Sécurité : Seul l'admin passe
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            $baseUrl = $_ENV['BASE_URL'] ?? '';
            header("Location: $baseUrl/index.php");
            exit;
        }
    }

    /**
     * PAGE D'ACCUEIL DU DASHBOARD (/user/admin ou /admin)
     * C'est ici qu'on calcule les variables $stats et $lastOrders
     */
    public function index() {
        // --- ETAPE 1 : Récupérer les Stats (KPIs) ---
        // On utilise des "ternaires" (?? 0) pour éviter les bugs si la BDD est vide
        $stats = [
            'revenue'      => $this->financial_model->getTotalRevenue() ?? 0,
            'orders_count' => $this->financial_model->countOrders() ?? 0,
            'users_count'  => $this->user_model->countUsers() ?? 0,
            'low_stock'    => $this->stock_model->countLowStockItems(10) ?? 0
        ];

        // --- ETAPE 2 : Récupérer les dernières commandes ---
        $lastOrders = $this->financial_model->getLastOrders(5);
        if (!$lastOrders) {
            $lastOrders = []; // On assure que ce soit un tableau vide et pas NULL
        }

        // --- ETAPE 3 : Envoyer tout ça à la Vue ---
        // C'est cette partie qui manquait et causait l'erreur !
        $this->render('admin_views', [
            'stats' => $stats,           // La variable $stats est envoyée ici
            'lastOrders' => $lastOrders, // La variable $lastOrders est envoyée ici
            'css' => 'admin_views.css'         // On charge le CSS pro
        ]);
    }

    // --- Page Statistiques détaillées (Menu "Statistiques") ---
    public function stats() {
        $this->render('admin_stats_views', ['css' => 'admin_stats_views.css']);
    }

    // --- Page Fournisseurs (Menu "Fournisseur") ---
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

        $this->render('admin_supplier_views', [
            'orders' => $groupedOrders,
            'css' => 'admin_supplier_views.css'
        ]);
    }
}