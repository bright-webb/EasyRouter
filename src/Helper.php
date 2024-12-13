<?php
namespace EasyRouter;
class Helper {
    // Start session
   public static function Session($key) {
    if (!isset($_SESSION)) {
        @session_start();
    }
    return $_SESSION[$key] ?? null;
}


   public static function Asset($asset) {
    return $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/' . ltrim($asset, '/');
}

    private static function ensureSessionStarted() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

public static function env($key, $default = null) {
    $value = getenv($key);
    return $value !== false ? $value : $default;
}

public static function url($path) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . '/' . ltrim($path, '/');
}

}
