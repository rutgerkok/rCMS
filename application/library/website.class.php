<?php

class Website {

    const MAX_SITE_OPTION_LENGTH = 200;
    const CONFIG_FILE = "content/config.php";

    protected $errors = array();
    protected $debug = true;
    protected $errorsDisplayed = false;
    protected $databaseObject;
    
    /** @var Config $config Settings of the site. */
    protected $config;
    /** @var Themes $themes_object */
    protected $themesObject;
    protected $currentPageId;
    protected $siteTitle;
    protected $currentPageTitle; // Title of the page
    protected $currentPageType; // HOME, NORMAL or BACKSTAGE
    /** @var Authentication $authentication_object */
    protected $authenticationObject;
    // The following two fields are only available when using the new page system
    /** @var Page $current_page */
    protected $currentPage; // Available during/after echo_page
    protected $authenticationFailedRank = -1; // Number of required rank which the user didn't have, or -1 if the user's rank is already high enough

    /**
     * Constructs the Website. Page- and theme-specific logic won't be loaded yet.
     */

    function __construct() {
        // We're loaded (included files test for the existance this constant)
        define("WEBSITE", "Loaded");

        // Site settings and database connection
        $this->config = new Config(self::CONFIG_FILE);
        $this->databaseObject = new Database($this);
        $this->config->readFromDatabase($this->databaseObject);

        $this->authenticationObject = new Authentication($this);

        // Workarounds for older PHP versions (5.2, 5.3 and 5.4)
        $this->requireFunctions("lcfirst", "http_response_code");
    }

    /**
     * For compability with old PHP versions, this method loads PHP equivalents
     * of unimplemented functions.
     * @param $functions string[] The functions to load.
     */
    private function requireFunctions($functions) {
        $functions = func_get_args();
        foreach ($functions as $function) {
            if (!function_exists($function)) {
                require_once ($this->getUriLibraries() . $function . '.function.php');
            }
        }
    }

    /**
     * Returns the full title that should be displayed at the top of this page.
     * @return string The title.
     */
    public function getSiteTitle() {
        return $this->siteTitle;
    }

    /**
     * Returns the current page id, like "article" or "account_management". Can
     * be converted to an url/uri using the get_ur*_page methods.
     */
    public function getPageId() {
        return $this->currentPageId;
    }

    /**
     * Returns the current page. Only works with the new page system.
     * @return Page The current page.
     */
    public function getPage() {
        return $this->currentPage;
    }

    /**
     * Returns a shorter title of this page that can be used in breadcrumbs.
     * @return string The shorter title.
     */
    public function getPageTitle() {
        return $this->currentPageTitle;
    }

    /**
     * Returns the current page type: HOME, NORMAL or BACKSTAGE.
     * @return string The current page type.
     */
    public function getPageType() {
        return $this->currentPageType;
    }

    // GETTING OTHER OBJECTS

    /**
     * Returns the database of this site
     * @return Database The database
     */
    public function getDatabase() {
        return $this->databaseObject;
    }

    /**
     * Loads and sets the page being displayed. Causes a fatal error for pages still
     * using the old .inc page system.
     * @param string $pageId The page id.
     */
    protected function loadPage($pageId) {
        // Convert page id to class name
        $pageParts = explode("_", $pageId);
        $pageClassName = "";
        foreach ($pageParts as $part) {
            $pageClassName .= ucFirst($part);
        }
        $pageClassName .= "Page";

        // Load that class
        $this->currentPage = new $pageClassName();
    }

    /**
     * Gets the theme manager. Returns null if the theme hasn't been loaded yet
     * (before echo_page is called).
     * @return Themes The theme manager.
     */
    public function getThemeManager() {
        return $this->themesObject;
    }

    /**
     * Gets the authentication object.
     * @return Authentication The authentication object.
     */
    public function getAuth() {
        return $this->authenticationObject;
    }
    
    /**
     * Gets all settings manager of the site.
     * @return Config The settings manager.
     */
    public function getConfig() {
        return $this->config;
    }

    // Paths

    /** Returns the path of the library directory */
    public function getUriLibraries() {
        return $this->getUriApplication() . "library/";
    }

    /** Returns the path of all default controllers, models, pages and views */
    public function getUriApplication() {
        return $this->getConfig()->get('uri') . "application/";
    }

