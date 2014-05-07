<?php

namespace Rcms\Core;

/**
 * Contains methods to check whether various things inputted by the user are
 * valid. If a function returns false, you can get the error message using
 * Validate::getLastError(..).
 */
class Validate {

    private static $lastError;
    private static $replaceInLastError = "";
    public static $MIN_PASSWORD_LENGHT = 5;

    private static function setError($code, $replaceInCode = "") {
        Validate::$lastError = $code;
        Validate::$replaceInLastError = $replaceInCode;
    }

    /**
     * Returns the localized error message of the last error.
     * @param Website $oWebsite The website object
     * @return string The localized error message
     */
    public static function getLastError(Website $oWebsite) {
        if (Validate::$replaceInLastError === "") {
            $message = $oWebsite->t("errors." . Validate::$lastError);
        } else {
            $message = $oWebsite->tReplaced("errors." . Validate::$lastError, Validate::$replaceInLastError);
        }
        Validate::$lastError = "";
        Validate::$replaceInLastError = "";
        return $message;
    }

    /**
     * Gets whether the input would be valid as an email address. As emails
     * should always be optional, an emtpy string is also valid.
     * @param string $email The email address.
     * @return boolean Whether the email is valid.
     */
    public static function email($email) {
        if ($email === '') {
            return true; // Email is optional, so allow empty email addresses
        }

        if (strLen($email) > 100) {
            Validate::setError("is_too_long_num", "100");
            return false;
        }

        if (preg_match('/^([*+!.&#$�\'\\%\/0-9a-z^_`{}=?~:-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,4})$/i', $email)) { //ingewikkeld, maar werkt
            return true;
        } else {
            Validate::setError("is_invalid");
            return false;
        }
    }

    /**
     * Checks if the passwords are equal and valid.
     * @param string $password1 The first password.
     * @param string $password2 The second password.
     * @return boolean Whether the passwords are equal and valid.
     */
    public static function password($password1, $password2) {
        $valid = true;

        if (strLen($password1) < self::$MIN_PASSWORD_LENGHT) {
            Validate::setError("is_too_short_num", Validate::$MIN_PASSWORD_LENGHT);
            $valid = false;
        }
        if ($password1 != $password2) {
            Validate::setError("is_not_equal_to_other_password");
            $valid = false;
        }
        return $valid;
    }

    public static function displayName($displayName) {
        $valid = true;

        if (strLen($displayName) < 4) {
            Validate::setError("is_too_short_num", "4");
            $valid = false;
        }
        if (strLen($displayName) > 20) {
            Validate::setError("is_too_long_num", "20");
            $valid = false;
        }
        if ($displayName != strip_tags($displayName)) {
            Validate::setError("contains_html");
            $valid = false;
        }
        return $valid;
    }

    public static function username($username) {
        $valid = true;

        $username = strToLower(trim($username));

        if (strLen($username) < 4) {
            Validate::setError("is_too_short_num", "4");
            $valid = false;
        }
        if (strLen($username) > 30) {
            Validate::setError("is_too_long_num", "30");
            $valid = false;
        }
        if (!preg_match("/^[a-z0-9_]*$/", $username)) {
            Validate::setError("contains_invalid_chars");
            $valid = false;
        }
        if (is_numeric($username)) {
            // Require letters to avoid the username 125234186528752396592318659213 matching with 1252341865287523960000000000000
            Validate::setError("contains_no_letters");
            $valid = false;
        }

        return $valid;
    }

    public static function range($number, $min, $max) {
        if (!is_numeric($number)) {
            Validate::setError("is_not_numeric");
            return false;
        }
        $number = (int) $number;
        if ($number < $min) {
            Validate::setError("is_too_low_num", $min);
            return false;
        }
        if ($number > $max) {
            Validate::setError("is_too_high_num", $max);
            return false;
        }
        return true;
    }

    /**
     * Returns whether the strings has the correct length;.
     * @param string $string The string to check.
     * @param int $min The minimum length, inclusive.
     * @param int $max The maximum length, inclusive.
     * @return boolean Whether the length of the given string is correct.
     */
    public static function stringLength($string, $min, $max) {
        if (strLen($string) < $min) {
            if ($min == 1) {
                Validate::setError("not_entered");
            } else {
                Validate::setError("is_too_short_num", $min);
            }
            return false;
        }
        if (strLen($string) > $max) {
            Validate::setError("is_too_long_num", $max);
            return false;
        }
        return true;
    }

    public static function url($linkUrl) {
        return self::stringLength($linkUrl, 1, Menus::MAX_URL_LENGTH);
    }

    public static function nameOfLink($linkText) {
        return self::stringLength($linkText, 1, Menus::MAX_LINK_TEXT_LENGTH);
    }

}
