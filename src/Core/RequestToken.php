<?php

namespace Rcms\Core;

use RuntimeException;

/**
 * Each request that alters data on the site should be protected with a
 * `RequestToken`. In this way evil links on other sites cannot damage this site
 * if a logged-in user of this site clicks on such a link.
 *
 * First of all, generate a token using `generateNew`. Save it to the session
 * using `saveToSession` and include it in a form with the field name set to
 * `RequestToken::FIELD_NAME`. The form validator must retrieve both values and
 * check them for equality using the `matches` method.
 */
class RequestToken {

    const SESSION_NAME = "request_token";
    const FIELD_NAME = "request_token";

    /**
     * Creates a new token.
     * @return RequestToken The token.
     */
    public static final function generateNew() {
        return new RequestToken(HashHelper::randomString());
    }

    /**
     * Gets the request token stored in the session. If nothing was stored in
     * the session a token of an empty string is returned.
     * @return RequestToken The token.
     */
    public static final function fromSession() {
        if (isSet($_SESSION[self::SESSION_NAME])) {
            return new RequestToken($_SESSION[self::SESSION_NAME]);
        }
        return new RequestToken("");
    }

    /**
     * Creates the request token passed to this page. If nothing was stored in
     * the request a token of an empty string is returned.
     * @param Request $request The request.
     * @return RequestToken The token.
     */
    public static final function fromRequest(Request $request) {
        return new RequestToken($request->getRequestString(self::FIELD_NAME, ""));
    }

    /**
     * Creates a request token from a string. If the string is empty, the
     * request token cannot be saved to a session.
     * @param string $string The string.
     * @return RequestToken The token.
     */
    public static final function fromString($string) {
        return new RequestToken((string) $string);
    }

    /** @var string The token string. */
    private $tokenString;

    private function __construct($tokenString) {
        $this->tokenString = $tokenString;
    }

    /**
     * Gets the token as a string.
     * @return string The string.
     */
    public function getTokenString() {
        return $this->tokenString;
    }

    /**
     * Checks if this token matches the other token. If both tokens are empty
     * it is not considered a match, and this method returns false.
     * @param RequestToken $other The other token.
     * @return boolean True if they match, false otherwise.
     */
    public function matches(RequestToken $other) {
        if (empty($this->tokenString)) {
            return false;
        }
        return $this->tokenString === $other->tokenString;
    }

    /**
     * Saves the request token to the session. Only newly generated tokens
     * (see `generateNew`) should be saved to a session.
     * @throws RuntimeException If the token is empty.
     */
    public function saveToSession() {
        if (empty($this->tokenString)) {
            throw new RuntimeException("Empty token string cannot be saved to session");
        }
        $_SESSION[self::SESSION_NAME] = $this->tokenString;
    }

}
