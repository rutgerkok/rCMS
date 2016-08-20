<?php

namespace Rcms\Page\Renderer;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

use Rcms\Core\Authentication;
use Rcms\Core\NotFoundException;
use Rcms\Core\Request;
use Rcms\Core\Website;
use Rcms\Page\Error404Page;
use Rcms\Page\ErrorLoginRequiredPage;
use Rcms\Page\HomePage;
use Rcms\Page\Page;

/**
 * Middleware that extracts the page from the request and then renders it.
 */
final class PageResponder {

    /**
     * @var Website The website instance.
     */
    private $website;

    public function __construct(Website $website) {
        $this->website = $website;
    }

    public function __invoke(ServerRequestInterface $requestInterface, ResponseInterface $response, callable $next = null) {
        $request = new Request($requestInterface);

        // Load page
        try {
            $pageName = $request->getPageName();

            // Switch to install page if website is not installed yet
            if (!$this->website->getConfig()->isDatabaseUpToDate()) {
                $pageName = "install";
            }

            $page = $this->loadPage($pageName);
        } catch (NotFoundException $e) {
            $page = new Error404Page();
        }

        // Login check
        $minimumRank = $page->getMinimumRank();
        if ($minimumRank != Authentication::RANK_LOGGED_OUT && !$this->website->getAuth()->check($minimumRank, false)) {
            // Login failure
            $page = new ErrorLoginRequiredPage($minimumRank);
        }

        // Get response
        $updatedResponse = $next? $next($requestInterface, $response) : $response;
        return Responses::getPageResponse($this->website, $request, $page, $updatedResponse);
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

        $className = $this->getPageClassName($pageName);
        return new $className;
    }

}
