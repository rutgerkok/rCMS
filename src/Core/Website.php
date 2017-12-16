<?php

namespace Rcms\Core;

use PDO;
use PDOException;
use Psr\Http\Message\UriInterface;
use Rcms\Core\Widget\InstalledWidgets;
use Rcms\Theme\ThemeManager;
use Zend\Diactoros\Uri;

class Website {

    const MAX_SITE_OPTION_LENGTH = 200;
    const CONFIG_FILE = "config.php";
    const BASE_NAMESPACE = "Rcms\\";

    /** @var TablePrefixedPDO The main database */
    protected $databaseObject;

    /** @var ThemeManager Themes object */
    protected $themesObject;

    /** @var Config Settings of the site. */
    protected $config;

    /** @var InstalledWidgets Widgets currently loaded. */
    protected $widgets;

    /** @var UserRepository User repository. */
    protected $userRepository;

    /** @var Text Handles errors, messages and translations. */
    protected $text;

    /**
     * Constructs the Website. Page- and theme-specific logic won't be loaded yet.
     */
    function __construct() {
        // We're loaded (included files test for the existance this constant)
        define("WEBSITE", "Loaded");

        // Site settings and database connection
        $this->config = new Config(dirname(dirname(__DIR__)) . '/' . self::CONFIG_FILE);
        $this->text = new Text(new Uri($this->getConfig()->get('url_web')), $this->getUriTranslations(Config::DEFAULT_LANGUAGE), $this->getUrlJavaScripts());

        // Connect to database, read settings
        try {
            $dataSource = "mysql:dbname={$this->config->get(Config::OPTION_DATABASE_NAME)};host={$this->config->get(Config::OPTION_DATABASE_HOST)}";
            $this->databaseObject = new TablePrefixedPDO($dataSource,
                    $this->config->get(Config::OPTION_DATABASE_USER),
                    $this->config->get(Config::OPTION_DATABASE_PASSWORD),
                    ["table_prefix" => $this->config->get(Config::OPTION_DATABASE_TABLE_PREFIX)]);
            $this->databaseObject->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->databaseObject->prefixTables(["categories", "users",
                "links", "artikel", "comments", "menus", "widgets", "documents",
                "settings", "gebruikers", "reacties", "categorie"]);
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
        if ($this->databaseObject !== null) {
            $this->userRepository = new UserRepository($this->databaseObject);
        }
        $this->themesObject = new ThemeManager($this);

        // Locales
        setLocale(LC_ALL, explode("|", $this->text->t("main.locales")));
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
     * @return ThemeManager The theme manager.
     */
    public function getThemeManager() {
        return $this->themesObject;
    }

    /**
     * Gets the user repository of the website, containing all user data.
     * @return UserRepository The user repository.
     * @throws NotFoundException If the user repository is offline.
     */
    public function getUserRepository() {
        if ($this->userRepository === null) {
            throw new NotFoundException();
        }
        return $this->userRepository;
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
    
    /**
     * Gets the ranks on this website.
     * @return Ranks The ranks.
     */
    public function getRanks() {
        return new Ranks();
    }

    // Paths

    /** Returns the main site url. Other urls start with this */
    public function getUrlMain() {
        return new Uri($this->getConfig()->get('url_web'));
    }

    /**
     * Gets the interal uri of the public (web) content directory.
     * @return string The url (with a trailing slash).
     */
    public function getUriWeb() {
        return $this->getConfig()->get('uri_web');
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
    public function getUrlPage($pageName, $params = null, $args = []) {
        return $this->text->getUrlPage($pageName, $params, $args);
    }

    /**
     * Gets the url of the themes directory.
     * @return UriInterface The url (with a trailing slash).
     */
    public function getUrlTheme($themeDirectoryName) {
        $extendUrl = $this->getUrlExtend();
        return $extendUrl->withPath($extendUrl->getPath() . "themes/$themeDirectoryName/");
    }
    
    /**
     * Gets the url of the theme that is currently active.
     * @return UriInterface The url (with a trailing slash).
     */
    public function getUrlActiveTheme() {
        return $this->getUrlTheme($this->getConfig()->get(Config::OPTION_THEME));
    }

    /**
     * Gets the path to the folder containing the extensions.
     * @return string The path to that folder, with a trailing slash.
     */
    public function getUriExtend() {
        return $this->config->get("uri_extend");
    }

    /**
     * Gets the URL to the folder containing the public files of the extensions.
     * @return type
     */
    public function getUrlExtend() {
        return new Uri($this->config->get("url_extend"));
    }

    /**
     * Gets the uri of the themes directory.
     * @return string The uri (with a trailing slash).
     */
    public function getUriThemes() {
        return $this->getUriExtend() . "themes/";
    }

    /**
     * Gets the uri of the widgets directory.
     * @return string The uri (with a trailing slash).
     */
    public function getUriWidgets() {
        return $this->getUriExtend() . "widgets/";
    }

    /**
     * Gets the URI of either the root translations directory, or the
     * translations directory of a specific language.
     * @param string|null $languageCode When present, the directory of this
     * specific language is returned.
     * @return string The uri (with a trailing slash).
     */
    public function getUriTranslations($languageCode = null) {
        $path = $this->getUriExtend() . "translations/";
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
        $contentUrl = $this->getUrlMain();
        return $contentUrl->withPath($contentUrl->getPath() . "javascript/");
    }

//Einde paden

    public function addError($error) {
        $this->text->addError($error);
    }

    // Translations, see documentation is Text class.
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

}