    /** Returns the path of all pages */
    public function getUriPages() {
        return $this->getUriApplication() . "pages/";
    }

    /** Returns the main site url. Other urls start with this */
    public function getUrlMain() {
        return $this->getConfig()->get('url');
    }

    /** Returns the site root directory */
    public function getUriMain() {
        return $this->getConfig()->get('uri');
    }

    /** Returns the url of the public content directory of this site */
    public function getUrlContent() {
        return $this->getConfig()->get('url') . "content/";
    }

    /** Returns the internal uri of the public content directory */
    public function getUriContent() {
        return $this->getConfig()->get('uri') . "content/";
    }

    /** Returns the url of a page, ready for links */
    public function getUrlPage($name, $id = -1337, $args = array()) {
        if ($id == -1337 && count($args) == 0) { // just the page name
            return $this->getUrlMain() . $name;
        } else { // also the other arguments
            if (count($args) == 0) {
                return $this->getUrlMain() . $name . "/" . $id; //geen andere variabelen, geef weer als example.com/naam/id
            } else { //wel andere variabelen
                $url = $this->getUrlMain() . "index.php?p=" . $name . "&amp;id=" . $id;
                foreach ($args as $key => $value)
                    $url.="&amp;$key=" . urlencode($value);
                return $url;
            }
        }
    }

    /** Returns the internal uri of a page */
    public function getUriPage($name) {
        // Has to account for both the old .inc pages and the newer .php pages
        // Because file_exists lookups are cached, this shouldn't really affect
        // performance.
        $uri_old = $this->getUriPages() . $name . ".inc";
        if (file_exists($uri_old)) {
            return $uri_old;
        } else {
            return $this->getUriPages() . str_replace("_", "", $name) . "page.class.php";
        }
    }

    //Geeft de map van alle thema's terug als url
    public function getUrlThemes() {
        return $this->getUrlContent() . "themes/";
    }

    //Geeft de map van alle thema's terug als uri
    public function getUriThemes() {
        return $this->getUriContent() . "themes/";
    }

    //Geeft de map van alle widgets terug als uri
    public function getUriWidgets() {
        return $this->getUriContent() . "widgets/";
    }

    public function getUriTranslations() {
        return $this->getUriContent() . "translations/";
    }

    public function getUrlJavaScripts() {
        return $this->getUrlContent() . "scripts/";
    }

//Einde paden

    public function addError($message, $public_message = false) {
        if ($this->debug || !$public_message) { //foutmelding alleen weergeven als melding ongevaarlijk is of als debuggen aan is gezet
            $this->errors[count($this->errors)] = $message;
        } else {
            $this->errors[count($this->errors)] = $public_message;
        }
        if ($this->errorsDisplayed) {//geef ook nieuwe foutmeldingen weer, als normale al weergegeven zijn
            $this->echoErrors();
        }
    }

    public function getErrorCount() {
        return count($this->errors);
    }

    public function echoErrors() { //geeft alle foutmeldingen weer
        $this->errorsDisplayed = true;

        $errorCount = count($this->errors); //totaal aantal foutmeldingen
        if ($errorCount == 0) {
            return true;
        } elseif ($errorCount == 1) {
            echo '<div class="error"><h3>' . $this->t("errors.error_occured") . '</h3>';
            echo $this->errors[0];
            echo '</div>';
        } else {
            echo '<div class="error">';
            echo "   <h3>" . str_replace("#", $errorCount, $this->t('errors.errors_occured')) . "</h3>";
            echo '   <p>';
            echo '      <ul>';
            foreach ($this->errors as $nr => $error) {
                echo '<li>' . $error . '</li>';
            }
            echo '      </ul>';
            echo '	 </p>';
            echo '</div>';
        }
        // Clear displayed errors
        unset($this->errors);
        $this->errors = array();
        return true;
    }

    function hasAccess() { //kijkt of site mag worden geladen
        $access = false;
        if ($this->getConfig()->get('password') == "") {
            $access = true;
        } elseif (isSet($_POST['key']) && $this->getConfig()->get('password') == $_POST['key']) {
            $access = true;
        } elseif (isSet($_GET['key']) && $this->getConfig()->get('password') == $_GET['key']) {
            $access = true;
        } elseif (isSet($_COOKIE['key']) && $this->getConfig()->get('password') == $_COOKIE['key']) {
            $access = true;
        }

        return $access;
    }

