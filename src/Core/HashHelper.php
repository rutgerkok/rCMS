<?php

namespace Rcms\Core;

use InvalidArgumentException;

class HashHelper {

    /**
     * As noted in the PHP documentation, values shorter than this number of
     * characters indicate a hashing failure.
     */
    const CRYPT_RETURN_VALUE_MIN_LENGHT = 13;
    
    /**
     * Creates a random string consisting of only hexadecimal chars of the
     * specified length. The random number generator does not have to be
     * cryptographically secure and can be time-based.
     * @param int $length Length of the desired string, in chars.
     * @return string The random string.
     * @throws InvalidArgumentException If the length is smaller than 0 or larger than 32.
     */
    public static function randomString($length = 32) {
        $lengthNumber = (int) $length;
        if ($lengthNumber <= 0 || $lengthNumber > 32) {
            throw new InvalidArgumentException("Invalid string length: $lengthNumber");
        }
        return substr(md5(uniqid(rand(), true)), 0, $lengthNumber);
    }

    /**
     * Hashes the string using blowfish or md5, depending on what this server
     * supports. The hash is salted.
     * @param string $string The string to hash.
     */
    public static function hash($string) {
        if (CRYPT_BLOWFISH) {
            // Blowfish
            if (version_compare(PHP_VERSION, "5.3.7", '>')) {
                $salt = '$2y$11$' . self::randomString(22);
            } else {
                $salt = '$2a$11$' . self::randomString(22);
            }
            $hashed = crypt($string, $salt);
            if (strLen($hashed) >= self::CRYPT_RETURN_VALUE_MIN_LENGHT) {
                return $hashed;
            }
        }
        if (CRYPT_MD5) {
            // Salted md5
            $salt = '$1$' . static::randomString(8) . '$';
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
