<?php

namespace Rcms\Core;

class JsonHelper {

    /**
     * Encodes an array as JSON. Same as
     * `json_encode($arr, JSON_UNESCAPED_UNICODE)`.
     * @param mixed $data Value to encode.
     * @return string Encoded value.
     */
    public static function arrayToString($data) {
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Decodes a JSON string into an array
     * @param string $data The json string.
     */
    public static function stringToArray($data) {
        return json_decode($data, true);
    }

}
