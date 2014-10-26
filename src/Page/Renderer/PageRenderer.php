<?php

namespace Rcms\Page\Renderer;

use BadMethodCallException;
use Rcms\Core\Authentication;
use Rcms\Core\Exception\NotFoundException;
use Rcms\Core\Exception\RedirectException;
use Rcms\Core\Request;
use Rcms\Core\Website;
use Rcms\Page\Page;
use Rcms\Page\View\LoginView;

/**
 * Used to render a webpage on the site.
 */
class PageRenderer {

    const HOME_PAGE_NAME = "home";
    const ERROR_404_PAGE_NAME = "error404";

    /**
     * Uses the request variables to construct an array of the path the user
     * requested.
     * @return string[] The path the user requested.
     */
    public static function getPagePath() {
        // Path given
        if (isSet($_SERVER["PATH_INFO"])) {
            // Paths can be in the form "/this/that/", change that to "this/that"
            $pathInfo = trim($_SERVER["PATH_INFO"], '/');
            return explode('/', $pathInfo);
        }

        // Construct from request variables
        $pageName = self::HOME_PAGE_NAME;
        $pageVar = '';
        if (isSet($_REQUEST['p'])) {
            $pageName = $_REQUEST['p'];
        }
        if (isSet($_REQUEST["id"])) {
            $pageVar = $_REQUEST["id"];
        }
        return array($pageName, $pageVar);
    }

    /**
     * @var Website The website class.
     */
    protected $website;

    /**
     * @var Request The request.
     */
    private $request;

    /**
     * @var Page Page the user is visiting 
     */
    protected $page;

    /**
     * @var int Number of required rank which the user didn't have, or -1 if the user's rank is already high enough.
     */
    protected $authenticationFailedRank = -1;

    public function __construct(Website $website, array $pagePath) {
        $this->website = $website;

        // Get from array
        $pageName = self::HOME_PAGE_NAME;
        $params = array();
        if (count($pagePath) > 0) {
            $pageName = $pagePath[0];
        }
        if (count($pagePath) > 1) {
            $params = array_slice($pagePath, 1);
        }

        // Populate fiels
        $this->request = new Request($website, $params);
        $this->page = $this->loadPage($pageName);

        // Locales
        setLocale(LC_ALL, explode("|", $website->t("main.locales")));

        // Some scripts still rely on those variables
        $_GET["p"] = $_POST["p"] = $_REQUEST["p"] = $pageName;
        if (count($params) >= 1) {
            $_GET["id"] = $_POST["id"] = $_REQUEST["id"] = $params[0];
        }
    }

    /**
     * Gets the page class for the page with the given name.
     * @param string $pageName The page name.
     */
    protected function loadPage($pageName) {
        if ($pageName != self::HOME_PAGE_NAME) {
            // Get current page title and id 
            if (!preg_match('/^[a-z0-9_]+$/i', $pageName) || !file_exists($this->getUriPage($pageName))) {
                // Page doesn't exist, show error and redirect
                http_response_code(404);
                $pageName = self::ERROR_404_PAGE_NAME;
            }
        }

        return $this->createPage($pageName);
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
        $pageClassName = "";
        foreach ($pageParts as $part) {
            $pageClassName .= ucFirst($part);
        }
        $pageClassName .= "Page";
        return $pageClassName;
    }

    protected function createPage($pageName) {
        $website = $this->website;
        $page = $this->createPageObject($pageName);

        // Check for site password
        if (!$website->hasAccess()) {
            // Echo site code page
            require($website->getUriLibraries() . 'login_page.php');
            exit;
        }

        // Set password cookie
        $sitePassword = $website->getConfig()->get("password");
        if (!empty($sitePassword)) {
            setCookie("key", $sitePassword, time() + 3600 * 24 * 365, "/");
        }

        // Authentication stuff
        $rank = (int) $page->getMinimumRank($this->request);

        if ($rank == Authentication::$LOGGED_OUT_RANK || $website->getAuth()->check($rank, false)) {
            // Call init method
            try {
                $page->init($this->request);
            } catch (NotFoundException $e) {
                $page = $this->loadPage(self::ERROR_404_PAGE_NAME);
                $page->init($this->request);
            } catch (RedirectException $e) {
                $this->handleRedirect($e);
            }
        } else {
            $this->authenticationFailedRank = $rank;
        }

        return $page;
    }

