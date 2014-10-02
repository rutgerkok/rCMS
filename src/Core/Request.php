<?php

namespace Rcms\Core;

/**
 * Represents a HTTP request to a page. Provides access to the Website object
 * and the request parameters.
 */
class Request {

    /** @var Website The website instance. */
    private $website;

    /** @var string[] Parameters given to the path of the request. */
    private $params;

    public function __construct(Website $website, array $params = array()) {
        $this->website = $website;
        $this->params = $params;
    }

    /**
     * Gets the website object.
     * @return Website The website object.
     */
    public function getWebsite() {
        return $this->website;
    }

    /**
     * Gets the parameter at the given position. If there is no parameter at
     * the given index, the default value is returned.
     * @param int $paramNr Position of the parameter, 0 for the first parameter.
     * @param string $defaultValue Default value.
     * @return string The parameter.
     */
    public function getParamString($paramNr, $defaultValue = "") {
        $paramNr = (int) $paramNr;
        if (!$this->hasParameter($paramNr)) {
            return $defaultValue;
        }
        return $this->params[$paramNr];
    }

    /**
     * Gets a string from the $_REQUEST array, without extra "magic quotes" 
     * (even if the server is running PHP 5.3 and has them enabled) and with a
     * default option if the $_REQUEST array doesn't contain the variable.
     * @param string $key Key in the $_REQUEST array.
     * @param string $defaultValue Default option, if value is not found.
     * @return string The value in the $_REQUEST array, or the default value.
     */
    public function getRequestString($key, $defaultValue = "") {
        return $this->website->getRequestString($key, $defaultValue);
    }

    /**
     * Gets whether a parameter exists at the given position.
     * @param int $paramNr Position of the parameter, 0 for the first parameter.
     * @return boolean True if the paramater exists, false otherwise.
     */
    public function hasParameter($paramNr) {
        if ($paramNr < 0) {
            return false;
        }
        return count($this->params) > $paramNr;
    }

    /**
     * Gets whether a request parameter with the given name exists.
     * @param string $key The key in the $_REQUEST array.
     * @return boolean True if a value exists (even if it's empty), false otherwise.
     */
    public function hasRequestValue($key) {
        return isSet($_REQUEST[$key]);
    }

    /**
     * Gets the parameter at the given position. If there is no paramater at the
     * given index, or if the parameter is not an integer, the default value is
     * returned.
     *
     * Note: negative integers are still valid integers.
     * @param int $paramNr Position of the parameter, 0 for the first parameter.
     * @param int $defaultValue Default value.
     * @return int The int.
     */
    public function getParamInt($paramNr, $defaultValue = 0) {
        $value = $this->getParamString($paramNr, (int) $defaultValue);

        // Check value type
        if (is_numeric($value)) {
            return (int) $value;
        }

        return (int) $defaultValue;
    }

    /**
     * Gets an int from the $_REQUEST array. Returns the default value if there
     * was no valid integer provided.
     *
     * Note: negative integers are still valid integers.
     * @param string $key Key in the $_REQUEST array.
     * @param int $defaultValue Default option.
     * @return int The int.
     */
    public function getRequestInt($key, $defaultValue = 0) {
        return $this->website->getRequestInt($key, $defaultValue);
    }

}
