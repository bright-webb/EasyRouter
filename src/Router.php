<?php
namespace EasyRouter;

class Router {
    private $routes = [];

    public function get($path, $callback) {
        $this->addRoute('GET', $path, $callback);
    }

    public function post($path, $callback) {
        $this->addRoute('POST', $path, $callback);
    }

    public function put($path, $callback) {
        $this->addRoute('PUT', $path, $callback);
    }

    public function delete($path, $callback) {
        $this->addRoute('DELETE', $path, $callback);
    }

    public function patch($path, $callback) {
        $this->addRoute('PATCH', $path, $callback);
    }

    private function addRoute($method, $path, $callback) {
        $this->routes[] = [
            'method' => $method,
            'path' => $this->formatPath($path),
            'callback' => $callback
        ];
    }

    private function formatPath($path) {
        return '/' . trim($path, '/');
    }

    public function dispatch() {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $requestMethod = strtoupper($requestMethod);

        if (!$this->isMethodSupported($requestMethod)) {
            $this->sendResponse(405, "405 Method Not Allowed");
            return;
        }

        foreach ($this->routes as $route) {
            $params = [];
            if ($route['method'] === $requestMethod && $this->matchRoute($route['path'], $requestUri, $params)) {
                return $this->executeCallback($route['callback'], $params);
            }
        }

        $this->sendResponse(404, "404 Not Found");
    }

    private function matchRoute($routePath, $requestUri, &$params) {
        $routeParts = explode('/', trim($routePath, '/'));
        $uriParts = explode('/', trim($requestUri, '/'));

        if (count($routeParts) !== count($uriParts)) {
            return false;
        }

        $params = [];
        foreach ($routeParts as $index => $part) {
            if (strpos($part, '{') === 0 && strpos($part, '}') === strlen($part) - 1) {
                $paramName = trim($part, '{}');
                $params[$paramName] = $uriParts[$index];
            } elseif ($part !== $uriParts[$index]) {
                return false;
            }
        }
        return true;
    }

    private function executeCallback($callback, $params) {
        if (is_array($callback) && count($callback) == 2) {
            $controllerName = $callback[0];
            $methodName = $callback[1];

            if (class_exists($controllerName) && method_exists($controllerName, $methodName)) {
                $instance = new $controllerName();
                return call_user_func_array([$instance, $methodName], $params);
            } else {
                $this->sendResponse(500, "Internal Server Error: Method not found");
            }
        } elseif (is_callable($callback)) {
            return call_user_func_array($callback, $params);
        } else {
            $this->sendResponse(500, "Internal Server Error: Invalid handler");
        }
    }

    private function isMethodSupported($method) {
        $supportedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];
        return in_array($method, $supportedMethods);
    }

    private function sendResponse($statusCode, $message) {
        http_response_code($statusCode);
        echo $message;
    }
}