<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\StockModel;

/**
 * StockController
 * * Manages the inventory of LEGO parts (Admin only).
 * * Provides functionalities to view, filter, and update stock quantities.
 */
class StockController extends Controller {
    
    private $stockModel;

    public function __construct() {
        parent::__construct();
        $this->stockModel = new StockModel();
    }

    public function index() {
        $model = new StockModel();

        $page = $_GET['page'] ?? 1;
        $limit = 50; // Nombre d'items par page
        $filterShape = $_GET['filter_shape'] ?? null;
        $filterColor = $_GET['filter_color'] ?? null;

        $stocks = $model->getPaginatedStock($limit, $page, $filterShape, $filterColor);
        
        $totalItems = $model->countStockItems($filterShape, $filterColor);
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

        header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/stock");
        exit;
    }
}