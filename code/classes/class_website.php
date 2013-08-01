<?php

class Website {

    const MAX_SITE_OPTION_LENGTH = 200;

    protected $errors = array();
    protected $debug = false;
    protected $errorsdisplayed = false;
    protected $database_object;

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
    protected $current_page; // Available during/after echo_page
    protected $authentication_failed_rank = -1; // Number of required rank which the user didn't have, or -1 if the user's rank is already high enough

    /**
     * Constructs the Website. Page- and theme-specific logic won't be loaded yet.
     */

    function __construct() {
        // Site settings and database connection
        $this->site_settings_file();
        $this->database_object = new Database($this);
        $this->site_settings_database();

        $this->authentication_object = new Authentication($this);

        // Patch for PHP 5.2.0, they don't have lcfist
        if (!function_exists("lcfirst")) {
            require_once($this->get_uri_scripts() . "function_lcfirst.php");
        }
    }

    /**
     * Returns the full title that should be displayed at the top of this page.
     * @return string The title.
     */
    public function get_site_title() {
        return $this->site_title;
    }

    /**
     * Returns the current page id, like "article" or "account_management". Can
     * be converted to an url/uri using the get_ur*_page methods.
     */
    public function get_page_id() {
        return $this->current_page_id;
    }

    /**
     * Returns the current page. Only works with the new page system.
     * @return Page The current page.
     */
    public function get_page() {
        return $this->current_page;
    }

    /**
     * Returns a shorter title of this page that can be used in breadcrumbs.
     * @return string The shorter title.
     */
    public function get_page_title() {
        return $this->current_page_title;
    }

    /**
     * Returns the current page type: HOME, NORMAL or BACKSTAGE.
     * @return string The current page type.
     */
    public function get_page_type() {
        return $this->current_page_type;
    }

    public function register_page(Page $page) {
        $this->current_page = $page;
    }

    // GETTING OTHER OBJECTS

    /**
     * Returns the database of this site
     * @return Database The database
     */
    public function get_database() {
        return $this->database_object;
    }

    /**
     * Gets the theme manager. Returns null if the theme hasn't been loaded yet
     * (before echo_page is called).
     * @return Themes The theme manager.
     */
    public function get_theme_manager() {
        return $this->themes_object;
    }

    /**
     * Gets the authentication object.
     * @return Authentication The authentication object.
     */
    public function get_authentication() {
        return $this->authentication_object;
    }

    // SITEVARS

