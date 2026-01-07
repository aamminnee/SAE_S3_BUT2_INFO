<?php
namespace App\Core;

use App\Controllers\ImagesController;

class Main {
    public function start() {
        // démarrage de la session uniquement si elle n'est pas déjà active
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // récupération des paramètres depuis l'url
        // on sépare les paramètres de l'url
        $uri = $_SERVER['REQUEST_URI'];
        
        // on retire le slash de fin s'il n'est pas à la racine
        if (!empty($uri) && $uri[-1] === '/' && $uri != '/') {
            $uri = substr($uri, 0, -1);
            
            // code de redirection
            http_response_code(301);
            
            // redirection
            header('Location: ' . $uri);
            exit;
        }

        // gestion des paramètres d'url
        $params = [];
        if (isset($_GET['p'])) {
            $params = explode('/', $_GET['p']);
        }

        // on vérifie si au moins un paramètre existe
        if (isset($params[0]) && $params[0] != '') {
            // on récupère le nom du contrôleur à instancier
            // on met une majuscule en 1ère lettre
            $controller = '\\App\\Controllers\\' . ucfirst(array_shift($params)) . 'Controller';

            // on instancie le contrôleur
            $controller = new $controller();

            // on récupère le 2ème paramètre d'url
            $action = (isset($params[0])) ? array_shift($params) : 'index';

            if (method_exists($controller, $action)) {
                // si la méthode existe dans le contrôleur
                // on appelle la méthode
                (isset($params[0])) ? call_user_func_array([$controller, $action], $params) : $controller->$action();
            } else {
                // on envoie le code réponse 404
                http_response_code(404);
                echo "La page recherchée n'existe pas";
            }
        } else {
            // ici on instancie le contrôleur par défaut
            // changer 'home' ou 'main' en 'images' pour en faire la page d'accueil
            $controller = new \App\Controllers\ImagesController();
            $controller->index();
        }
    }
}