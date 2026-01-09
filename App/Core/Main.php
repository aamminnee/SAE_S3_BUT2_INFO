<?php
namespace App\Core;

use App\Controllers\ImagesController;

/**
 * Main Router Class
 * * Acts as the entry point of the application (Front Controller).
 * * Parses the URL (routing), instantiates the appropriate Controller,
 * * and calls the requested method with parameters.
 */
class Main {
    public function start() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $params = [];
        if (isset($_GET['p']) && !empty($_GET['p'])) {
            $params = explode('/', $_GET['p']);
        }

        if (!empty($params) && isset($params[0]) && $params[0] != '') {
            $controllerName = '\\App\\Controllers\\' . ucfirst(array_shift($params)) . 'Controller';

            if (class_exists($controllerName)) {
                $controller = new $controllerName();
                $action = (isset($params[0])) ? array_shift($params) : 'index';

                if (method_exists($controller, $action)) {
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
            $controller = new \App\Controllers\ImagesController();
            $controller->index();
        }
    }
}