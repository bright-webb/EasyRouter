<?php
class Helper {
    // Start session
    public static function StartSession() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function Asset($asset){
        echo $_SERVER['DOCUMENT_ROOT']."/{$asset}";
    }
}