    private function handleRedirect(RedirectException $e) {
        if ($e->getRedirectionType() == RedirectException::TYPE_ALWAYS) {
            http_response_code(301);
        }
        header("Location: " . $e->getRedirectionUrl());
    }

    protected function createPageObject($pageName) {
        // Try new page system
        $className = $this->getPageClassName($pageName);
        $pageFile = $this->website->getUriPages() . $className . ".php";
        if (file_exists($pageFile)) {
            // Convert page id to class name
            $fullClassName = Website::BASE_NAMESPACE . "Page\\" . $className;

            // Load that class
            return new $fullClassName();
        }

        // Try legacy page system
        $oldPageFile = $this->website->getUriPages() . $pageName . ".inc";
        if (file_exists($oldPageFile)) {
            return new OldPageWrapper(ucFirst(str_replace('_', ' ', $pageName)), $oldPageFile);
        }

        throw new BadMethodCallException("Invalid page name: " . $pageName);
    }

    /**
     * Returns the current page. Only works with the new page system.
     * @return Page The current page.
     */
    public function getPage() {
        return $this->page;
    }

    /**
     * Returns a shorter title of this page that can be used in breadcrumbs.
     * @return string The shorter title.
     */
    public function getPageTitle() {
        if ($this->authenticationFailedRank >= 0) {
            return $this->website->t("main.log_in");
        }
        return $this->page->getPageTitle($this->website->getText());
    }

    /**
     * Gets the short title of the current page. Must not be called before
     * render() is called, so that pages are properly initialized.
     * @return string The title.
     */
    public function getShortPageTitle() {
        if ($this->authenticationFailedRank >= 0) {
            return $this->website->t("main.log_in");
        }
        return $this->page->getShortPageTitle($this->website->getText());
    }

    /**
     * Returns the current page type: HOME, NORMAL or BACKSTAGE.
     * @return string The current page type.
     */
    public function getPageType() {
        return $this->page->getPageType();
    }

    /**
     * Gets the title for in headers on the page. Must not be called before
     * render() is called, so that pages are properly initialized.
     * @return string The title.
     */
    public function getHeaderTitle() {
        $title = $this->website->getSiteTitle();
        if ($this->website->getConfig()->get("append_page_title", false)) {
            if (!($this->page instanceof HomePage)) {
                $title.= " - " . $this->getShortPageTitle();
            }
        }
        return $title;
    }

    /** Returns the internal uri of a page */
    public function getUriPage($name) {
        // Has to account for both the old .inc pages and the newer .php pages
        $oldPageUri = $this->website->getUriPages() . $name . ".inc";
        if (file_exists($oldPageUri)) {
            return $oldPageUri;
        } else {
            return $this->website->getUriPages() . $this->getPageClassName($name) . ".php";
        }
    }

    /**
     * Echoes the whole page.
     */
    public function render() {
        $website = $this->website;

        // Output page
        $themes = $website->getThemeManager();
        $outputContext = new ThemeElementsRenderer($website, $themes->getCurrentTheme(), $this);
        $outputContext->render();
    }

    /**
     * Gets the main content of the page. If the user is logged out, an login
     * form is returned.
     * @return string The main content.
     */
    public function getMainContent() {
        if ($this->authenticationFailedRank >= 0) {
            $auth = $this->website->getAuth();
            $errorMessage = $auth->getLoginError($this->authenticationFailedRank);
            $loginView = new LoginView($this->website->getText(), $errorMessage);
            return $loginView->getText();
        } else {
            return $this->page->getPageContent($this->request);
        }
    }

}
