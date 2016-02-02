<?php

namespace Rcms\Page\Renderer;

use Psr\Http\Message\ServerRequestInterface;
use Rcms\Core\Authentication;
use Rcms\Core\Exception\NotFoundException;
use Rcms\Core\Request;
use Rcms\Core\Website;
use Rcms\Page\Page;
use Rcms\Page\Error404Page;
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
        // TODO

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
        return $page->getResponse($this->website, $request);
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

}
