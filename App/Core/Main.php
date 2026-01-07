<?php
namespace App\Core;

use App\Controllers\ImagesController;

class Main {
    public function start() {
        // start session
        session_start();

        // retrieve parameters from url
        // we separate the parameters from the url
        $uri = $_SERVER['REQUEST_URI'];
        
        // we remove the trailing slash if it is not the root
        if (!empty($uri) && $uri[-1] === '/' && $uri != '/') {
            $uri = substr($uri, 0, -1);
            
            // redirection code
            http_response_code(301);
            
            // redirection
            header('Location: ' . $uri);
            exit;
        }

        // management of url parameters
        $params = [];
        if (isset($_GET['p'])) {
            $params = explode('/', $_GET['p']);
        }

        // check if at least one parameter exists
        if (isset($params[0]) && $params[0] != '') {
            // recover the name of the controller to instantiate
            // on met une majuscule en 1ère lettre
            $controller = '\\App\\Controllers\\' . ucfirst(array_shift($params)) . 'Controller';

            // we instantiate the controller
            $controller = new $controller();

            // we recover the 2nd url parameter
            $action = (isset($params[0])) ? array_shift($params) : 'index';

            if (method_exists($controller, $action)) {
                // if the method exists in the controller
                // we call the method
                (isset($params[0])) ? call_user_func_array([$controller, $action], $params) : $controller->$action();
            } else {
                // we send the 404 response code
                http_response_code(404);
                echo "La page recherchée n'existe pas";
            }
        } else {
            // here we instantiate the default controller
            // change 'Home' or 'Main' to 'Images' to make it the landing page
            $controller = new \App\Controllers\ImagesController();
            $controller->index();
        }
    }
}