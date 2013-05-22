<?php

class Website {

    protected $errors = array();
    protected $debug = false;
    protected $errorsdisplayed = false;
    protected $database_object;
    protected $current_page_id;
    protected $current_page_title; // Site title [- page title]
    protected $current_page_title_short; // Based on page id
    protected $current_page_type; // NORMAL, NOWIDGETS or BACKSTAGE
    /** @var Themes $themes */
    private $themes;

    function __construct() {

        // Pagevars and settings
        $this->site_settings();
        setlocale(LC_ALL, $this->config['locales']);

        // Database
        $this->database_object = new Database($this);

        // Get page to display
        if (isset($_REQUEST['p']) && !empty($_REQUEST['p']) && $_REQUEST['p'] != 'home') {
            $this->current_page_id = $_REQUEST['p'];

            //Titel instellen
            $this->current_page_title = $this->get_sitevar('title'); //begin met alleen de naam van de site...
            $this->current_page_title_short = ucfirst(str_replace('_', ' ', $_REQUEST['p'])); //korte titel
            if ($this->get_sitevar('showpage'))
                $this->current_page_title.= ' - ' . $this->current_page_title_short; //...verleng eventueel met paginanaam

            if (!file_exists($this->get_uri_page($this->current_page_id))) {
                // Page doesn't exist, redirect
                $this->current_page_id = 'home';
                $this->add_error($this->t("main.page") . " '" . $this->current_page_title_short . "' " . $this->t('errors.not_found')); //en laat een foutmelding zien
            }
        } else {
            $this->current_page_id = 'home';
            //Titel instellen
            $this->current_page_title = $this->get_sitevar('hometitle');
            $this->current_page_title_short = 'Home';
        }

        // Get the layout of the page
        switch ($this->current_page_id) {
            case "home":
                $this->current_page_type = "NORMAL";
                break;
            case "category":
            case "search":
            case "article":
            case "view_article":
            case "archive":
            case "calendar":
                $this->current_page_type = "NOWIDGETS";
                break;
            default:
                $this->current_page_type = "BACKSTAGE";
                break;
        }

        // For some reason, some servers don't have this function.
        if (!function_exists("lcfirst")) {
            require_once("function_lcfirst.php");
        }
    }

    /**
     * Returns the full title that should be displayed at the top of this page.
     * @return string The title.
     */
    public function get_page_title() {
        return $this->current_page_title;
    }

    /**
     * Returns the current page id, like "article" or "account_management". Can
     * be converted to an url/uri using the get_ur*_page methods.
     */
    public function get_page_id() {
        return $this->current_page_id;
    }

    /**
     * Returns a shorter title of this page that can be used in breadcrumbs.
     * @return string The shorter title.
     */
    public function get_page_shorttitle() {
        return $this->current_page_title_short;
    }

    /**
     * Returns the current page type: NORMAL, NOWIDGETS or BACKSTAGE.
     * @return string The current page type.
     */
    public function get_page_type() {
        return $this->current_page_type;
    }

    /**
     * Returns the database of this site
     * @return Database The database
     */
    public function get_database() {
        return $this->database_object;
    }

    public function get_sitevar($var) {
        if (isset($this->config[$var])) {
            return($this->config[$var]);
        } else {
            return false;
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
        return $this->get_uri_modules() . $name . ".inc";
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

    public function error_clear_all() {
        unset($this->errors);
        $this->errors = array();
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
        $this->error_clear_all();
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
        if ($this->has_access()) { //geef de pagina weer
            setcookie("key", $this->get_sitevar('password'), time() + 3600 * 24 * 365, "/");
            $this->themes = new Themes($this);
            $this->themes->output();
        } else { //laat inlogscherm zien
            require($this->get_uri_scripts() . 'login_page.php');
        }
    }

    /**
     * Echoes only the main content of the page, without any clutter.
     */
    public function echo_page_content() { //geeft de hoofdpagina weer
        if ($this->has_access()) {
            if (file_exists($this->get_uri_page($this->current_page_id))) { //voeg de module in als die bestaat (al gecheckt in constructor)
                require($this->get_uri_page($this->current_page_id));
            }
        }
    }

    public function logged_in() {
        if (
                isset($_SESSION['id']) &&
                isset($_SESSION['user']) &&
                isset($_SESSION['pass']) &&
                isset($_SESSION['display_name']) &&
                isset($_SESSION['email']) &&
                isset($_SESSION['admin'])) {
            return true;
        }
    }

    public function logged_in_staff($admin = false) {
        if (
                isset($_SESSION['id']) &&
                isset($_SESSION['user']) &&
                isset($_SESSION['pass']) &&
                isset($_SESSION['display_name']) &&
                isset($_SESSION['email']) &&
                isset($_SESSION['admin']) &&
                ($_SESSION['admin'] == 0 || $_SESSION['admin'] == 1)) {
            if ($admin == false || $_SESSION['admin'] == 1) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the id of the user currently logged in. Returns -1 if the user isn't logged in.
     * @return int The id of the user currently logged in.
     */
    public function get_current_user_id() {
        return isset($_SESSION['id']) ? (int) $_SESSION['id'] : -1;
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

    /**
     * Gets the theme manager. Returns null if the theme hasn't been loaded yet
     * (before echo_page is called).
     * @return Themes The theme manager.
     */
    public function get_theme_manager() {
        return $this->themes;
    }

    public function site_settings() {
        //SITES INSTELLEN
        if (file_exists('config.php')) {
            require('config.php');
        } else {
            echo "<code>config.php</code> was not found! Place it next to your index.php";
            die();
        }
    }

    // TRANSLATIONS

    public function t($key) {
        $keys = explode(".", $key, 2);
        if (isset($this->translations[$keys[0]])) { //al geladen
            return $this->translations[$keys[0]][$keys[1]];
        } else { //moet nog geladen worden
            $translations_file = $this->get_uri_scripts() . "translations/" . $this->get_sitevar("language") . "/translations_" . $keys[0] . ".txt";
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

    /**
     * Gets a variable from the $_REQUEST array, without extra "magic quotes"
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

}

?>