<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\CommandeModel;
use App\Models\ImagesModel;

class CommandeController extends Controller
{
    // affiche la liste des commandes de l'utilisateur
    public function index()
    {
        // vérification de connexion
        if (!isset($_SESSION['user_id'])) {
            header("Location: /user/login");
            exit;
        }

        $commandeModel = new CommandeModel();
        // récupération des commandes via le modèle
        $commandes = $commandeModel->getCommandeByUserId($_SESSION['user_id']);

        // affichage de la vue
        $this->render('commande_views', [
            'commandes' => $commandes,
            'css' => 'commande_views.css'
        ]);
    }

    // affiche le détail d'une commande spécifique
    public function detail($id)
    {
        // vérification de connexion
        if (!isset($_SESSION['user_id'])) {
            header("Location: /user/login");
            exit;
        }

        $id = (int)$id;
        $commandeModel = new CommandeModel();
        $imagesModel = new ImagesModel();

        // récupération de la commande
        $commande = $commandeModel->getCommandeById($id);

        // si la commande n'existe pas ou n'appartient pas à l'utilisateur (sécurité sup.)
        if (!$commande) {
            header("Location: /commande");
            exit;
        }

        // on récupère l'image associée (support objet/array selon pdo)
        $id_image = is_object($commande) ? $commande->id_images : $commande['id_images'];
        $mosaic = $imagesModel->getImageById($id_image);

        // affichage de la vue détail
        $this->render('commande_detail_views', [
            'commande' => $commande,
            'mosaic' => $mosaic,
            'css' => 'commande_detail_views.css'
        ]);
    }
}