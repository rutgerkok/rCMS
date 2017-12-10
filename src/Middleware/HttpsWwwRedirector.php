<?php

namespace Rcms\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Rcms\Core\Website;

/**
 * Middleware that checks whether the site is accessed using the correct URL.
 * For example, http://example.com/ can be corrected to https://www.example.com/.
 */
final class HttpsWwwRedirector {

    /**
     * @var Website The website instance.
     */
    private $website;

    public function __construct(Website $website) {
        $this->website = $website;
    }

    public function __invoke(ServerRequestInterface $request,
            ResponseInterface $response, callable $next) {
        $siteUrl = $this->website->getUrlMain();
        $currentUrl = $request->getUri();
        $mustRedirect = false;

        // Check if www should be used or omitted
        $useWww = (strpos($siteUrl->getHost(), "www.") === 0);
        $usedWww = (strpos($currentUrl->getHost(), "www.") === 0);
        if ($useWww && !$usedWww) {
            // Add www
            $currentUrl = $currentUrl->withHost("www." . $currentUrl->getHost());
            $mustRedirect = true;
        } elseif (!$useWww && $usedWww) {
            // Remove www
            $currentUrl = $currentUrl->withHost(substr($currentUrl->getHost(), 4));
            $mustRedirect = true;
        }
        
        // Check if https should be added
        if ($siteUrl->getScheme() === "https" && $currentUrl->getScheme() === "http") {
            // Redirect to https
            $currentUrl = $currentUrl->withScheme("https");
            $mustRedirect = true;
        }

        // Redirect (or not)
        if ($mustRedirect) {
            return Responses::withPermanentRedirect($response, $currentUrl);
        }
        return $next($request, $response);
    }

}
