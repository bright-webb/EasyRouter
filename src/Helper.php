<?php
namespace EasyRouter;
class Helper {
    // Start session
    public static function StartSession() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

   public static function Asset($asset) {
    return $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/' . ltrim($asset, '/');
}


    public static function Session($user){
        if(isset($_SESSION[$user])){
            return $_SESSION[$user];
        } else {
            return false;
        }
    }
}
