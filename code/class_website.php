<?php

class Website {
    /*
     * ATTRIBUTEN:
     * 	$pagevars['title'] - (string) huidige paginatitel, gegenereerd aan de hand van $_REQUEST['p'] in de constructor
     * 	$pagevars['shorttitle'] - (string) kortere paginatitel (zonder Bioscience), gegenereerd aan de hand van $_REQUEST['p'] in de constructor
     *  $pagevars['file'] - (string) huidig paginabestand (zonder extensie of map)
     *  $pagevars['errors'] - (array) huidige paginafouten
     *  $pagevars['debug'] - (bool) geeft aan of alle foutmeldingen weergegeven moeten worden.
     *  $pagevars['database_object'] - (object) de databaseverbinding, opgeslagen door class_database.php
     *  $pagevars['site'] - (string) de geladen site, bioscience of phpark
     *  $pagevars['type'] - (string) het type pagina, "NORMAL", "NOWIDGETS" of "BACKSTAGE"
     *  $pagevars['local']
     */

    protected $pagevars = array();
    protected $errorsdisplayed = false;
    public /* final */ $IS_WEBSITE_OBJECT = true;
    
    function __construct() {
        
        // Pagevars and settings
        $this->pagevars['errors'] = array();
        $this->site_settings();
        setlocale(LC_ALL, $this->config['locales']);
        $this->pagevars['debug'] = true;
        $this->pagevars['database_object'] = null;
        
        // Database
        $this->get_database();

        // Get page to display
        if (isset($_REQUEST['p']) && !empty($_REQUEST['p']) && $_REQUEST['p'] != 'home') {
            $this->pagevars['file'] = $_REQUEST['p'];

            //Titel instellen
            $this->pagevars['title'] = $this->get_sitevar('title'); //begin met alleen de naam van de site...
            $this->pagevars['shorttitle'] = ucfirst(str_replace('_', ' ', $_REQUEST['p'])); //korte titel
            if ($this->get_sitevar('showpage'))
                $this->pagevars['title'].= ' - ' . $this->pagevars['shorttitle']; //...verleng eventueel met paginanaam

            if (!file_exists($this->get_uri_modules() . $this->pagevars['file'] . ".inc")) {
                $this->pagevars['file'] = 'home'; //bestaat pagina niet? dan naar homepage
                $this->add_error("Page '" . $this->pagevars['title'] . "' " . $this->t('errors.not_found')); //en laat een foutmelding zien
            }
        } else {
            $this->pagevars['file'] = 'home';
            //Titel instellen
            $this->pagevars['title'] = $this->get_sitevar('hometitle');
            $this->pagevars['shorttitle'] = 'Home';
        }

        // Get the layout of the page
        switch ($this->pagevars['file']) {
            case "home":
                $this->pagevars['type'] = "NORMAL";
                break;
            case "category":
            case "search":
            case "article":
            case "view_article":
            case "archive":
            case "calendar":
                $this->pagevars['type'] = "NOWIDGETS";
                break;
            default:
                $this->pagevars['type'] = "BACKSTAGE";
                break;
        }

        // For some reason, some servers don't have this function.
        if (!function_exists("lcfirst")) {
            require_once("function_lcfirst.php");
        }
    }

    public function set_pagevar($var, $value) {
        $this->pagevars[$var] = $value;
        return true;
    }

    public function get_pagevar($var) {
        return($this->pagevars[$var]);
    }

    /**
     * Returns the database of this site
     * @return Database The database
     */
    public function get_database() {
        if ($this->pagevars["database_object"] == null) {
            $this->pagevars["database_object"] = new Database($this);
        }
        return $this->pagevars["database_object"];
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

    public function get_url_page($name, $id = -1051414, $args = array()) {
        if ($id == -1051414) { //geen id
            return $this->get_url_main() . $name; //dus ook geen andere variabelen, geef weer als example.com/naam
        } else { //wel id
            if (count($args) == 0)
                return $this->get_url_main() . $name . "/" . $id; //geen andere variabelen, geef weer als example.com/naam/id
            else { //wel andere variabelen
                $url = $this->get_url_main() . "index.php?p=" . $name . "&amp;id=" . $id;
                foreach ($args as $key => $value)
                    $url.="&amp;$key=" . urlencode($value);
                return $url;
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
        if ($this->pagevars['debug'] || !$public_message) { //foutmelding alleen weergeven als melding ongevaarlijk is of als debuggen aan is gezet
            $this->pagevars['errors'][count($this->pagevars['errors'])] = $message;
        } else {
            $this->pagevars['errors'][count($this->pagevars['errors'])] = $public_message;
        }
        if ($this->errorsdisplayed) {//geef ook nieuwe foutmeldingen weer, als normale al weergegeven zijn
            $this->echo_errors();
        }
    }

    public function error_count() {
        return count($this->pagevars['errors']);
    }

    public function error_clear_all() {
        unset($this->pagevars['errors']);
        $this->pagevars['errors'] = array();
    }

    public function echo_errors() { //geeft alle foutmeldingen weer
        $this->errorsdisplayed = true;

        $errors = count($this->pagevars['errors']); //totaal aantal foutmeldingen
        if ($errors == 0) {
            return true;
        } elseif ($errors == 1) {
            echo '<div class="fout"><h3>' . $this->t("errors.error_occured") . '</h3>';
            echo $this->pagevars['errors'][0];
            echo '</div>';
        } else {
            echo '<div class="fout">';
            echo "   <h3>" . str_replace("#", $errors, $this->t('errors.errors_occured')) . "</h3>";
            echo '   <p>';
            echo '      <ul>';
            foreach ($this->pagevars['errors'] as $nr => $error) {
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

    //Laat de gehele pagina zien
    public function echo_page() {
        if ($this->has_access()) { //geef de pagina weer
            setcookie("key", $this->get_sitevar('password'), time() + 3600 * 24 * 365, "/");
            new Themes($this);
        } else { //laat inlogscherm zien
            require($this->get_uri_scripts() . 'login_page.php');
        }
    }

    public function echo_page_content() { //geeft de hoofdpagina weer
        if ($this->has_access()) {
            if (file_exists($this->get_uri_page($this->pagevars['file']))) { //voeg de module in als die bestaat (al gecheckt in constructor)
                require($this->get_uri_page($this->pagevars['file']));
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
            if($admin == false || $_SESSION['admin'] == 1) {
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
        return isset($_SESSION['id'])? (int) $_SESSION['id'] : -1;
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
        if($lowercase) {
            return str_replace("#", strtolower($this->t($replace_in_key)), $this->t($key));
        } else {
            return str_replace("#", $this->t($replace_in_key), $this->t($key));
        }
    }
    
    public function t_replaced($key, $replace_in_key, $lowercase = false) {
        if($lowercase) {
            return str_replace("#", strtolower($replace_in_key), $this->t($key));
        } else {
            return str_replace("#", $replace_in_key, $this->t($key));
        }
    }

}

?>