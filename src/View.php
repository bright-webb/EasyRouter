<?php

namespace EasyRouter;
use EasyRouter\Flash;

class View {
    protected static $viewsPath;
    protected static $cache = [];
    protected static $layout;
    protected static $sections = [];
    protected static $currentSection;
    protected static $data = [];
    public static $sessions = [];

    public static function setViewsPath($path) {
        self::$viewsPath = rtrim($path, '/') . '/';
    }

    public static function asset($path)
    {
        return '/'. ltrim($path, '/');
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

    public static function layout($layout) {
        self::$layout = $layout;
    }

    public static function extend($layout, $data = []) {
        self::$layout = $layout;
        self::$data = $data;
    }

    // Get data passed to the parent view - extend
    public static function props(){
        return self::$data;
    }

    public static function renderLayout() {
        extract(self::$data); 
        include self::$layout; 
    }


    public static function section($name, $data = []) {
        self::$currentSection = $name;
        ob_start();
        extract($data);
    }

    public static function endSection() {
        if(!self::$currentSection){
            throw new \Exception('No section started');
           }
    
           self::$sections[self::$currentSection] = ob_get_clean();
           self::$currentSection = null;
    }

    public static function sectionContent($name){
        echo self::$sections[$name] ?? '';
    }

    public static function setCurrentSection($section) {
        self::$currentSection = $section;
    }

    public static function getViewFile($view) {
        // Check cache first
        if(isset(self::$cache[$view])){
            return self::$cache[$view];
        }

        // path to the view file
        $file = self::$viewsPath . str_replace('.', '/', $view) . '.php';

        // Cache the path
        self::$cache[$view] = $file;
        return $file;
    }

    public function back() {
        header("Location: " . $_SERVER['HTTP_REFERER']);
        return $this;
    }

    public static function redirect() {
        return new self();
    }

    public function to($url){
        header("Location: " . $url);
        return $this;
    }

    public function with($key, $message){
        $_SESSION['lu'][$key] = $message;
    }

    public static function setSession($data){
        $_SESSION['fl']['data'] = $data;
        self::$sessions = $data;
    }
    
}