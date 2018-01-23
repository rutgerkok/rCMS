<?php

namespace Rcms\Core;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Simplified variant of {@link ServerRequestInterface}.
 */
class Request {

    /**
     * @var ServerRequestInterface The PSR request interface.
     */
    private $serverRequest;

    /** @var string[] The parts that exist in the path.. */
    private $pathParts = [];

    public function __construct(ServerRequestInterface $serverRequest) {
        $this->serverRequest = $serverRequest;

        // Parse the URL path
        $serverParams = $serverRequest->getServerParams();
        if (isSet($serverParams["PATH_INFO"])) {
            $path = $serverRequest->getServerParams()["PATH_INFO"];
            $this->pathParts = explode('/', trim($path, '/'));
        }

        // Support old index.php?page=foo&id=3
        if (empty($this->pathParts) && $this->hasRequestValue("p")) {

            $pageName = $this->getRequestString("p");
            
            if ($this->hasRequestValue("id")) {
                $pageId = $this->getRequestInt("id");
                $this->pathParts = [$pageName, (string) $pageId];
            } else {
                $this->pathParts = [$pageName];
            }
        }
    }

    /**
     * Checks if the current user has the given rank.
     * @param int $rank The rank.
     * @return bool True if the user has this rank, false otherwise.
     */
    public function hasRank($rank) {
        if ($rank == Ranks::LOGGED_OUT) {
            return true; // Lowest rank, so always true
        }
 
        $user = $this->getCurrentUser();
        if ($user === null) {
            return false;
        }
        return $user->hasRank($rank);
    }

    /**
     * Gets the currently logged in user.
     * @return User|null The user, or null if logged out.
     */
    public function getCurrentUser() {
        return $this->serverRequest->getAttribute("user", null);
    }

    /**
     * Gets the name of the requested page. The name is an empty string if no
     * page name was specified in the request.
     * @return string The name.
     */
    public function getPageName() {
        if (empty($this->pathParts)) {
            return "";
        }
        return $this->pathParts[0];
    }

    /**
     * Gets the parameter at the given position. If there is no parameter at
     * the given index, the default value is returned.
     * @param int $paramNr Position of the parameter, 0 for the first parameter.
     * @param string $defaultValue Default value.
     * @return string The parameter.
     */
    public function getParamString($paramNr, $defaultValue = "") {
        if (!$this->hasParameter($paramNr)) {
            return $defaultValue;
        }
        return $this->pathParts[$paramNr + 1];
    }

    /**
     * Gets a string from either the query parameters or from the request body.
     * @param string $key The key.
     * @param string $defaultValue Default option, if value is not found.
     * @return string The value, or the default value if not found.
     */
    public function getRequestString($key, $defaultValue = "") {
        $postData = $this->serverRequest->getParsedBody();
        if (isSet($postData[$key]) && is_scalar($postData[$key])) {
            return (string) $postData[$key];
        }
        $getData = $this->serverRequest->getQueryParams();
        if (isSet($getData[$key]) && is_scalar($getData[$key])) {
            return (string) $getData[$key];
        }
        return $defaultValue;
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
        return count($this->pathParts) - 1 > $paramNr;
    }

    /**
     * Gets whether a parameter exists in either the query parameters or the
     * request body.
     * @param string $key The key.
     * @return boolean True if a value exists (even if it's empty), false otherwise.
     */
    public function hasRequestValue($key) {
        return isSet($this->serverRequest->getQueryParams()[$key]) || isSet($this->serverRequest->getParsedBody()[$key]);
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
        $stringValue = $this->getParamString($paramNr, "");
        if (is_numeric($stringValue)) {
            return (int) $stringValue;
        }
        return $defaultValue;
    }

    /**
     * Gets an int from either the query parameters or from the request body.
     *
     * Note: negative integers are still valid integers.
     * @param string $key The key.
     * @param int $defaultValue Default option.
     * @return int The int, or the default option if value exists for the given key.
     */
    public function getRequestInt($key, $defaultValue = 0) {
        $stringValue = $this->getRequestString($key, "");
        if (is_numeric($stringValue)) {
            return (int) $stringValue;
        }
        return $defaultValue;
    }
    
    /**
     * Gets this request as a PSR ServerRequestInterface.
     * @return ServerRequestInterface The PSR-request.
     */
    public function toPsr() {
        return $this->serverRequest;
    }

}