    /**
     * Echoes the whole page.
     */
    public function echoPage() {
        // Rewrite view_url to p and id
        if (isset($_GET["view_url"])) {
            $split = explode("/", $_GET["view_url"], 2);
            $_REQUEST["p"] = $_POST["p"] = $_GET["p"] = $split[0];
            if (count($split) == 2) {
                $_REQUEST["id"] = $_POST["id"] = $_GET["id"] = $split[1];
            }
        }

        // Check for site password
        if (!$this->hasAccess()) {
            // Echo site code page
            require($this->getUriLibraries() . 'login_page.php');
            return;
        }

        // Site title
        $this->siteTitle = $this->getConfig()->get('title');

        // Get id of page to display
        $givenPageId = $this->getRequestString("p", "home");
        if ($givenPageId != 'home') {
            // Get current page title and id 
            if (!preg_match('/^[a-z0-9_]+$/i', $givenPageId) || !file_exists($this->getUriPage($givenPageId))) {
                // Page doesn't exist, show error and redirect
                http_response_code(404);
                $this->addError($this->t("main.page") . " '" . htmlSpecialChars($givenPageId) . "' " . $this->t('errors.not_found'));
                $this->currentPageId = 'home';
            } else {
                $this->currentPageId = $givenPageId;
            }
        } else {
            // No page id given
            $this->currentPageId = 'home';
        }

        // Set password cookie
        if (strLen($this->getConfig()->get('password')) != 0) {
            setCookie("key", $this->getConfig()->get('password'), time() + 3600 * 24 * 365, "/");
        }

        // Perform page logic (supporting both the old .inc and the new .php pages)
        $uri = $this->getUriPage($this->currentPageId);
        if (substr($uri, -4) == ".php") {
            // We're on the new page system
            $this->loadPage($this->currentPageId);

            // Page title
            $this->currentPageTitle = $this->currentPage->getPageTitle($this);
            if ($this->getConfig()->get('append_page_title')) {
                $this->siteTitle.= ' - ' . $this->currentPage->getShortPageTitle($this);
            }

            // Page type
            $this->currentPageType = $this->currentPage->getPageType();

            // Authentication stuff
            $rank = (int) $this->currentPage->getMinimumRank($this);
            if ($rank == Authentication::$LOGGED_OUT_RANK || $this->getAuth()->check($rank, false)) {
                // Call init methord
                $this->currentPage->init($this);
            } else {
                $this->authenticationFailedRank = $rank;
            }
        } else {
            // Old page system
            // Page title
            $this->currentPageTitle = ucfirst(str_replace('_', ' ', $this->currentPageId));
            if ($this->getConfig()->get('append_page_title')) {
                $this->siteTitle.= ' - ' . $this->currentPageTitle;
            }

            // Page type
            switch ($this->currentPageId) {
                case "search":
                case "archive":
                case "calendar":
                    $this->currentPageType = "NORMAL";
                    break;
                default:
                    $this->currentPageType = "BACKSTAGE";
                    break;
            }
        }

        // Output page
        $this->themesObject = new Themes($this);
        $this->themesObject->output();
    }

    /**
     * Echoes only the main content of the page, without any clutter.
     */
    public function echoPageContent() { //geeft de hoofdpagina weer
        // Locales
        setLocale(LC_ALL, explode("|", $this->t("main.locales")));

        if ($this->currentPage) {
            // New page system
            // Title
            $title = $this->currentPage->getPageTitle($this);
            if (!empty($title)) {
                echo "<h2>" . $title . "</h2>\n";
            }

            // Get page content (based on permissions)
            $textToDisplay = "";
            if ($this->authenticationFailedRank >= 0) {
                $loginView = new LoginView($this, $this->authenticationFailedRank);
                $textToDisplay = $loginView->getText();
            } else {
                $textToDisplay = $this->currentPage->getPageContent($this);
            }

            // Echo errors
            if (!$this->errorsDisplayed) {
                $this->echoErrors();
            }

            // Display page content
            echo $textToDisplay;
        } else {
            // Old page system
            require($this->getUriPage($this->currentPageId));
        }
    }

