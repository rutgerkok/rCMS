<?php

class JSONHelper {

    /**
     * Same as json_encode($arr, JSON_UNESCAPED_UNICODE), but compatible with
     * PHP 5.2. See http://nl3.php.net/manual/en/function.json-encode.php#105789
     * @param mixed $arr Value to encode.
     * @return string Encoded value.
     */
    public static function array_to_string($data) {
        //convmap since 0x80 char codes so it takes all multibyte codes (above ASCII 127). So such characters are being "hidden" from normal json_encoding
        array_walk_recursive($data, "jsonhelper_encode_item");
        return mb_decode_numericentity(json_encode($data), array(0x80, 0xffff, 0, 0xffff), 'UTF-8');
    }

    /**
     * Decodes a JSON string into an array
     * @param string $data The json string.
     */
    public static function string_to_array($data) {
        return json_decode($data, true);
    }

}

/*
 * I want to use inline functions, but those aren't available in PHP 5.2.
 * Normally functions aren't permitted in rCMS, but an exception had to be made
 * here.
 */

function jsonhelper_encode_item(&$item, $key) {
    if (is_string($item)) {
        $item = mb_encode_numericentity($item, array(0x80, 0xffff, 0, 0xffff), 'UTF-8');
    }
}

?>
