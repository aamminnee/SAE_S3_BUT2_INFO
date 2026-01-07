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
    public function index()
    {
        // checking if the user is admin
        // // fix: check correct session keys set in usercontroller
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            // // fix: redirect to the correct login route with base url
            header("Location: " . ($_ENV['BASE_URL'] ?? '') . "/user/login");
            exit;
        }

        // fetching data for the view
        $stockData = $this->stockModel->getAllStockItems();
        $itemList = $this->stockModel->getItemList();

        // sending data to the view
        $this->render('stock_views', [
            'stocks' => $stockData,
            'items' => $itemList
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