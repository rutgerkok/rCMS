<?php

class StringHelper {

    /**
     * Hashes the string using blowfish or md5, depending on what this server
     * supports. The hash is salted.
     * @param string $string The string to hash.
     */
    public static function hash($string) {
        if (CRYPT_BLOWFISH) {
            // Blowfish, we're safe
            $salt = '$2y$11$' . substr(md5(uniqid(rand(), true)), 0, 22);
            return crypt($string, $salt);
        } elseif (CRYPT_MD5) {
            // Salted md5, let's hope for the best
            $salt = '$1$' . substr(md5(uniqid(rand(), true)), 0, 8) . '$';
            return crypt($string, $salt);
        } else {
            // There's no hope for this server anymore
            return crypt($string);
        }
    }

    /**
     * Verifies that the hash matches the input.
     * @param string $string The input.
     * @param string $hash The stored hash.
     * @return boolean True if the input matches the hash, false otherwise.
     */
    public static function verify_hash($string, $hash) {
        return crypt($string, $hash) === $hash;
    }

}

?>
