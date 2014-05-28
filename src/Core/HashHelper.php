<?php

namespace Rcms\Core;

class HashHelper {

    /**
     * As noted in the PHP documentation, values shorter than this number of
     * characters indicate a hashing failure.
     */
    const CRYPT_RETURN_VALUE_MIN_LENGHT = 13;

    /**
     * Hashes the string using blowfish or md5, depending on what this server
     * supports. The hash is salted.
     * @param string $string The string to hash.
     */
    public static function hash($string) {
        if (CRYPT_BLOWFISH) {
            // Blowfish
            if (version_compare(PHP_VERSION, "5.3.7", '>')) {
                $salt = '$2y$11$' . substr(md5(uniqid(rand(), true)), 0, 22);
            } else {
                $salt = '$2a$11$' . substr(md5(uniqid(rand(), true)), 0, 22);
            }
            $hashed = crypt($string, $salt);
            if (strLen($hashed) >= self::CRYPT_RETURN_VALUE_MIN_LENGHT) {
                return $hashed;
            }
        }
        if (CRYPT_MD5) {
            // Salted md5
            $salt = '$1$' . substr(md5(uniqid(rand(), true)), 0, 8) . '$';
            $hashed = crypt($string, $salt);
            if (strLen($hashed) >= self::CRYPT_RETURN_VALUE_MIN_LENGHT) {
                return $hashed;
            }
        }

        // Try the default algorithm
        $hashed = crypt($string);
        if (stLen($hashed) >= self::CRYPT_RETURN_VALUE_MIN_LENGHT) {
            return $hashed;
        }

        // Failure
        throw new Exception("Hashing of string failed");
    }

    /**
     * Verifies that the hash matches the input.
     * @param string $string The input.
     * @param string $hash The stored hash.
     * @return boolean True if the input matches the hash, false otherwise.
     */
    public static function verifyHash($string, $hash) {
        return crypt($string, $hash) === $hash;
    }

}

?>
