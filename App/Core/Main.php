<?php
namespace App\Core;

use App\Controllers\MainController;

class Main
{
    public function start()
    {
        // démarrage de la session si ce n'est pas déjà fait
        if (session_status() === PHP_SESSION_NONE) session_start();

        // on retire le "trailing slash" éventuel de l'url
        $uri = $_SERVER['REQUEST_URI'];

        // on vérifie que l'uri n'est pas vide et se termine par un slash
        if (!empty($uri) && $uri != '/' && $uri[-1] === '/') {
            $uri = substr($uri, 0, -1);
            http_response_code(301);
            header('Location: ' . $uri);
            exit;
        }

        // on gère les paramètres d'url (p=controleur/methode/parametres)
        $params = [];
        if (isset($_GET['p']))
            $params = explode('/', $_GET['p']);

        if (isset($params[0]) && $params[0] != "") {
            // on a au moins un paramètre
            // on récupère le nom du contrôleur à instancier
            // on met une majuscule en 1ère lettre, on ajoute le namespace complet avant
            $controllerName = '\\App\\Controllers\\' . ucfirst(array_shift($params)) . 'Controller';

            if (class_exists($controllerName)) {
                $controller = new $controllerName();

                // on récupère le 2ème paramètre d'url (la méthode)
                $action = (isset($params[0])) ? array_shift($params) : 'index';

                if (method_exists($controller, $action)) {
                    // si il reste des paramètres, on les passe à la méthode
                    (isset($params[0])) ? call_user_func_array([$controller, $action], $params) : $controller->$action();
                } else {
                    http_response_code(404);
                    echo "La page recherchée n'existe pas (Méthode introuvable)";
                }
            } else {
                http_response_code(404);
                echo "La page recherchée n'existe pas (Contrôleur introuvable)";
            }
        } else {
            // aucun paramètre, on instancie le contrôleur par défaut (imagescontroller pour ton site)
            $controller = new \App\Controllers\ImagesController;
            $controller->index();
        }
    }
}