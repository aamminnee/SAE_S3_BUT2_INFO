<?php
namespace App\Core;

use App\Controllers\ImagesController; // ne pas oublier l'import

class Main
{
    public function start()
    {
        // démarrage de la session si elle n'est pas déjà active
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // on retire le "trailing slash" éventuel de l'url
        $uri = $_SERVER['REQUEST_URI'];

        // on vérifie que uri n'est pas vide et se termine par un slash
        if (!empty($uri) && $uri != '/' && $uri[-1] === '/') {
            // on enlève le slash
            $uri = substr($uri, 0, -1);

            // on envoie un code de redirection permanente
            http_response_code(301);

            // on redirige vers l'url sans /
            header('Location: ' . $uri);
            exit;
        }

        // on gère les paramètres d'url (p=controleur/methode/parametres)
        $params = [];
        if (isset($_GET['p']) && !empty($_GET['p'])) {
            $params = explode('/', $_GET['p']);
        }

        // cas où on a un paramètre dans l'url (ex: index.php?p=user/login)
        if (!empty($params) && isset($params[0]) && $params[0] != '') {
            // on récupère le nom du contrôleur à instancier
            $controllerName = '\\App\\Controllers\\' . ucfirst(array_shift($params)) . 'Controller';

            // on instancie le contrôleur s'il existe
            if (class_exists($controllerName)) {
                $controller = new $controllerName();
                $action = (isset($params[0])) ? array_shift($params) : 'index';

                if (method_exists($controller, $action)) {
                    // on appelle la méthode avec les paramètres restants
                    (isset($params[0])) ? call_user_func_array([$controller, $action], $params) : $controller->$action();
                } else {
                    http_response_code(404);
                    echo "La page recherchée n'existe pas (méthode introuvable).";
                }
            } else {
                http_response_code(404);
                echo "La page recherchée n'existe pas (contrôleur introuvable).";
            }
        } else {
            // --- modification demandée ---
            // si aucun paramètre n'est passé (racine du site), on charge la page des images
            // cela remplace la logique précédente qui cherchait maincontroller
            $controller = new \App\Controllers\ImagesController();
            $controller->index();
        }
    }
}