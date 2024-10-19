<?php
namespace EasyRouter;
class Flash {
    public static function set($key, $message) {
        if(session_id()){
            session_start(); // strt session if not already started
        }
        $_SESSION['flash'][$key] = $message;
    }

    // Get a flash message and remove it from the ssion
    public static function get($key) {
        if(session_id()){
            session_start(); // start session if not already started
        }
        if(isset($_SESSION['flash'][$key])){
            $message = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $message;
        }

        return null;
    }

    // Check if a flash message exists
    public static function has($key) {
        if(session_id()){
            session_start(); // start session if not already started
        }
        return isset($_SESSION['flash'][$key]);
    }
}