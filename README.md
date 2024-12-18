# EasyRouter

Easy Router is a lightweight PHP library designed to simplify routing in web applications. It provides a simple and intuitive way to define routes and handle requests, making it easy to build scalable and maintainable web applications.

## Features

- **Route Management**: Supports multiple HTTP methods (GET, POST, PUT, DELETE, PATCH) and route grouping.
- **Middleware Support**: Global and route specific middleware for flexible request handling.
- **View Rendering**: Simple and efficient templating with support for passing dynamic data.
- **Session Utilities**: Built in helper and flash messaging system for better user feedback.
- **Error Handling**: Customizable error handlers for different HTTP status codes.
- **Lightweight**: Minimal dependencies with a focus on performance.

## Installation

To use Easy Router in your project, you can install it via Composer:

```bash
composer composer require bright-webb/easy-router
```

Alternatively, download the source files and include them manually in your project.

## Getting Started

### Setup Autoloading

Ensure your project supports PSR-4 autoloading or include the files manually. If using Composer, include the autoloader:

```php
require_once __DIR__ . '/vendor/autoload.php';
```



## Components
### Router
The `Router` class handles defining and executing routes.

### View
The `View` class simplifies rendering templates

- **`render($view, $data = [])`**: Renders the specified view with optional data.

```php
View::render('about', ['company' => 'EasyRouter.']);
```

### Helper
Utility methods for simplifying common tasks, such as URL generation or string manipulation.

### Flash
The `Flash` class provides a simple interface for temporary session messages:

- **`set($key, $message)`**: Sets a flash message.
- **`get($key)`**: Retrieves and clears a flash message.

### Error Handling
Custom error handlers for HTTP status codes can be defined:

```php
$router->setErrorHandler(404, function() {
    echo 'Page not found.';
});
```

## Router Class

The `Router` class is the core of the Easy Router library. It manages route definitions, middleware, and request handling.

### Features

- Supports multiple HTTP methods (`GET`, `POST`, `PUT`, `DELETE`, `PATCH`).
- Named routes with URL generation.
- Middleware support for both global and route specific middleware.
- Route grouping with prefixes and middleware.
- Customizable error handling for HTTP status codes.
- Event hooks for pre and post route matching.



### Usage Example

#### Basic Routing
```php
use EasyRouter\Router;

$router = Router::getInstance();

// Define routes
$router->get('/', function() {
    echo 'Welcome to Easy Router!';
});

$router->post('/submit', function() {
    echo 'Form submitted!';
});

$router->run();
```

#### Route with Parameters
```php
$router->get('/user/{id}', function($id) {
    echo "User ID: $id";
});
```

#### Named Routes
```php
$router->get('/profile/{username}', function($username) {
    echo "Profile of $username";
})->name('profile');

// Generate URL
$url = $router->url('profile', ['username' => 'bright']);
echo $url; // Output: /profile/bright
```

#### Grouped Routes
```php
$router->group(['prefix' => 'admin', 'middleware' => ['auth']], function($router) {
    $router->get('/dashboard', function() {
        echo 'Admin Dashboard';
    });
});
```

You can use a method in place of the callback function if only the method name is provided.
### Example
```php
$router->get('/user/{id}',[ UserController::class, 'getUser']);
```

---

### Methods

#### Constructor and Singleton
```php
public static function getInstance(): Router
```
Creates and returns a singleton instance of the Router class.

---

#### Adding Routes
- **`get($path, $callback)`**: Defines a route for `GET` requests.
- **`post($path, $callback)`**: Defines a route for `POST` requests.
- **`put($path, $callback)`**: Defines a route for `PUT` requests.
- **`delete($path, $callback)`**: Defines a route for `DELETE` requests.
- **`patch($path, $callback)`**: Defines a route for `PATCH` requests.
- **`any($path, $callback)`**: Accepts all HTTP methods for a route.
- **`add($method, $path, $callback)`**: Adds a route for a specific HTTP method.

##### Example:
```php
$router->get('/example', function() {
    echo 'Example route';
});
```

---

#### Middleware
- **`middleware($middleware, $callback = null)`**: Attaches middleware to a route or globally.
- **`use(...$args)`**: Adds global middleware, route prefixes, or event hooks.

##### Example
```php
$router->middleware('auth', function() {
    echo 'Authentication required!';
});
```

---

#### Route Groups
- **`group(array $attributes, callable $callback)`**: Groups routes with shared attributes like prefixes or middleware.

##### Example
```php
$router->group(['prefix' => 'api', 'middleware' => ['api_auth']], function($router) {
    $router->get('/users', function() {
        echo 'API Users';
    });
});
```

---

#### Named Routes and URL Generation
- **`name($name)`**: Names a route.
- **`url($name, $parameters = [])`**: Generates a URL for a named route.

##### Example
```php
$router->get('/home', function() {
    echo 'Home Page';
})->name('home');

echo $router->url('home');
```

---

#### Error Handling
- **`error($code, callable $handler)`**: Registers a custom handler for an HTTP error code.

##### Example
```php
$router->error(404, function() {
    echo 'Page not found!';
});
```

---

#### Running the Router
- **`run()`**: Starts the router and handles the incoming request.

##### Example:
```php
$router->run();
```

---

### Advanced Features

#### Pattern Matching
- **`pattern($name, $pattern)`**: Defines custom patterns for route parameters.

##### Example:
```php
$router->pattern('id', '[0-9]+');
$router->get('/product/{id}', function($id) {
    echo "Product ID: $id";
});
```

#### Event Hooks
- **`triggerEvent($event, $params = [])`**: Attaches hooks for `before` or `after` route matching.

##### Example:
```php
$router->use('before', function() {
    echo 'Before route matching';
});
```

---

#### Middleware Redirection
- **`middlewareRedirect($redirectTo)`**: Sets a redirection path for unauthorized access.

##### Example:
```php
$router->middlewareRedirect('/login');
```



## Contributing

Contributions are welcome! Please fork the repository and submit a pull request with your changes.

## License

Easy Router is open-source software licensed under the [MIT License](LICENSE).
