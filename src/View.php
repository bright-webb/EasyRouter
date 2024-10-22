<?php

namespace EasyRouter;
use EasyRouter\Flash;
use Psr\Log\LoggerInterface;

/**
 * Class View
 * @package EasyRouter
 */

class View
{
    protected static $viewsPath;
    protected static $cache = [];
    protected static $layout;
    protected static $sections = [];
    protected static $currentSection;
    protected static $data = [];
    public static $sessions = [];
    protected static $globalData = [];
    protected static $viewComposers = [];
    protected static $components = [];
    protected $middlewares = [];
    protected static $extensions = [];
    protected static $sectionStack = [];
    protected static $logger;
    protected static $errorHandler;
    protected static $conditionals = [];
    protected static $blocks = [];
    protected static $parent;
    protected static $yields = [];
    protected static $macros = [];
    protected static $debugging = false;

    // Initialize logger
    public static function setLogger(LoggerInterface $logger)
    {
        self::$logger = $logger;
    }

    // Enable / disable debuggin
    public static function debug($enable = true)
    {
        self::$debugging = $enable;
    }

    // Handle error
    protected static function throwError($error, $message = '')
    {
        if (self::$logger) {
            self::$logger->error($message . ':' . $error->getMessage(), [
                'file' => $error->getFile(),
                'line' => $error->getLine(),
                'trace' => $error->getTraceAsString()
            ]);
        }

        // If debug is enabled
        if (self::$debugging) {
            throw $error;
        }

        // Render error view if available
        try {
            return self::render('errors.500', ['error' => $error]);
        } catch (\Exception $e) {
            return 'Error: ' . $error->getMessage();
        }
    }
    public static function setViewsPath($path)
    {
        self::$viewsPath = rtrim($path, '/') . '/';
    }

    public static function asset($path)
    {
        return '/' . ltrim($path, '/');
    }

    // Inheritance
    public static function block($name, $callback = null)
    {
        if ($callback === null) {
            // capture block content
            self::$currentSection = $name;
            ob_start();
        } else {
            // Define block with immediate content
            self::$blocks[$name] = $callback();
        }
    }

    public static function endBlock()
    {
        if (!self::$currentSection) {
            throw new \Exception("No block started");
        }

        $content = ob_get_clean();
        $name = self::$currentSection;

        if (!isset(self::$blocks[$name])) {
            self::$blocks[$name] = $content;
        }

        self::$currentSection = null;
    }

    public static function yield($name)
    {
        echo self::$blocks[$name] ?? '';
    }
    public static function render($view, $data = []) {
        $viewFile = self::getViewFile($view);
        
        
        if(!file_exists($viewFile)) {
            throw new \Exception("View file not found: $viewFile");
        }

       

        // inject flash messages into view data
        $data['flash'] = [
            'success' => Flash::get('success'),
            'error' => Flash::get('error')
        ];

        // Extract the data array into variables
        extract($data);
        
        
        ob_start();
        include $viewFile;
        $content = ob_get_clean();


        // If layout is set, render it with the captured content
        if(self::$layout){
            $layout = self::$layout;
            self::$layout = null; // reset the layout
            return self::render($layout, ['content' => $content]);
        }

        
        
        echo $content;  
        
        return new self(); 
    }

    public static function layout($layout)
    {
        self::$layout = $layout;
    }

    public static function extend($layout, $data = [])
    {
        self::$layout = $layout;
        self::$data = $data;
    }

    // Get data passed to the parent view - extend
    public static function props()
    {
        return self::$data;
    }

    // Conditional methods
    public static function unless($condition, $callback)
    {
        if (!$condition) {
            $callback();
        }
    }

    public static function if($condition, $callback)
    {
        if ($condition) {
            $callback();
        }
    }

    public static function loop($items, $callback)
    {
        foreach ($items as $key => $item) {
            $callback($item, $key);
        }
    }

    // Macros
    public static function macro($name, $callback)
    {
        self::$macros[$name] = $callback;
    }

    public static function run($name, ...$args)
    {
        if (!isset(self::$macros[$name])) {
            throw new \Exception("Macro not found: $name");
        }
        return call_user_func_array(self::$macros[$name], $args);
    }


    // Reusable compnents
    public static function slot($name, $content = null)
    {
        if ($content === null) {
            self::$currentSection = "slot_{$name}";
            ob_start();
        } else {
            self::$sections["slot_{$name}"] = $content;
        }
    }

    public static function endSlot()
    {
        if (!self::$currentSection || !str_starts_with(self::$currentSection, 'slot_')) {
            throw new \Exception('No slot started');
        }

        self::$sections[self::$currentSection] = ob_get_clean();
        self::$currentSection = null;
    }


    public static function section($name, $data = [])
    {
        self::$currentSection = $name;
        ob_start();
        extract($data);
    }

    public static function endSection()
    {
        if (!self::$currentSection) {
            throw new \Exception('No section started');
        }

        self::$sections[self::$currentSection] = ob_get_clean();
        self::$currentSection = null;
    }

    public static function sectionContent($name)
    {
        echo self::$sections[$name] ?? '';
    }

    public static function setCurrentSection($section)
    {
        self::$currentSection = $section;
    }

    public static function getViewFile($view)
    {
        // Check cache first
        if (isset(self::$cache[$view])) {
            return self::$cache[$view];
        }

        // path to the view file
        $file = self::$viewsPath . str_replace('.', '/', $view) . '.php';

        // Cache the path
        self::$cache[$view] = $file;
        return $file;
    }

    public function back()
    {
        header("Location: " . $_SERVER['HTTP_REFERER']);
        return $this;
    }

    public static function redirect()
    {
        return new self();
    }

    public function to($url)
    {
        header("Location: " . $url);
        return $this;
    }

    public function with($key, $message)
    {
        $_SESSION['lu'][$key] = $message;
    }

    public static function setSession($data)
    {
        $_SESSION['fl']['data'] = $data;
        self::$sessions = $data;
    }

}