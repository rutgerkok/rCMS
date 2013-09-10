<?php

class Website {

    const MAX_SITE_OPTION_LENGTH = 200;

    protected $errors = array();
    protected $debug = false;
    protected $errorsDisplayed = false;
    protected $databaseObject;

    /** @var Themes $themes_object */
    protected $themes_object;
    protected $current_page_id;
    protected $site_title;
    protected $current_page_title; // Title of the page
    protected $current_page_type; // HOME, NORMAL or BACKSTAGE
    /** @var Authentication $authentication_object */
    protected $authentication_object;
    // The following two fields are only available when using the new page system
    /** @var Page $current_page */
    protected $currentPage; // Available during/after echo_page
    protected $authentication_failed_rank = -1; // Number of required rank which the user didn't have, or -1 if the user's rank is already high enough

    /**
     * Constructs the Website. Page- and theme-specific logic won't be loaded yet.
     */

    function __construct() {
        // Site settings and database connection
        $this->readSiteSettingsFromFile();
        $this->databaseObject = new Database($this);
        $this->readSiteSettingsFromDatabase();

        $this->authentication_object = new Authentication($this);

        // Patch for PHP 5.2.0, they don't have lcFirst
        if (!function_exists("lcFirst")) {
            require_once($this->getUriLibraries() . "function_lcfirst.php");
        }
    }

    /**
     * Returns the full title that should be displayed at the top of this page.
     * @return string The title.
     */
    public function getSiteTitle() {
        return $this->site_title;
    }

    /**
     * Returns the current page id, like "article" or "account_management". Can
     * be converted to an url/uri using the get_ur*_page methods.
     */
    public function getPageId() {
        return $this->current_page_id;
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
        return $this->current_page_title;
    }

    /**
     * Returns the current page type: HOME, NORMAL or BACKSTAGE.
     * @return string The current page type.
     */
    public function getPageType() {
        return $this->current_page_type;
    }

    public function registerPage(Page $page) {
        $this->currentPage = $page;
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
        return $this->themes_object;
    }

    /**
     * Gets the authentication object.
     * @return Authentication The authentication object.
     */
    public function getAuth() {
        return $this->authentication_object;
    }

    // SITEVARS

    /**
     * Gets a setting from either the options.php or the settings table.
     * @param string $name Name of the setting.
     * @return mixed false if not found, otherwise the value.
     */
    public function getSiteSetting($name) {
        if (isSet($this->config[$name])) {
            $value = $this->config[$name];
            if (strtolower($value) == "false") {
                // Because "false" == true
                return false;
            }
            return $value;
        } else {
            return false;
        }
    }

    /**
     * Changes a setting. Saves a setting to the database. Does nothing if the setting is unchanged.
     * @param string $name The name of the setting.
     * @param string $value The value of the setting.
     */
    public function setSiteSetting($name, $value) {
        if (isSet($this->config[$name]) && $this->config[$name] == $value) {
            // No need to update
            return;
        }

        // Apply on current page
        $this->config[$name] = $value;

        // Save to database
        $oDB = $this->getDatabase();
        if (isSet($this->config[$name])) {
            // Update setting
            $sql = "UPDATE `settings` SET ";
            $sql.= "`setting_value` = '{$oDB->escapeData($value)}' ";
            $sql.= "WHERE `setting_name` = '{$oDB->escapeData($name)}'";
            $oDB->query($sql);
        } else {
            // New setting
            $sql = "INSERT INTO `settings` (`setting_name`, `setting_value`) ";
            $sql.= " VALUES ('{$oDB->escapeData($name)}', ";
            $sql.= "'{$oDB->escapeData($value)}')";
            $oDB->query($sql);
        }
    }

    // Paths

    /** Returns the path of the library directory */
    public function getUriLibraries() {
        return $this->getSiteSetting('uri') . "library/";
    }
    
    /** Returns the path of the config directory */
    public function getUriConfigs() {
        return $this->getSiteSetting('uri') . "config/";
    }
    
    /** Returns the path of all default controllers, models, pages and views */
    public function getUriApplication() {
        return $this->getSiteSetting('uri') . "application/";
    }

    /** Returns the path of all pages */
    public function getUriPages() {
        return $this->getUriApplication() . "pages/";
    }

    /** Returns the main site url. Other urls start with this */
    public function getUrlMain() {
        return $this->getSiteSetting('url');
    }

    /** Returns the site root directory */
    public function getUriMain() {
        return $this->getSiteSetting('uri');
    }
    
    /** Returns the url of the public content directory of this site */
    public function getUrlContent() {
        return $this->getSiteSetting('url');
    }
    
    /** Returns the internal uri of the public content directory */
    public function getUriContent() {
        return $this->getSiteSetting('uri') . "content/";
    }

