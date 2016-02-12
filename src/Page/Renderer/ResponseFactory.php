<?php

namespace Rcms\Page\Renderer;

use DateInterval;
use DateTimeImmutable;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Rcms\Core\Authentication;
use Rcms\Core\Config;
use Rcms\Core\Exception\NotFoundException;
use Rcms\Core\Request;
use Rcms\Core\Website;
use Rcms\Page\Page;
use Rcms\Page\Error404Page;
use Rcms\Page\ErrorAccessCodeRequiredPage;
use Rcms\Page\ErrorLoginRequiredPage;
use Rcms\Page\HomePage;

/**
 * Used to render a webpage on the site.
 */
final class ResponseFactory {

    /**
     * @var Website The website.
     */
    private $website;

    public function __construct(Website $website) {
        $this->website = $website;
    }

    public function __invoke(ServerRequestInterface $serverRequest) {
        $request = new Request($serverRequest);

        // Load page
        try {
            $pageName = $request->getPageName();
            $page = $this->loadPage($pageName);
        } catch (NotFoundException $e) {
            $page = new Error404Page();
        }

        // Access code check
        $enteredCorrectKey = $this->checkSiteAccess($serverRequest);
        if (!$enteredCorrectKey) {
            $page = new ErrorAccessCodeRequiredPage();
        }

        // Login check
        $minimumRank = $page->getMinimumRank($request);
        if ($minimumRank != Authentication::RANK_LOGGED_OUT && !$this->website->getAuth()->check($minimumRank, false)) {
            // Login failure
            $page = new ErrorLoginRequiredPage($minimumRank);
        }

        // Initialize page
        try {
            $page->init($this->website, $request);
        } catch (NotFoundException $e) {
            $page = new Error404Page();
            $page->init($this->website, $request);
        }

        // Convert page to response
        $response = $page->getResponse($this->website, $request);
        if ($enteredCorrectKey) {
            $response = $this->withSiteAccessCookie($response);
        }
        return $response;
    }

    /**
     * Gets the simple class name of the given page id.
     * 
     * This method doesn't check its parameter. For example, "delete_article"
     * would turn into "DeleteArticlePage", even if no such page would exist.
     * @param string $pageName The page id.
     * @return string The simple class name.
     */
    private function getPageClassName($pageName) {
        $pageParts = explode("_", $pageName);
        $pageClassName = 'Rcms\\Page\\';
        foreach ($pageParts as $part) {
            $pageClassName .= ucFirst($part);
        }
        $pageClassName .= "Page";
        return $pageClassName;
    }

    /**
     * Loads the page with the given name.
     * @param string $pageName The page name.
     * @return Page The page.
     * @throws NotFoundException If the page with the given name is not found.
     */
    private function loadPage($pageName) {
        if ($pageName == "") {
            return new HomePage();
        }
        if (!preg_match('/^[a-z0-9_]+$/i', $pageName)) {
            // Invalid name
            throw new NotFoundException();
        }

        $pageClass = $this->getPageClassName($pageName);
        if (!class_exists($pageClass)) {
            // No such class exists, try old system
            return $this->loadPageUsingOldSystem($pageName);
        }
        return new $pageClass;
    }

    private function loadPageUsingOldSystem($pageName) {
        if (!preg_match('/^[a-z0-9_]+$/i', $pageName)) {
            // Invalid name
            throw new NotFoundException();
        }

        $pageFile = $this->website->getUriPage($pageName);
        if (!file_exists($pageFile)) {
            throw new NotFoundException();
        }

        return new OldPageWrapper($this->website->getSiteTitle(), $pageFile);
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
            return;
        }

        $now = new DateTimeImmutable();
        $expires = $now->add(new DateInterval('P60D'))->format('r');
        $siteUrl = $this->website->getText()->getUrlMain();
        $path = empty($siteUrl->getPath())? '/' : $siteUrl->getPath();

        $cookieInstructioh = "key=$siteKey; expires=$expires; path=$path";
        return $response->withAddedHeader("Set-Cookie", $cookieInstructioh);
    }

}
