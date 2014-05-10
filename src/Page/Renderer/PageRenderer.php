<?php

namespace Rcms\Page\Renderer;

use BadMethodCallException;
use Rcms\Core\Authentication;
use Rcms\Core\Request;
use Rcms\Core\Website;
use Rcms\Page\Page;

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

/**
 * Used to render a webpage on the site.
 */
class PageRenderer {

    const HOME_PAGE_NAME = "home";

    /**
     * Uses the request variables to construct an array of the path the user
     * requested.
     * @return string[] The path the user requested.
     */
    public static function getPagePath() {
        // Path given
        if (isSet($_GET["view_url"])) {
            return explode('/', $_GET["view_url"]);
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
    protected $request;
    /**
     * @var string Internal name of the page, like "edit_article".
     */
    protected $pageName;
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
        $this->page = $this->loadPage($pageName);
        $this->pageName = $pageName;
        $this->request = new Request($website, $params);

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
        $website = $this->website;
        if ($pageName != self::HOME_PAGE_NAME) {
            // Get current page title and id 
            if (!preg_match('/^[a-z0-9_]+$/i', $pageName) || !file_exists($this->getUriPage($pageName))) {
                // Page doesn't exist, show error and redirect
                http_response_code(404);
                $website->addError($website->t("main.page") . " '" . htmlSpecialChars($pageName) . "' " . $website->t('errors.not_found'));
                $pageName = self::HOME_PAGE_NAME;
            }
        }

        return $this->createPageObject($pageName);
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

    private function createPageObject($pageName) {
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
     * Returns the internal page name, like "article" or "account_management". Can
     * be converted to an url/uri using the get_ur*_page methods.
     */
    public function getPageName() {
        return $this->pageName;
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
        return $this->page->getShortPageTitle($this->website);
    }

    /**
     * Returns the current page type: HOME, NORMAL or BACKSTAGE.
     * @return string The current page type.
     */
    public function getPageType() {
        return $this->page->getPageType();
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

        // Check for site password
        if (!$website->hasAccess()) {
            // Echo site code page
            require($website->getUriLibraries() . 'login_page.php');
            return;
        }

        // Set password cookie
        $sitePassword = $website->getConfig()->get("password");
        if (!empty($sitePassword)) {
            setCookie("key", $sitePassword, time() + 3600 * 24 * 365, "/");
        }

        // Authentication stuff
        $rank = (int) $this->page->getMinimumRank($this->request);
        if ($rank == Authentication::$LOGGED_OUT_RANK || $website->getAuth()->check($rank, false)) {
            // Call init method
            $this->page->init($this->request);
        } else {
            $this->authenticationFailedRank = $rank;
        }

        // Output page
        $themes = $website->getThemeManager();
        $outputContext = new ThemeElementsRenderer($website, $themes->getCurrentTheme(), $this);
        $outputContext->render();
    }

    /**
     * Gets the title for in headers on the page. Must not be called before
     * render() is called, so that pages are properly initialized.
     * @return string The title.
     */
    public function getHeaderTitle() {
        $title = $this->website->getSiteTitle();
        if ($this->website->getConfig()->get("append_page_title", false)) {
            if ($this->pageName !== self::HOME_PAGE_NAME) {
                $title.= " - " . $this->getShortPageTitle();
            }
        }
        return $title;
    }
    
    /**
     * Gets the short title of the current page. Must not be called before
     * render() is called, so that pages are properly initialized.
     * @return string The title.
     */
    public function getShortPageTitle() {
        return $this->page->getShortPageTitle($this->request);
    }

    /**
     * Echoes only the main content of the page, without any clutter.
     */
    public function echoPageContent() { //geeft de hoofdpagina weer
        $website = $this->website;

        // Locales
        setLocale(LC_ALL, explode("|", $website->t("main.locales")));

        // Title
        $title = $this->page->getPageTitle($this->request);
        if (!empty($title)) {
            echo "<h2>" . $title . "</h2>\n";
        }

        // Get page content (based on permissions)
        $textToDisplay = "";
        if ($this->authenticationFailedRank >= 0) {
            $loginView = new LoginView($website, $this->authenticationFailedRank);
            $textToDisplay = $loginView->getText();
        } else {
            $textToDisplay = $this->page->getPageContent($this->request);
        }

        // Display page content
        echo $textToDisplay;
    }

}