    /**
     * Checks if the current user viewing the site has the rank.
     * @param int $neededRank The needed rank.
     * @return boolean Whether the user has that rank.
     */
    public function userHasRank($neededRank) {
        $oAuth = $this->getAuth();
        $user = $oAuth->getCurrentUser();
        if ($user) {
            $userRank = $user->getRank();
            if ($oAuth->isHigherOrEqualRank($userRank, $neededRank)) {
                return true;
            } else {
                return false;
            }
        } elseif (!$oAuth->isValidRankForAccounts($neededRank)) {
            return true;
        } else {
            return false;
        }
    }

    public function isLoggedIn() {
        return $this->getAuth()->getCurrentUser() != null;
    }

    public function isLoggedInAsStaff($admin = false) {
        $neededRank = Authentication::$MODERATOR_RANK;
        if ($admin) {
            $neededRank = Authentication::$ADMIN_RANK;
        }
        return $this->userHasRank($neededRank);
    }

    /**
     * Returns the id of the user currently logged in. Returns -1 if the user isn't logged in.
     * @return int The id of the user currently logged in.
     */
    public function getCurrentUserId() {
        $user = $this->getAuth()->getCurrentUser();
        if ($user == null) {
            return -1;
        } else {
            return $user->getId();
        }
    }

    /**
     * Returns the number of sidebars that this theme supports. Won't work
     * if echoPage is not yet called.
     * @return int The number of sidebars.
     */
    public function getThemeWidgetAreaCount() {
        // Defined sidebars plus one for the homepage
        return count($this->getThemeManager()->get_theme()->getWidgetAreas($this)) + 1;
    }

    // TRANSLATIONS

    public function t($key) {
        $keys = explode(".", $key, 2);
        if (isSet($this->translations[$keys[0]])) { //al geladen
            return $this->translations[$keys[0]][$keys[1]];
        } else { //moet nog geladen worden
            $translationsFile = $this->getUriTranslations() . $this->getConfig()->get("language") . "/translations_" . $keys[0] . ".txt";
            if (file_exists($translationsFile)) { //laad
                $fileContents = file($translationsFile);
                foreach ($fileContents as $line) {
                    $translation = explode("=", $line, 2);
                    $this->translations[$keys[0]][$translation[0]] = trim($translation[1]);
                }
                unset($fileContents);

                //en geef juiste waarde terug
                return $this->translations[$keys[0]][$keys[1]];
            } else { //foutmelding
                echo "<br /><br /><code>$translationsFile</code> was not found!";
                die();
            }
        }
    }

    public function tReplacedKey($key, $replaceInKey, $lowercase = false) {
        if ($lowercase) {
            return str_replace("#", strToLower($this->t($replaceInKey)), $this->t($key));
        } else {
            return str_replace("#", $this->t($replaceInKey), $this->t($key));
        }
    }

    public function tReplaced($key, $replaceInKey, $lowercase = false) {
        if ($lowercase) {
            return str_replace("#", strtolower($replaceInKey), $this->t($key));
        } else {
            return str_replace("#", $replaceInKey, $this->t($key));
        }
    }

    // INPUT FROM $_REQUEST

    /**
     * Gets a string from the $_REQUEST array, without extra "magic quotes"
     * and with a default option if the $_REQUEST array doesn't contain the
     * variable.
     * @param string $name Key in the $_REQUEST array.
     * @param string $default Default option, if value is not found.
     * @return string The value in the $_REQUEST array, or the default value.
     */
    public function getRequestString($name, $default = "") {
        if (isSet($_REQUEST[$name])) {
            if (ini_get("magic_quotes_gpc")) {
                return stripSlashes($_REQUEST[$name]);
            } else {
                return $_REQUEST[$name];
            }
        } else {
            return $default;
        }
    }

    /**
     * Gets an int from the $_REQUEST array. Returns the default value if there
     * was no valid integer provided.
     * @param string $name Key in the $_REQUEST array.
     * @param int $default Default option.
     * @return int The int.
     */
    public function getRequestInt($name, $default = 0) {
        if (isSet($_REQUEST[$name])) {
            if (is_numeric($_REQUEST[$name])) {
                return (int) $_REQUEST[$name];
            }
        }
        return $default;
    }

}
