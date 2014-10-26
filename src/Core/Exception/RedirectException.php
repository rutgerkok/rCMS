<?php

namespace Rcms\Core\Exception;

use Exception;

/**
 * Thrown when a page redirect needs to happen.
 */
class RedirectException extends Exception {

    const TYPE_ALWAYS = "pernament";
    const TYPE_ONCE = "temporary";

    /**
     *
     * @var string The url to redirect to.
     */
    private $url;

    /**
     * @var string One of the type constants.
     */
    private $type;

    /**
     * Constructs a new {@link RedirectException}.
     * @param string $url The url to redirect to.
     * @param string $type The redirect type, either {@link #TYPE_ALWAYS} or
     * {@link #TYPE_ONCE}. Only {@link #TYPE_ALWAYS} may be cached by the
     * browser.
     */
    public function __construct($url, $type = self::TYPE_ONCE) {
        parent::__construct("Redirect", 0, null);
        $this->url = (string) $url;
        $this->type = $type === self::TYPE_ALWAYS? self::TYPE_ALWAYS : self::TYPE_ONCE;
    }

    /**
     * Gets the redirect type, either {@link #TYPE_ALWAYS} or
     * {@link #TYPE_ONCE}. Only {@link #TYPE_ALWAYS} may be cached by the
     * browser.
     * @return string The redirect type.
     */
    public function getRedirectionType() {
        return $this->type;
    }
    
    /**
     * Gets the url to redirect to.
     * @return string The url.
     */
    public function getRedirectionUrl() {
        return $this->url;
    }

}
