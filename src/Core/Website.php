<?php

namespace Rcms\Core;

use Exception;

class Website {

    const MAX_SITE_OPTION_LENGTH = 200;
    const CONFIG_FILE = "config.php";
    const BASE_NAMESPACE = "Rcms\\";

    protected $errors = array();
    protected $debug = false;
    protected $databaseObject;

    /** @var Themes Themes object */
    protected $themesObject;

    /** @var Config Settings of the site. */
    protected $config;

    /** @var Widgets Widgets object. */
    protected $widgets;

    /** @var Authentication Handles authentication */
    protected $authenticationObject;

    /**
     * @deprecated For old page system. Errors are now always echoed after the
     * page is rendered, so old pages shouldn't try to display them themselves 
     */
    public $errorsDisplayed = true;

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
        $this->themesObject = new Themes($this);

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
        return $this->getConfig()->get("title");
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

    /**
     * Gets the widgets manager of the site.
     * @return Widgets The widgets manager.
     */
    public function getWidgets() {
        if (!$this->widgets) {
            // Not every page needs them, so use lazy initialization
            $this->widgets = new Widgets($this);
        }
        return $this->widgets;
    }

    // Paths

    /** Returns the path of the library directory */
    public function getUriLibraries() {
        return $this->getUriApplication() . "library/";
    }

    /** Returns the path of all default controllers, models, pages and views */
    public function getUriApplication() {
        return $this->getConfig()->get('uri') . "src/";
    }

    /** Returns the path of all pages */
    public function getUriPages() {
        return $this->getUriApplication() . "Page/";
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
    }

    public function getErrorCount() {
        return count($this->errors);
    }

    /**
     * Gets a list of all errors that occured loading this page.
     * @return string[] All errors.
     */
    public function getErrors() {
        return $this->errors;
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
        return count($this->getThemeManager()->getCurrentTheme()->getWidgetAreas($this)) + 1;
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

    // Input from $_REQUEST

    /**
     * Gets a string from the $_REQUEST array, without extra "magic quotes"
     * and with a default option if the $_REQUEST array doesn't contain the
     * variable.
     *
     * Note: this method will eventually be moved to the Request class. For now,
     * it remains here for the widgets, as they don't have access to Request yet.
     *
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
     *
     * Note: this method will eventually be moved to the Request class. For now,
     * it remains here for the widgets, as they don't have access to Request yet.
     *
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
        return (int) $default;
    }

    // For old page system

    /**
     * @deprecated Used to give old .inc pages the Website context
     */
    public function execute($file) {
        require $file;
    }

    /**
     * @deprecated Keeps old page system from breaking. Errors are now printed
     * by the page renderer.
     */
    public function echoErrors() {
        // Empty!
    }

}
