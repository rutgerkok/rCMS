<?php

namespace Rcms\Core;

use PDO;
use PDOException;
use Rcms\Core\Exception\NotFoundException;
use Rcms\Core\Widget\InstalledWidgets;
use Zend\Diactoros\Uri;

class Website {

    const MAX_SITE_OPTION_LENGTH = 200;
    const CONFIG_FILE = "config.php";
    const BASE_NAMESPACE = "Rcms\\";

    /** @var TablePrefixedPDO The main database */
    protected $databaseObject;

    /** @var Themes Themes object */
    protected $themesObject;

    /** @var Config Settings of the site. */
    protected $config;

    /** @var InstalledWidgets Widgets currently loaded. */
    protected $widgets;

    /** @var Authentication Handles authentication */
    protected $authenticationObject;

    /** @var Text Handles errors, messages and translations. */
    protected $text;

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
        $this->text = new Text(new Uri($this->getConfig()->get('url')), $this->getUriTranslations(Config::DEFAULT_LANGUAGE), $this->getUrlJavaScripts());

        // Connect to database, read settings
        try {
            $dataSource = "mysql:dbname={$this->config->get(Config::OPTION_DATABASE_NAME)};host={$this->config->get(Config::OPTION_DATABASE_HOST)}";
            $this->databaseObject = new TablePrefixedPDO($dataSource, $this->config->get(Config::OPTION_DATABASE_USER), $this->config->get(Config::OPTION_DATABASE_PASSWORD), array("table_prefix" => $this->config->get(Config::OPTION_DATABASE_TABLE_PREFIX)));
            $this->databaseObject->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->databaseObject->prefixTables(array("categorie", "users",
                "links", "artikel", "comments", "menus", "widgets", "documents",
                "settings", "gebruikers", "reacties"));
            $this->config->readFromDatabase($this->databaseObject);
        } catch (PDOException $e) {
            // No database connection - safe to ignore this error, as the page
            // renderer will start the installation procedure, based on the lack
            // of settings
            $this->text->addError($this->text->tReplaced("install.no_database_connection", $e->getMessage()));
        }

        // Set updated properties of Text object, now that settings are read
        // from the database
        $this->text->setTranslationsDirectory($this->getUriTranslations($this->config->get("language")));
        $this->text->setUrlRewrite($this->config->get("url_rewrite"));

        // Init other objects
        if ($this->databaseObject == null) {
            $this->authenticationObject = new Authentication($this, null);
        } else {
            $this->authenticationObject = new Authentication($this, new UserRepository($this->databaseObject));
        }
        $this->themesObject = new Themes($this);

        // Workarounds for older PHP versions (5.3)
        $this->requireFunctions("http_response_code");