    /**
     * Gets a setting from either the options.php or the settings table.
     * @param string $name Name of the setting.
     * @return mixed false if not found, otherwise the value.
     */
    public function get_sitevar($name) {
        if (isset($this->config[$name])) {
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
    public function set_sitevar($name, $value) {
        if (isset($this->config[$name]) && $this->config[$name] == $value) {
            // No need to update
            return;
        }

        // Apply on current page
        $this->config[$name] = $value;

        // Save to database
        $oDB = $this->get_database();
        if (isset($this->config[$name])) {
            // Update setting
            $sql = "UPDATE `settings` SET ";
            $sql.= "`setting_value` = '{$oDB->escape_data($value)}' ";
            $sql.= "WHERE `setting_name` = '{$oDB->escape_data($name)}'";
            $oDB->query($sql);
        } else {
            // New setting
            $sql = "INSERT INTO `settings` (`setting_name`, `setting_value`) ";
            $sql.= " VALUES ('{$oDB->escape_data($name)}', ";
            $sql.= "'{$oDB->escape_data($value)}')";
            $oDB->query($sql);
        }
    }

//Alle paden hier
    //Geeft de map van de scripts terug als url
    public function get_url_scripts() {
        return $this->get_sitevar('url') . "code/";
    }

    //Geeft de map van de scripts terug als uri
    public function get_uri_scripts() {
        return $this->get_sitevar('uri') . "code/";
    }

    //Geeft de map van alle modules terug als url
    public function get_url_modules() {
        return $this->get_url_scripts() . "pages/";
    }

    //Geeft de map van alle modules terug als uri
    public function get_uri_modules() {
        return $this->get_uri_scripts() . "pages/";
    }

    //Geeft de url van de hoofdpagina terug, zoals example.com
    public function get_url_main() {
        return $this->get_sitevar('url');
    }

    public function get_uri_main() {
        return $this->get_sitevar('uri');
    }

    public function get_url_page($name, $id = -1337, $args = array()) {
        if ($this->get_sitevar("fancy_urls")) {
            if ($id == -1337 && count($args) == 0) { // just the page name
                return $this->get_url_main() . $name;
            } else { // also the other arguments
                if (count($args) == 0)
                    return $this->get_url_main() . $name . "/" . $id; //geen andere variabelen, geef weer als example.com/naam/id
                else { //wel andere variabelen
                    $url = $this->get_url_main() . "index.php?p=" . $name . "&amp;id=" . $id;
                    foreach ($args as $key => $value)
                        $url.="&amp;$key=" . urlencode($value);
                    return $url;
                }
            }
        } else {
            if ($id == -1337 && count($args) == 0) { // just the page name
                return $this->get_url_main() . "index.php?p=" . $name;
            } else { // also the other arguments
                if (count($args) == 0)
                    return $this->get_url_main() . "index.php?p=" . $name . "&amp;id=" . $id; //geen andere variabelen, geef weer als example.com/naam/id
                else {
                    $url = $this->get_url_main() . "index.php?p=" . $name . "&amp;id=" . $id;
                    foreach ($args as $key => $value)
                        $url.="&amp;$key=" . urlencode($value);
                    return $url;
                }
            }
        }
    }

    public function get_uri_page($name) {
        // Has to account for both the old .inc pages and the newer .php pages
        // Because file_exists lookups are cached, this shouldn't really affect
        // performance.
        $uri_old = $this->get_uri_modules() . $name . ".inc";
        if (file_exists($uri_old)) {
            return $uri_old;
        } else {
            return $this->get_uri_modules() . $name . ".php";
        }
    }

    //Geeft de map van alle thema's terug als url
    public function get_url_themes() {
        return $this->get_url_scripts() . "themes/";
    }

    //Geeft de map van alle thema's terug als uri
    public function get_uri_themes() {
        return $this->get_uri_scripts() . "themes/";
    }

    //Geeft de map van alle widgets terug als url
    public function get_url_widgets() {
        return $this->get_url_scripts() . "widgets/";
    }

    //Geeft de map van alle widgets terug als uri
    public function get_uri_widgets() {
        return $this->get_uri_scripts() . "widgets/";
    }

    public function get_uri_translations() {
        return $this->get_uri_scripts() . "translations/";
    }

//Einde paden

    public function add_error($message, $public_message = false) {
        if ($this->debug || !$public_message) { //foutmelding alleen weergeven als melding ongevaarlijk is of als debuggen aan is gezet
            $this->errors[count($this->errors)] = $message;
        } else {
            $this->errors[count($this->errors)] = $public_message;
        }
        if ($this->errorsdisplayed) {//geef ook nieuwe foutmeldingen weer, als normale al weergegeven zijn
            $this->echo_errors();
        }
    }

    public function error_count() {
        return count($this->errors);
    }

    public function echo_errors() { //geeft alle foutmeldingen weer
        $this->errorsdisplayed = true;

        $error_count = count($this->errors); //totaal aantal foutmeldingen
        if ($error_count == 0) {
            return true;
        } elseif ($error_count == 1) {
            echo '<div class="error"><h3>' . $this->t("errors.error_occured") . '</h3>';
            echo $this->errors[0];
            echo '</div>';
        } else {
            echo '<div class="error">';
            echo "   <h3>" . str_replace("#", $error_count, $this->t('errors.errors_occured')) . "</h3>";
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

    function has_access() { //kijkt of site mag worden geladen
        $access = false;
        if ($this->get_sitevar('password') == "")
            $access = true;
        if (isset($_POST['key']) && $this->get_sitevar('password') == $_POST['key'])
            $access = true;
        if (isset($_GET['key']) && $this->get_sitevar('password') == $_GET['key'])
            $access = true;
        if (isset($_COOKIE['key']) && $this->get_sitevar('password') == $_COOKIE['key'])
            $access = true;

        return $access;
    }

    /**
     * Echoes the whole page.
     */
    public function echo_page() {
        // Check for site password
        if ($this->has_access()) {
            // Site title
            $this->site_title = $this->get_sitevar('title');

            // Get id of page to display
            $given_page_id = $this->get_request_string("p", "home");
            if ($given_page_id != 'home') {
                // Get current page title and id 
                $this->current_page_id = $given_page_id;

                if (!file_exists($this->get_uri_page($this->current_page_id))) {
                    // Page doesn't exist, show error and redirect
                    $this->add_error($this->t("main.page") . " '" . $this->current_page_id . "' " . $this->t('errors.not_found'));
                    $this->current_page_id = 'home';
                }
            } else {
                // No page id given
                $this->current_page_id = 'home';
            }

            // Set cookie
            if (strlen($this->get_sitevar('password')) != 0) {
                setcookie("key", $this->get_sitevar('password'), time() + 3600 * 24 * 365, "/");
            }

            // Perform page logic (supporting both the old .inc and the new .php pages)
            $uri = $this->get_uri_page($this->current_page_id);
            if (substr($uri, -4) == ".php") {
                // We're on the new page system
                require($uri);
                // Page title
                $this->current_page_title = $this->current_page->get_page_title($this);
                if ($this->get_sitevar('append_page_title')) {
                    $this->site_title.= ' - ' . $this->current_page->get_short_page_title($this);
                }
                // Page type
                $this->current_page_type = $this->current_page->get_page_type();
                // Authentication stuff
                $rank = (int) $this->current_page->get_minimum_rank($this);
                if ($rank >= 0) {
                    $oAuth = $this->get_authentication();
                    if (!$oAuth->check($rank, false)) {
                        $this->authentication_failed_rank = $rank;
                    }
                }
                // Call init methord
                $this->current_page->init($this);
            } else {
                // Old page system
                // Page title
                $this->current_page_title = ucfirst(str_replace('_', ' ', $this->current_page_id));
                if ($this->get_sitevar('append_page_title')) {
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
            require($this->get_uri_scripts() . 'login_page.php');
        }
    }

    /**
     * Echoes only the main content of the page, without any clutter.
     */
    public function echo_page_content() { //geeft de hoofdpagina weer
        if ($this->has_access()) {
            // Locales
            setlocale(LC_ALL, explode("|", $this->t("main.locales")));

            if ($this->current_page != null) {
                // New page system
                // Title
                $title = $this->current_page->get_page_title($this);
                if (!empty($title)) {
                    echo "<h2>" . $title . "</h2>\n";
                }

                // Get page content (based on permissions)
                $text_to_display = "";
                if ($this->authentication_failed_rank >= 0) {
                    $text_to_display = $this->authentication_object->get_login_form($this->authentication_failed_rank);
                } else {
                    $text_to_display = $this->current_page->get_page_content($this);
                }

                // Echo errors
                if (!$this->errorsdisplayed) {
                    $this->echo_errors();
                }

                // Display page content
                echo $text_to_display;
            } else {
                // Old page system
                require($this->get_uri_page($this->current_page_id));
            }
        }
    }

    public function logged_in() {
        return $this->get_authentication()->get_current_user() != null;
    }

    public function logged_in_staff($admin = false) {
        $needed_rank = Authentication::$MODERATOR_RANK;
        if ($admin) {
            $needed_rank = Authentication::$ADMIN_RANK;
        }
        $oAuth = $this->get_authentication();
        $user = $oAuth->get_current_user();
        if ($user != null && $oAuth->is_higher_or_equal_rank($user->get_rank(), $needed_rank)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns the id of the user currently logged in. Returns -1 if the user isn't logged in.
     * @return int The id of the user currently logged in.
     */
    public function get_current_user_id() {
        $user = $this->get_authentication()->get_current_user();
        if ($user == null) {
            return -1;
        } else {
            return $user->get_id();
        }
    }

    /**
     * Returns the number of sidebars that this theme supports. Won't work
     * if echo_page is not yet called.
     * @return int The number of sidebars.
     */
    public function get_theme_widget_area_count() {
        // Defined sidebars plus one for the homepage
        return count($this->get_theme_manager()->get_theme()->get_widget_areas($this)) + 1;
    }

    protected function site_settings_file() {
        // Apply some standard settings to get the site running (in case those
        // cannot be loaded from the database)
        $this->config["language"] = "en";
        $this->config["theme"] = "rkok";
        $this->config["title"] = "Welcome!";

        // Load other settings from config.php (required)
        if (file_exists('config.php')) {
            require('config.php');
        } else {
            echo "<code>config.php</code> was not found! Place it next to your index.php";
            die();
        }
    }

    protected function site_settings_database() {
        // Load settings from the database
        $oDatabase = $this->get_database();
        $result = $oDatabase->query("SELECT `setting_name`, `setting_value` FROM `settings`", false);
        if ($result) {
            while (list($key, $value) = $oDatabase->fetch($result)) {
                $this->config[$key] = $value;
            }
        }
    }

    // TRANSLATIONS

    public function t($key) {
        $keys = explode(".", $key, 2);
        if (isset($this->translations[$keys[0]])) { //al geladen
            return $this->translations[$keys[0]][$keys[1]];
        } else { //moet nog geladen worden
            $translations_file = $this->get_uri_translations() . $this->get_sitevar("language") . "/translations_" . $keys[0] . ".txt";
            if (file_exists($translations_file)) { //laad
                $file_contents = file($translations_file);
                foreach ($file_contents as $line) {
                    $translation = explode("=", $line, 2);
                    $this->translations[$keys[0]][$translation[0]] = trim($translation[1]);
                }
                unset($file_contents);

                //en geef juiste waarde terug
                return $this->translations[$keys[0]][$keys[1]];
            } else { //foutmelding
                echo "<br /><br /><code>$translations_file</code> was not found!";
                die();
            }
        }
    }

    public function t_replaced_key($key, $replace_in_key, $lowercase = false) {
        if ($lowercase) {
            return str_replace("#", strtolower($this->t($replace_in_key)), $this->t($key));
        } else {
            return str_replace("#", $this->t($replace_in_key), $this->t($key));
        }
    }

    public function t_replaced($key, $replace_in_key, $lowercase = false) {
        if ($lowercase) {
            return str_replace("#", strtolower($replace_in_key), $this->t($key));
        } else {
            return str_replace("#", $replace_in_key, $this->t($key));
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
    public function get_request_string($name, $default = "") {
        if (isset($_REQUEST[$name])) {
            if (ini_get("magic_quotes_gpc")) {
                return stripslashes($_REQUEST[$name]);
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
    public function get_request_int($name, $default = 0) {
        if (isset($_REQUEST[$name])) {
            if (is_numeric($_REQUEST[$name])) {
                return (int) $_REQUEST[$name];
            }
        }
        return $default;
    }

}

?>