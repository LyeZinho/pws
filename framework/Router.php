<?php

class Router {
    
    public function route() {
        $routes = require(__DIR__ . '/../routes.php');
        
        $controllerPrefix = $_GET['c'] ?? null;
        $action = $_GET['a'] ?? null;
        
        // Debug: Log das informações da requisição
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log("Router Debug - Controller: $controllerPrefix, Action: $action, Method: " . $_SERVER['REQUEST_METHOD']);
        }
        
        // Se não há parâmetros, usa a rota padrão
        if (!$controllerPrefix && !$action) {
            $route = $routes['defaultRoute'];
            $method = $route[0];
            $controller = $route[1];
            $action = $route[2];
        } else {
            // Busca a rota específica
            if (isset($routes[$controllerPrefix][$action])) {
                $route = $routes[$controllerPrefix][$action];
                $method = $route[0];
                $controller = $route[1];
                $action = $route[2];
            } else {
                // Rota não encontrada
                http_response_code(404);
                echo "Página não encontrada";
                if (defined('APP_DEBUG') && APP_DEBUG) {
                    echo "<br>Debug: Rota '$controllerPrefix/$action' não encontrada";
                }
                return;
            }
        }
        
        // Debug: Log da rota encontrada
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log("Router Debug - Route found: $method -> $controller::$action");
        }
        
        // Verifica se o método HTTP é permitido
        $allowedMethods = explode('|', $method);
        if (!in_array($_SERVER['REQUEST_METHOD'], $allowedMethods)) {
            http_response_code(405);
            echo "Método não permitido";
            if (defined('APP_DEBUG') && APP_DEBUG) {
                echo "<br>Debug: Método '" . $_SERVER['REQUEST_METHOD'] . "' não permitido. Permitidos: " . implode(', ', $allowedMethods);
            }
            return;
        }
        
        // Verifica se a classe do controller existe
        if (!class_exists($controller)) {
            throw new Exception("Controller '{$controller}' não encontrado");
        }
        
        // Instancia o controller e chama o método
        $controllerInstance = new $controller();
        
        if (!method_exists($controllerInstance, $action)) {
            throw new Exception("Método '{$action}' não encontrado no controller '{$controller}'");
        }
        
        $controllerInstance->$action();
    }
}