        // Locales
        setLocale(LC_ALL, explode("|", $this->text->t("main.locales")));
    }

    /**
     * For compability with old PHP versions, this method loads PHP equivalents
     * of unimplemented functions.
     * @param $functions string[] The functions to load.
     */
    private function requireFunctions($functions) {
        $arguments = func_get_args();
        foreach ($arguments as $function) {
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
     * @return TablePrefixedPDO The database.
     * @throws NotFoundException When not connected.
     */
    public function getDatabase() {
        if ($this->databaseObject == null) {
            throw new NotFoundException();
        }
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
     * @return WidgetRepository The widgets manager.
     */
    public function getWidgets() {
        if (!$this->widgets) {
            // Not every page needs them, so use lazy initialization
            $this->widgets = new InstalledWidgets($this);
        }
        return $this->widgets;
    }

    /**
     * Gets access to the message system of the page. This is uses to translate
     * messages and notify users.
     * @return Text The message system.
     */
    public function getText() {
        return $this->text;
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
        return new Uri($this->getConfig()->get('url'));
    }

    /** Returns the site root directory */
    public function getUriMain() {
        return $this->getConfig()->get('uri');
    }

    /**
     * Gets the url of the public content directory.
     * @return UriInterface The url (with a trailing slash).
     */
    public function getUrlContent() {
        return new Uri($this->getConfig()->get('url') . "content/");
    }

    /**
     * Gets the interal uri of the public content directory.
     * @return string The url (with a trailing slash).
     */
    public function getUriContent() {
        return $this->getConfig()->get('uri') . "content/";
    }

    /** @deprecated Only accounts for old page system. */
    public function getUriPage($name) {
        return $this->getUriPages() . $name . ".inc";
    }

    /**
     * Creates an URL to the given page.
     * @param string $pageName Name of the page, like "edit_article".
     * @param string|string[]|null $params Parameters of the page, appear in URL
     * as subdirectories. `getUrlPage("foo", ["this", "that"])` -> 
     * `foo/this/that`. You can pass one string, or an array of strings. You can
     * also pass null to skip this parameter.
     * @param array $args Array of key/value pairs that should be used as the
     * query string. `["foo" => "bar"]`  gives `?foo=bar` at the end of the URL.
     * @return UriInterface The url.
     */
    public function getUrlPage($pageName, $params = null, $args = array()) {
        return $this->text->getUrlPage($pageName, $params, $args);
    }

    /**
     * Gets the (web-accessible) url of the themes directory.
     * @return UriInterface The url (with a trailing slash).
     */
    public function getUrlThemes() {
        $contentUrl = $this->getUrlContent();
        return $contentUrl->withPath($contentUrl->getPath() . "themes/");
    }

    /**
     * Gets the uri of the themes directory.
     * @return string The uri (with a trailing slash).
     */
    public function getUriThemes() {
        return $this->getUriContent() . "themes/";
    }

    /**
     * Gets the uri of the widgets directory.
     * @return UriInterface The uri (with a trailing slash).
     */
    public function getUriWidgets() {
        return $this->getUriContent() . "widgets/";
    }

    /**
     * Gets the URI of either the root translations directory, or the
     * translations directory of a specific language.
     * @param string|null $languageCode When present, the directory of this
     * specific language is returned.
     * @return UriInterface The uri (with a trailing slash).
     */
    public function getUriTranslations($languageCode = null) {
        $path = $this->getUriContent() . "translations/";
        if ($languageCode !== null) {
            $path.= $languageCode . '/';
        }
        return $path;
    }

    /**
     * Gets the directory where the JavaScript files are stored.
     * @return UriInterface The directory (so URL path has a trailing slash)
     */
    public function getUrlJavaScripts() {
        $contentUrl = $this->getUrlContent();
        return $contentUrl->withPath($contentUrl->getPath() . "scripts/");
    }

//Einde paden

    public function addError($error) {
        $this->text->addError($error);
    }

    /**
     * @deprecated Misused as a way to check if it is safe to save something.
     * If you need to error count for display purposes, count them yourselves.
     */
    public function getErrorCount() {
        return count($this->text->getErrors());
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
        $neededRank = Authentication::RANK_MODERATOR;
        if ($admin) {
            $neededRank = Authentication::RANK_ADMIN;
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

    // Translations, see documentation is Messages class.
    public function t($key) {
        return $this->text->t($key);
    }

    public function tReplacedKey($key, $replacementKey, $lowercase = false) {
        return $this->text->tReplacedKey($key, $replacementKey, $lowercase);
    }

    public function tReplaced($key, $replacements) {
        // Varargs support
        if (!is_array($replacements)) {
            $replacements = array_slice(func_get_args(), 1);
        }
        return $this->text->tReplaced($key, $replacements);
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
        // Note: logic is the same as in the Request class - keep them in sync!
        if (isSet($_REQUEST[$name]) && is_scalar($_REQUEST[$name])) {
            if (ini_get("magic_quotes_gpc")) {
                return stripSlashes((string) $_REQUEST[$name]);
            } else {
                return (string) $_REQUEST[$name];
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
        // Note: logic is the same as in the Request class - keep them in sync!
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
