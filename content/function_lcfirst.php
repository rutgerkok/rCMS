<?php

/**
 * Converts the first character of a string to lowercase. This function is
 * included in PHP >= 5.3.0, so check with function_exists before including this
 * file.
 * 
 * @param string $string Input string.
 * @return string Output string.
 */
function lcFirst($string) {
    $first_char_lowercase = strtolower(substr($string, 0, 1));
    $other_chars = substr($string, 1); // Everything after the first char
    return $first_char_lowercase . $other_chars;
}

?>