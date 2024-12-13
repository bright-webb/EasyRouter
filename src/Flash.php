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

    // Get all flash messages without removing them
    public static function all() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['flash'] ?? [];
    }

    // Clear all flash messages
    public static function clear() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['flash']);
    }

    // Keep a specific flash message across requests
    public static function keep($key) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['flash'][$key])) {
            $_SESSION['persistent_flash'][$key] = $_SESSION['flash'][$key];
        }
    }
}