    /** Returns the url of a page, ready for links */
    public function getUrlPage($name, $id = -1337, $args = array()) {
        if ($id == -1337 && count($args) == 0) { // just the page name
            return $this->getUrlMain() . $name;
        } else { // also the other arguments
            if (count($args) == 0)
                return $this->getUrlMain() . $name . "/" . $id; //geen andere variabelen, geef weer als example.com/naam/id
            else { //wel andere variabelen
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
            return $this->getUriPages() . $name . ".php";
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
        return $this->getUriConfigs() . "widgets/";
    }

    public function getUriTranslations() {
        return $this->getUriConfigs() . "translations/";
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
        if ($this->getSiteSetting('password') == "")
            $access = true;
        if (isSet($_POST['key']) && $this->getSiteSetting('password') == $_POST['key'])
            $access = true;
        if (isSet($_GET['key']) && $this->getSiteSetting('password') == $_GET['key'])
            $access = true;
        if (isSet($_COOKIE['key']) && $this->getSiteSetting('password') == $_COOKIE['key'])
            $access = true;

        return $access;
    }

    /**
     * Echoes the whole page.
     */
    public function echoPage() {
        // Rewrite view_url to p and id
        if(isset($_GET["view_url"])) {
            $split = explode("/", $_GET["view_url"], 2);
            $_REQUEST["p"] = $_POST["p"] = $_GET["p"] = $split[0];
            if(count($split) == 2) {
                $_REQUEST["id"] = $_POST["id"] = $_GET["id"] = $split[1];
            }
        }
        
        // Check for site password
        if ($this->hasAccess()) {
            // Site title
            $this->site_title = $this->getSiteSetting('title');

            // Get id of page to display
            $given_page_id = $this->getRequestString("p", "home");
            if ($given_page_id != 'home') {
                // Get current page title and id 
                $this->current_page_id = $given_page_id;

                if (!file_exists($this->getUriPage($this->current_page_id))) {
                    // Page doesn't exist, show error and redirect
                    $this->addError($this->t("main.page") . " '" . htmlSpecialChars($this->current_page_id) . "' " . $this->t('errors.not_found'));
                    $this->current_page_id = 'home';
                }
            } else {
                // No page id given
                $this->current_page_id = 'home';
            }

            // Set cookie
            if (strLen($this->getSiteSetting('password')) != 0) {
                setcookie("key", $this->getSiteSetting('password'), time() + 3600 * 24 * 365, "/");
            }

            // Perform page logic (supporting both the old .inc and the new .php pages)
            $uri = $this->getUriPage($this->current_page_id);
            if (substr($uri, -4) == ".php") {
                // We're on the new page system
                require($uri);
                // Page title
                $this->current_page_title = $this->currentPage->getPageTitle($this);
                if ($this->getSiteSetting('append_page_title')) {
                    $this->site_title.= ' - ' . $this->currentPage->getShortPageTitle($this);
                }
                // Page type
                $this->current_page_type = $this->currentPage->getPageType();
                // Authentication stuff
                $rank = (int) $this->currentPage->getMinimumRank($this);
                if ($rank >= 0) {
                    $oAuth = $this->getAuth();
                    if (!$oAuth->check($rank, false)) {
                        $this->authentication_failed_rank = $rank;
                    }
                }
                // Call init methord
                $this->currentPage->init($this);
            } else {
                // Old page system
                // Page title
                $this->current_page_title = ucfirst(str_replace('_', ' ', $this->current_page_id));
                if ($this->getSiteSetting('append_page_title')) {
                    $this->site_title.= ' - ' . $this->current_page_title;
                }
                // Page type
                switch ($this->current_page_id) {
                    case "home":
                        $this->current_page_type = "HOME";
                        break;
                    case "category":
                    case "search":
                    case "article":
                    case "view_article":
                    case "archive":
                    case "calendar":
                        $this->current_page_type = "NORMAL";
                        break;
                    default:
                        $this->current_page_type = "BACKSTAGE";
                        break;
                }
            }

            // Output page
            $this->themes_object = new Themes($this);
            $this->themes_object->output();
        } else {
            // Echo site code page
            require($this->getUriLibraries() . 'login_page.php');
        }
    }

    /**
     * Echoes only the main content of the page, without any clutter.
     */
    public function echoPageContent() { //geeft de hoofdpagina weer
        if ($this->hasAccess()) {
            // Locales
            setlocale(LC_ALL, explode("|", $this->t("main.locales")));

            if ($this->currentPage != null) {
                // New page system
                // Title
                $title = $this->currentPage->getPageTitle($this);
                if (!empty($title)) {
                    echo "<h2>" . $title . "</h2>\n";
                }

                // Get page content (based on permissions)
                $textToDisplay = "";
                if ($this->authentication_failed_rank >= 0) {
                    $textToDisplay = $this->authentication_object->getLoginForm($this->authentication_failed_rank);
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
                require($this->getUriPage($this->current_page_id));
            }
        }
    }

    public function isLoggedIn() {
        return $this->getAuth()->getCurrentUser() != null;
    }

    public function isLoggedInAsStaff($admin = false) {
        $needed_rank = Authentication::$MODERATOR_RANK;
        if ($admin) {
            $needed_rank = Authentication::$ADMIN_RANK;
        }
        $oAuth = $this->getAuth();
        $user = $oAuth->getCurrentUser();
        if ($user != null && $oAuth->isHigherOrEqualRank($user->getRank(), $needed_rank)) {
            return true;
        } else {
            return false;
        }
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

    protected function readSiteSettingsFromFile() {
        // Apply some standard settings to get the site running (in case those
        // cannot be loaded from the database)
        $this->config["language"] = "en";
        $this->config["theme"] = "rkok";
        $this->config["title"] = "Welcome!";

        // Load other settings from config.php (required)
        $file = "../config/config.php";
        if (file_exists($file)) {
            require($file);
        } else {
            echo "<code>" . $file . "</code> was not found! Please create a config file.";
            die();
        }
    }

    protected function readSiteSettingsFromDatabase() {
        // Load settings from the database
        $oDatabase = $this->getDatabase();
        $result = $oDatabase->query("SELECT `setting_name`, `setting_value` FROM `settings`", false);
        if ($result) {
            while (list($key, $value) = $oDatabase->fetchNumeric($result)) {
                $this->config[$key] = $value;
            }
        }
    }

    // TRANSLATIONS

    public function t($key) {
        $keys = explode(".", $key, 2);
        if (isSet($this->translations[$keys[0]])) { //al geladen
            return $this->translations[$keys[0]][$keys[1]];
        } else { //moet nog geladen worden
            $translationsFile = $this->getUriTranslations() . $this->getSiteSetting("language") . "/translations_" . $keys[0] . ".txt";
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

?>