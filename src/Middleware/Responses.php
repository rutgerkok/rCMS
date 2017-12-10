<?php

namespace Rcms\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Rcms\Core\NotFoundException;
use Rcms\Core\Website;
use Rcms\Core\Request;
use Rcms\Page\Error404Page;
use Rcms\Page\Page;
use Rcms\Theme\PageRenderer;

/**
 * Description of PageToResponse
 */
final class Responses {
    
    public static function getPageResponse(Website $website,  Request $request, Page $page, ResponseInterface $response) {
       
        try {
            $page->init($website, $request);
        } catch (NotFoundException $e) {
            $page = new Error404Page();
            $page->init($website, $request);
        }

        $pageRenderer = new PageRenderer($website, $request, $page);
        $body = $response->getBody();
        $pageRenderer->render($body);

        $body->rewind();

        return $page->modifyResponse($response);
    }
    
    /**
     * Modifies the response so that it redirects to the given URL.
     * @param ResponseInterface $response The response
     * @param UriInterface $url The URL to redirect to.
     * @return ResponseInterface The modified response.
     */
    public static function withTemporaryRedirect(ResponseInterface $response, UriInterface $url) {
        return $response->withStatus(302)->withAddedHeader("Location", (string) $url);
    }

    /**
     * Modifies the response so that it redirects to the given URL. Browsers are
     * allowed to cache this redirect.
     * @param ResponseInterface $response The response
     * @param UriInterface $url The URL to redirect to.
     * @return ResponseInterface The modified response.
     */
    public static function withPermanentRedirect(ResponseInterface $response, UriInterface $url) {
        return $response->withStatus(301)->withAddedHeader("Location", (string) $url);
    }
}
