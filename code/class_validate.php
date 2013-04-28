<?php

class Validate {

    private static $last_error;
    private static $replace_in_last_error = "";
    
    public static $MIN_PASSWORD_LENGHT = 5;
    
    private static function set_error($code, $replace_in_code = "") {
        Validate::$last_error = $code;
        Validate::$replace_in_last_error = $replace_in_code;
    }

    /**
     * Returns the localized error message of the last error.
     * @param Website $oWebsite The website object
     * @return string The localized error message
     */
    public static function get_last_error(Website $oWebsite) {
        if (Validate::$replace_in_last_error === "") {
            $message = $oWebsite->t("errors." . Validate::$last_error);
        } else {
            $message = str_replace("#", Validate::$replace_in_last_error, $oWebsite->t("errors." . Validate::$last_error));
        }
        Validate::$last_error = "";
        Validate::$replace_in_last_error = "";
        return $message;
    }

    public static function email($email) {
        if ($email === '')
            return true; // Email is optional, so allow empty email addresses

        if (strlen($email) > 100) {
            Validate::set_error("is_too_long_num", "100");
            return false;
        }
        
        if (preg_match('/^([*+!.&#$�\'\\%\/0-9a-z^_`{}=?~:-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,4})$/i', $email)) { //ingewikkeld, maar werkt
            return true;
        } else {
            Validate::set_error("is_invalid");
            return false;
        }
    }

    public static function password($password1, $password2) {
        $valid = true;

        if (strlen($password1) < self::$MIN_PASSWORD_LENGHT) {
            Validate::set_error("is_too_short_num", Validate::$MIN_PASSWORD_LENGHT);
            $valid = false;
        }
        if ($password1 != $password2) {
            Validate::set_error("is_not_equal_to_other_password");
            $valid = false;
        }
        return $valid;
    }

    public static function display_name($display_name) {
        $valid = true;

        if (strlen($display_name) < 4) {
            Validate::set_error("is_too_short_num", "4");
            $valid = false;
        }
        if (strlen($display_name) > 20) {
            Validate::set_error("is_too_long_num", "20");
            $valid = false;
        }
        if ($display_name != strip_tags($display_name)) {
            Validate::set_error("contains_html");
            $valid = false;
        }
        return $valid;
    }

    public static function username($username, Website $oWebsite) {
        $valid = true;

        if (strlen($username) < 4) {
            Validate::set_error("is_too_short_num", "4");
            $valid = false;
        }
        if (strlen($username) > 30) {
            Validate::set_error("is_too_long_num", "30");
            $valid = false;
        }
        if ($username != strip_tags($username)) {
            Validate::set_error("contains_html");
            $valid = false;
        }

        if ($valid) {
            $oDB = $oWebsite->get_database();
            $username = $oDB->escape_data(htmlentities(strtolower($username)));
            if ($oDB->rows($oDB->query('SELECT gebruiker_id FROM `gebruikers` WHERE gebruiker_login = \'' . $username . '\' LIMIT 0 , 1')) > 0) {
                Validate::set_error("already_exists");
                $valid = false;
            }
        }

        return $valid;
    }
    
    public static function range($number, $min, $max) {
        if(!is_numeric($number)) {
            Validate::set_error("is_not_numeric");
            return false;
        }
        $number = (int) $number;
        if($number < $min) {
            Validate::set_error("is_too_low_num", $min);
            return false;
        }
        if($number > $max) {
            Validate::set_error("is_not_high_num", $max);
            return false;
        }
        return true;
    }
}
?>