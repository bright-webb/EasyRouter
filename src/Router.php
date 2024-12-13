<?php
namespace EasyRouter;
use EasyRouter\Flash;

class Router {
    private $routes = [];
    private $globalMiddleware = [];
    private $middlewares = [];
    private $routePrefix = '';
    private $eventHooks = [];
    private $routeGroups = [];
    private $currentGroup = null;
    private $namedRoutes = [];
    private $patterns = [];
    private $errorHandlers = [];
    private $baseNamespace = '';
    private static $instance = null;
    protected $middlewareRedirect = "/";


     public static function getInstance() {
         if (self::$instance === null) {
             self::$instance = new self();
         }
         return self::$instance;
     }

    public function get($path, $callback) {
        $this->addRoute('GET', $path, $callback);

        return $this;
    }

    public function post($path, $callback) {
        $this->addRoute('POST', $path, $callback);

        return $this;
    }

    public function put($path, $callback) {
        $this->addRoute('PUT', $path, $callback);
    }

    public function delete($path, $callback) {
        $this->addRoute('DELETE', $path, $callback);

        return $this;
    }

    public function patch($path, $callback) {
        $this->addRoute('PATCH', $path, $callback);

        return $this;
    }

    public function any($path, $callback) {
        foreach(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'] as $method) {
            $this->addRoute($method, $path, $callback);
        }
        return $this;
    }

    public function add($route, $path, $callback) {
        $this->addRoute($route, $path, $callback);

        return $this;
    }

    public function group(array $attributes, callable $callback) {
        $previousGroup = $this->currentGroup;
        
        $this->currentGroup = [
            'prefix' => $attributes['prefix'] ?? '',
            'middleware' => $attributes['middleware'] ?? [],
            'namespace' => $attributes['namespace'] ?? '',
        ];
        
        $callback($this);
        
        $this->currentGroup = $previousGroup;
        return $this;
    }

    // Named routes
    public function name($name) {
        $lastRoute = end($this->routes);
        if ($lastRoute) {
            $this->namedRoutes[$name] = $lastRoute['path'];
        }
        return $this;
    }

    // URL generation for named routes
    public function url($name, $parameters = []) {
        if (!isset($this->namedRoutes[$name])) {
            throw new \Exception("Route {$name} not found.");
        }

        $uri = $this->namedRoutes[$name];
        foreach ($parameters as $key => $value) {
            $uri = str_replace("{{$key}}", $value, $uri);
        }
        return $uri;
    }

    public function pattern($name, $pattern) {
        $this->patterns[$name] = $pattern;
        return $this;
    }



    // middleware
    public function middleware($middleware, $callback = null) {
        if (is_array($middleware)) {
            foreach ($middleware as $m) {
                $this->globalMiddleware[] = $m;
            }
            return $this;
        }

        if ($callback === null) {
            $lastRoute = end($this->routes);
            if ($lastRoute) {
                $this->routes[key($this->routes)]['middleware'][] = $middleware;
            }
            return $this;
        }

        
        if(isset($_SESSION[$middleware])) {
            return $_SESSION[$middleware];
        } else {
            if($callback === null) {
                Flash::set('error', 'Session expired');
            } else {
                call_user_func($callback);
            }
        }
        
        return $this;
    }

    public function use(...$args) {
        if (count($args) === 1) {
            $arg = $args[0];

            // Handle middleware 
            if (is_callable($arg)) {
                $this->middlewares[] = $arg;
                return $this;
            }

            // Handle route prefix 
            if (is_string($arg)) {
                $this->routePrefix = '/' . trim($arg, '/');
                return $this;
            }

            if (count($args) === 2 && is_string($args[0]) && is_callable($args[1])) {
                $event = $args[0];
                $callback = $args[1];
    
                if (!isset($this->eventHooks[$event])) {
                    $this->eventHooks[$event] = [];
                }
                $this->eventHooks[$event][] = $callback;
                return $this;
            }
        }

       

        throw new \InvalidArgumentException("Invalid arguments.");
    }

      // Middleware dispatch (globally and route-specific)
      private function handleMiddleware($request) {
        foreach ($this->middlewares as $middleware) {
            $middleware($request);
        }
    }

      // Event trigger 
      private function triggerEvent($event, $params = []) {
        if (isset($this->eventHooks[$event])) {
            foreach ($this->eventHooks[$event] as $callback) {
                call_user_func_array($callback, $params);
            }
        }
    }


    public function middlewareRedirect($redirectTo){
        $this->middlewareRedirect = $redirectTo;
    }

    private function addRoute($method, $path, $callback, $priority = 0) {
        $this->routes[] = [
            'method' => $method,
            'path' => $this->formatPath($path),
            'callback' => $callback,
            'priority' => $priority,
        ];
        usort($this->routes, fn($a, $b) => $b['priority'] <=> $a['priority']);
    }

    public function subdomain($subdomain, $callback) {
        if ($_SERVER['HTTP_HOST'] === $subdomain) {
            $callback($this);
        }
        return $this;
    }
    

    private function formatPath($path) {
        return '/' . trim($path, '/');
    }

    public function run() {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $requestMethod = strtoupper($requestMethod);

        // Handle global middleware
        $this->handleMiddleware($requestUri);
        $this->triggerEvent('before', [$requestMethod, $requestUri]);

        if (!$this->isMethodSupported($requestMethod)) {
            $this->sendResponse(405, "405 Method Not Allowed");
            return;
        }

        
        foreach ($this->routes as $route) {
            $params = [];
            if ($route['method'] === $requestMethod && $this->matchRoute($route['path'], $requestUri, $params)) {
                // Execute global middleware
                foreach ($this->globalMiddleware as $middleware) {
                    $response = $this->executeMiddleware($middleware);
                    if ($response !== null) {
                        return $response;
                    }
                }

                if (isset($route['middleware'])) {
                    foreach ($route['middleware'] as $middleware) {
                        $response = $this->executeMiddleware($middleware);
                        if ($response !== null) {
                            return $response;
                        }
                    }
                }
                
                return $this->executeCallback($route['callback'], $params);
            }
            
        }

        // After route matching hook
        $this->triggerEvent('after', [$requestMethod, $requestUri]);
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

    private function executeMiddleware($middleware) {
        if (is_string($middleware)) {
            $middleware = new $middleware();
        }
        
        if (method_exists($middleware, 'handle')) {
            return $middleware->handle();
        }
        
        return null;
    }

    public function cacheRoutes($filePath) {
        file_put_contents($filePath, serialize($this->routes));
    }
    
    public function loadCachedRoutes($filePath) {
        if (file_exists($filePath)) {
            $this->routes = unserialize(file_get_contents($filePath));
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

    public function error($code, callable $handler) {
        $this->errorHandlers[$code] = $handler;
        return $this;
    }

    private function handleError($code, $message = '') {
        if (isset($this->errorHandlers[$code])) {
            call_user_func($this->errorHandlers[$code], $message);
        } else {
            $this->sendResponse($code, $message);
        }
    }
}
