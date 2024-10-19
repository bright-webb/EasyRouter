<?php

namespace EasyRouter;

class View  {
    protected static $viewsPath;
    protected static $cache = [];
    protected static $layout;
    protected static $sections = [];
    protected static $currentSection;

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
        
        return ''; 
    }

    public static function setLayout($layout) {
        self::$layout = $layout;
    }

    public static function section($name) {
        self::$currentSection = $name;
        ob_start();
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
}