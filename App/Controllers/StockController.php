<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\StockModel;

class StockController extends Controller
{
    // property to store the model instance
    private $stockModel;

    public function __construct()
    {
        parent::__construct();
        // instantiating the model
        $this->stockModel = new StockModel();
    }

    // main method to display the management page
    public function index() {
        $model = new StockModel();

        // 1. Récupération des paramètres URL (Filtres et Page)
        $page = $_GET['page'] ?? 1;
        $limit = 50; // Nombre d'items par page
        $filterShape = $_GET['filter_shape'] ?? null;
        $filterColor = $_GET['filter_color'] ?? null;

        // 2. Récupération des données
        $stocks = $model->getPaginatedStock($limit, $page, $filterShape, $filterColor);
        
        // 3. Calcul de la pagination
        $totalItems = $model->countStockItems($filterShape, $filterColor);
        $totalPages = ceil($totalItems / $limit);

        // 4. Données pour les menus déroulants et la recherche
        $allItems = $model->getAllItemsForSearch();
        $shapes = $model->getAllShapes(); // Pour le filtre
        $colors = $model->getAllColors(); // Pour le filtre

        // 5. Envoi à la vue
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

    // method to handle stock addition
    public function add()
    {
        // checking the post method
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // retrieving and sanitizing inputs
            $itemId = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
            $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;

            // simple validation
            if ($itemId > 0 && $quantity != 0) {
                // calling the model to update
                $this->stockModel->updateStock($itemId, $quantity);
            }
        }

        // redirecting to the stock page
        // // fix: use base url for redirection
        header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/stock");
        exit;
    }
}