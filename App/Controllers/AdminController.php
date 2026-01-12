<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\AdminModel;
use App\Models\UsersModel;
use App\Models\FinancialModel;
use App\Models\StockModel;

/**
 * AdminController
 * * Manages the back-office (administration panel) of the application.
 * It handles the main dashboard, global statistics, and supplier/factory order management.
 * * Access to this controller is strictly restricted to users with the 'admin' role.
 */
class AdminController extends Controller {
    private $admin_model;
    private $user_model;
    private $financial_model;
    private $stock_model;

    /**
     * Constructor
     * * Initializes the parent controller (session, translations) and loads necessary models.
     * Performs a security check: redirects non-admin users to the homepage immediately.
     */
    public function __construct() {
        parent::__construct();
        
        $this->admin_model = new AdminModel();
        $this->user_model = new UsersModel();
        $this->financial_model = new FinancialModel();
        $this->stock_model = new StockModel();
        
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            $baseUrl = $_ENV['BASE_URL'] ?? '';
            header("Location: $baseUrl/index.php");
            exit;
        }
    }

    /**
     * Dashboard (Index)
     * * Gathers Key Performance Indicators (KPIs) for the admin dashboard:
     * - Total revenue
     * - Number of orders
     * - Number of registered users
     * - Low stock alerts
     * * It also fetches the latest 5 orders for a quick overview.
     */
    public function index() {

        $stats = [
            'revenue'      => $this->financial_model->getTotalRevenue() ?? 0,
            'orders_count' => $this->financial_model->countOrders() ?? 0,
            'users_count'  => $this->user_model->countUsers() ?? 0,
            'low_stock'    => $this->stock_model->countLowStockItems(50) ?? 0
        ];

        $lastOrders = $this->financial_model->getLastOrders(5);
        if (!$lastOrders) {
            $lastOrders = [];
        }

        $this->render('admin_views', [
            'stats' => $stats,
            'lastOrders' => $lastOrders,
            'css' => 'admin_views.css' 
        ]);
    }

    public function stats() {
        $this->render('admin_stats_views', [
            'css' => 'admin_stats_views.css'
        ]);
    }

    /**
     * Supplier Orders Management
     * * Retrieves the list of orders sent to the factory (suppliers).
     * Since the database returns flat rows (one row per item), this method
     * groups items by 'id_FactoryOrder' to display structured orders in the view.
     */
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

    /**
     * Exécute les commandes de l'usine (Java)
     * Route: POST /admin/runFactory
     */
    public function runFactory() {
        // Sécurité admin
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header('Location: ' . ($_ENV['BASE_URL'] ?? '') . '/index.php');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            
            // 1. LISTE BLANCHE : On sécurise les actions autorisées
            $allowedActions = ['refill', 'order', 'proactive', 'restock'];
            
            if (in_array($action, $allowedActions)) {
                
                // 2. CHEMINS (Votre configuration validée)
                $projectRoot = realpath(__DIR__ . '/../../'); 
                $legoToolsDir = $projectRoot . '/JAVA/legotools'; // Le chemin qui fonctionne !
                $jarPath = $legoToolsDir . '/target/legotools-1.0-SNAPSHOT.jar';

                // 3. TROUVER JAVA
                $javaBin = trim(shell_exec("which java"));
                if (empty($javaBin)) $javaBin = '/usr/bin/java'; // Fallback

                // 4. CONSTRUCTION DE LA COMMANDE
                // On passe l'action dynamiquement ($action vaut 'refill', 'order', etc.)
                $cmd = "cd " . escapeshellarg($legoToolsDir) . " && " . $javaBin . " -Dfile.encoding=UTF-8 -jar " . escapeshellarg($jarPath) . " " . escapeshellarg($action) . " 2>&1";
                
                // 5. EXÉCUTION
                $output = [];
                $returnCode = 0;
                exec($cmd, $output, $returnCode);

                // 6. TRAITEMENT DU RÉSULTAT
                $resultText = implode("\n", $output);
                $_SESSION['factory_output'] = $resultText;

                // Mise à jour du solde si détecté dans la réponse
                if (preg_match('/Nouveau solde : (\d+)/', $resultText, $matches)) {
                    $_SESSION['last_factory_balance'] = $matches[1];
                } elseif (preg_match('/Solde : (\d+)/', $resultText, $matches)) {
                    $_SESSION['last_factory_balance'] = $matches[1];
                }

                // On reste sur la page fournisseur pour voir le résultat
                header('Location: ' . ($_ENV['BASE_URL'] ?? '') . '/admin/supplier');
                exit;
            }
            
            // Si action inconnue ou autre erreur
            header('Location: ' . ($_ENV['BASE_URL'] ?? '') . '/admin/index');
            exit;
        }
    }
}