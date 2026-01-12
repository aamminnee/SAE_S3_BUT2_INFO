<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\StockModel;

class StockController extends Controller {
    
    private $stockModel;

    public function __construct() {
        parent::__construct();
        $this->stockModel = new StockModel();
    }

    public function index() {
        $model = new StockModel();

        $page = (int)($_GET['page'] ?? 1);
        $limit = 50; 
        
        // 1. Récupération des filtres
        $filterShape = $_GET['filter_shape'] ?? null;
        $filterColor = $_GET['filter_color'] ?? null;
        $filterStatus = $_GET['filter_status'] ?? 'all'; // 'all', 'low', ou 'critical'

        // 2. On passe le $filterStatus au Modèle (il faudra modifier le modèle aussi !)
        $stocks = $model->getPaginatedStock($limit, $page, $filterShape, $filterColor, $filterStatus);
        
        // 3. On compte aussi avec le filtre pour que la pagination soit juste
        $totalItems = $model->countStockItems($filterShape, $filterColor, $filterStatus);
        $totalPages = ceil($totalItems / $limit);

        $allItems = $model->getAllItemsForSearch();
        $shapes = $model->getAllShapes();
        $colors = $model->getAllColors();

        $this->render('stock_views', [
            'stocks' => $stocks,
            'allItems' => $allItems,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'shapesList' => $shapes,
            'colorsList' => $colors,
            // On peut passer le status actuel si besoin, bien que $_GET suffise dans la vue
            'currentStatus' => $filterStatus, 
            'css' => 'stock_views.css'
        ]);
    }

    public function add() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $itemId = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
            $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;

            if ($itemId > 0 && $quantity != 0) {
                $this->stockModel->updateStock($itemId, $quantity);
            }
        }
        // Redirection en gardant les filtres si possible, sinon retour simple
        header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/stock");
        exit;
    }
}