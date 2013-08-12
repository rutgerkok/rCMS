<?php

class Themes {

    private $website_object; //bewaart het website-object
    private $widgets_object; //bewaart het widgets-object
    private $theme;

    public function __construct(Website $oWebsite) {
        $this->website_object = $oWebsite;
        $this->widgets_object = new Widgets($oWebsite);
    }

    /**
     * Called by Website. Echoes the whole page.
     */
    public function output() {
        if (file_exists($this->get_uri_theme() . "main.php")) {
            require($this->get_uri_theme() . "main.php");
        } else {
            die("<code>" . $this->get_uri_theme() . "main.php</code> was not found! Theme is missing/incomplete.");
        }
    }

    /**
     * Normall called by Website. Gets the page object
     * @return Theme The page.
     */
    public function get_theme() {
        if (!$this->theme) {
            if (file_exists($this->get_uri_theme() . "options.php")) {
                require($this->get_uri_theme() . "options.php");
            } else {
                die("<code>" . $this->get_uri_theme() . "options.php</code> was not found! Theme is missing/incomplete.");
            }
        }
        return $this->theme;
    }

    /**
     * Used by a theme (in it's options.php) to register itself.
     * @param Theme $theme The theme to register.
     */
    public function register_theme(Theme $theme) {
        $this->theme = $theme;
    }

    /**
     * Echoes three &lt;li&gt; links representing the accounts menu.
     */
    public function echo_accounts_menu() {
        $oWebsite = $this->website_object;

        if ($oWebsite->logged_in_staff(true)) {
            // Logged in as admin
            echo '<li><a href="' . $oWebsite->get_url_page("admin") . '">' . $oWebsite->t("main.admin") . '</a></li>';
        }
        if ($oWebsite->logged_in()) {
            // Logged in
            echo '<li><a href="' . $oWebsite->get_url_page("account") . '">' . $this->website_object->t("main.my_account") . '</a></li>';
            echo '<li><a href="' . $oWebsite->get_url_page("log_out") . '">' . $this->website_object->t("main.log_out") . '</a></li>';
        } else {
            // Not logged in
            if ($oWebsite->get_sitevar("user_account_creation")) {
                // Show account creation link
                echo '<li><a href="' . $oWebsite->get_url_page("create_account") . '">' . $this->website_object->t("main.create_account") . '</a></li>';
            }
            echo '<li><a href="' . $oWebsite->get_url_page("log_in") . '">' . $this->website_object->t("main.log_in") . '</a></li>';
        }
    }
    
    public function echo_account_label() {
        $oWebsite = $this->website_object;
        $user = $oWebsite->get_authentication()->get_current_user();
        
        // Get welcome text
        if($user == null) {
            // Logged out
            $welcome_text = $oWebsite->t("main.welcome_guest") . " ";
            $welcome_text.= '<a class="arrow" href="' . $oWebsite->get_url_page("log_in") . '">';
            $welcome_text.= $oWebsite->t("main.log_in") . "</a>\n";
        } else {
            // Logged in
            $welcome_text = $oWebsite->t_replaced("main.welcome_user", $user->get_display_name());
            $welcome_text.= ' <span class="username">(@' . $user->get_username() . ')</span>';
        }
        echo "<p>" . $welcome_text . "</p>";          
    }
    
    public function echo_account_box($gravatar_size = 140) {
        $oWebsite = $this->website_object;
        $user = $oWebsite->get_authentication()->get_current_user();
        
        if($user == null) {
            // Nothing to display
            return;
        }
        
        // Get avatar url
        $avatar_url = $user->get_avatar_url($gravatar_size);
        
        // Display account box
        echo '<img id="account_box_gravatar" src="' . $avatar_url . '" />';
        echo '<ul>';
        echo $this->echo_accounts_menu();
        echo '</ul>';
    }

    public function echo_breadcrumbs() {
        $oWebsite = $this->website_object;

        echo <<<EOT
			<a href="http://www.leiden.edu/" class="first">Leiden University</a>
			<a href="http://www.research.leiden.edu/">Research Portal</a>
			<a href="http://www.research.leiden.edu/research-profiles/">Leiden Research Profiles</a>
			<a href="{$oWebsite->get_url_main()}">Datascience</a>
EOT;
        // Nog de laatste link?
        if ($oWebsite->get_page_id() != 'home') {
            if($oWebsite->get_page() != null) {
                echo ' <a href="#">' . $oWebsite->get_page()->get_short_page_title($oWebsite) . '</a>';
            } else {
                echo ' <a href="#">' . $oWebsite->get_page_title() . '</a>';
            }
            
        }
    }

    public function echo_copyright() {
        echo $this->website_object->get_sitevar("copyright");
    }

    public function echo_menu() {
        $oWebsite = $this->website_object; //afkorting
        $oMenu = new Menus($oWebsite);
        echo $oMenu->get_as_html($oMenu->get_menu_top(new Categories($oWebsite, $oWebsite->get_database())));
        unset($oMenu);
    }

    /**
     * Called by theme. Echoes the page content.
     */
    public function echo_page_content() {
        $this->website_object->echo_page_content();
    }

    //Geeft een zoekformulier weer
    public function echo_search_form() {
        $oWebsite = $this->website_object;

        //Zoekwoord
        $keyword = "";
        if (isset($_REQUEST['searchbox']))
            $keyword = htmlspecialchars($_REQUEST['searchbox']);

        echo '<form id="searchform" name="searchform" action="' . $oWebsite->get_url_main() . '" method="get">';
        echo '<input type="hidden" name="p" value="search" />';
        echo '<input type="search" size="21" name="searchbox" id="searchbox" value="' . $keyword . '" />';
        echo '<input type="submit" class="button" value="' . $oWebsite->t("main.search") . '" name="searchbutton" id="searchbutton" />';
        echo '</form>';
    }

    public function echo_widgets($area) {
        $oWebsite = $this->website_object;

        $oWidgets = $this->widgets_object;
        $oWidgets->echo_widgets_sidebar($area);
    }
    
    /**
     * Returns whether the user viewing the page is logged in.
     * @return boolean True if logged in, false otherwise.
     */
    public function logged_in() {
        return $this->website_object->logged_in();
    }

    //Geeft de titel terug die boven aan de pagina, in de header, moet worden weergegeven. De paginatitel zit ingesloten in echo_page()
    public function get_site_title() {
        return $this->website_object->get_site_title();
    }

    //Geeft het type pagina terug, "HOME", "NORMAL" of "BACKSTAGE"
    public function get_page_type() {
        return $this->website_object->get_page_type();
    }

    //Geeft de map van de scripts terug als uri
    public function get_uri_scripts() {
        return $this->website_object->get_uri_scripts();
    }

    public function get_url_scripts() {
        return $this->website_object->get_url_scripts();
    }

    public function get_uri_theme() {
        return $this->website_object->get_uri_themes() . $this->website_object->get_sitevar("theme") . "/";
    }

    public function get_url_theme() {
        return $this->website_object->get_url_themes() . $this->website_object->get_sitevar("theme") . "/";
    }

}

?>