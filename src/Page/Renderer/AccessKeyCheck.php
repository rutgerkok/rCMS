<?php

namespace Rcms\Page\Renderer;

use DateInterval;
use DateTimeImmutable;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Rcms\Core\Config;
use Rcms\Core\Request;
use Rcms\Core\Website;
use Rcms\Page\ErrorAccessCodeRequiredPage;

/**
 * Middleware that checks whether the site key is entered. If not, an error page
 * is shown with a form to enter the key. If yes, the key is saved to a cookie.
 */
final class AccessKeyCheck {

    /**
     * @var Website The website instance.
     */
    private $website;

    public function __construct(Website $website) {
        $this->website = $website;
    }

    public function __invoke(ServerRequestInterface $request,
            ResponseInterface $response, callable $next = null) {
        $enteredCorrectKey = $this->checkSiteAccess($request);
        if (!$enteredCorrectKey) {
            // Show error page
            $page = new ErrorAccessCodeRequiredPage();
            return Responses::getPageResponse($this->website, new Request($request), $page, $response);
        }

        // Render page, add access key cookie
        $updatedResponse = $next ? $next($request, $response) : $response;
        return $this->withSiteAccessCookie($updatedResponse);
    }

    /**
     * Checks if the user has access to the site. This is the case if the site
     * is public or if the user has entered the access key.
     * @param ServerRequestInterface $serverRequest The request.
     * @return boolean True if the user has access, false otherwise.
     */
    private function checkSiteAccess(ServerRequestInterface $serverRequest) {
        $correctKey = $this->website->getConfig()->get(Config::OPTION_ACCESS_CODE);
        if (empty($correctKey)) {
            // The website doesn't use an access key
            return true;
        }

        // Check for key in cookie
        $cookies = $serverRequest->getCookieParams();
        if (isSet($cookies["key"]) && $cookies["key"] === $correctKey) {
            return true;
        }

        // Newly entered key
        $postData = $serverRequest->getParsedBody();
        if (isSet($postData["key"])) {
            $text = $this->website->getText();
            if ($postData["key"] === $correctKey) {
                $text->addMessage($text->t("access_key.access_granted"));
                return true;
            } else {
                $text->addMessage($text->t("access_key.entered_wrong_key"));
                return false;
            }
        }

        return false;
    }

    /**
     * Sets/renews the site access cookie. Only call this if the user has
     * actually access to the website. This method does nothing if the website
     * has no site key set.
     * @param ResponseInterface $response The current response.
     * @return ResponseInterface The response with the Set-Cookie header.
     */
    private function withSiteAccessCookie(ResponseInterface $response) {
        $siteKey = $this->website->getConfig()->get(Config::OPTION_ACCESS_CODE);
        if (empty($siteKey)) {
            // The website doesn't use an access key
            return $response;
        }

        $now = new DateTimeImmutable();
        $expires = $now->add(new DateInterval('P60D'))->format('r');
        $siteUrl = $this->website->getText()->getUrlMain();
        $path = empty($siteUrl->getPath()) ? '/' : $siteUrl->getPath();

        $cookieInstructioh = "key=$siteKey; expires=$expires; path=$path";
        return $response->withAddedHeader("Set-Cookie", $cookieInstructioh);
    }

}
