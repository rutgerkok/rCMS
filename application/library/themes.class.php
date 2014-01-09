<?php

class Themes {

    private $websiteObject; //bewaart het website-object
    private $widgets_object; //bewaart het widgets-object
    private $theme;

    public function __construct(Website $oWebsite) {
        $this->websiteObject = $oWebsite;
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
    public function registerTheme(Theme $theme) {
        $this->theme = $theme;
    }

    /**
     * Echoes three &lt;li&gt; links representing the accounts menu.
     */
    public function echo_accounts_menu() {
        $oWebsite = $this->websiteObject;

        if ($oWebsite->isLoggedInAsStaff(true)) {
            // Logged in as admin
            echo '<li><a href="' . $oWebsite->getUrlPage("admin") . '">' . $oWebsite->t("main.admin") . '</a></li>';
        }
        if ($oWebsite->isLoggedIn()) {
            // Logged in
            echo '<li><a href="' . $oWebsite->getUrlPage("account") . '">' . $this->websiteObject->t("main.my_account") . '</a></li>';
            echo '<li><a href="' . $oWebsite->getUrlPage("log_out") . '">' . $this->websiteObject->t("main.log_out") . '</a></li>';
        } else {
            // Not logged in
            if ($oWebsite->getConfig()->get("user_account_creation")) {
                // Show account creation link
                echo '<li><a href="' . $oWebsite->getUrlPage("create_account") . '">' . $this->websiteObject->t("main.create_account") . '</a></li>';
            }
            echo '<li><a href="' . $oWebsite->getUrlPage("log_in") . '">' . $this->websiteObject->t("main.log_in") . '</a></li>';
        }
    }

    public function echo_account_label() {
        $oWebsite = $this->websiteObject;
        $user = $oWebsite->getAuth()->getCurrentUser();

        // Get welcome text
        if ($user == null) {
            // Logged out
            $welcome_text = $oWebsite->t("main.welcome_guest") . " ";
            $welcome_text.= '<a class="arrow" href="' . $oWebsite->getUrlPage("log_in") . '">';
            $welcome_text.= $oWebsite->t("main.log_in") . "</a>\n";
        } else {
            // Logged in
            $display_name = htmlSpecialChars($user->getDisplayName());
            $welcome_text = <<<EOT
                                <a class="user_welcome_link" href="{$oWebsite->getUrlPage("account")}">
                                    {$oWebsite->tReplaced("main.welcome_user", $display_name)}
                                    <span class="username">(@{$user->getUsername()})</span>
                                </a>
EOT;
        }
        echo "<p>" . $welcome_text . "</p>";
    }

    public function echo_account_box($gravatar_size = 140) {
        $oWebsite = $this->websiteObject;
        $user = $oWebsite->getAuth()->getCurrentUser();

        if ($user == null) {
            // Nothing to display
            return;
        }

        // Get avatar url
        $avatar_url = $user->getAvatarUrl($gravatar_size);

        // Display account box
        echo '<img id="account_box_gravatar" src="' . $avatar_url . '" />';
        echo '<ul>';
        echo $this->echo_accounts_menu();
        echo '</ul>';
    }

    public function echo_breadcrumbs() {
        $oWebsite = $this->websiteObject;

        echo <<<EOT
			<a href="http://www.leiden.edu/" class="first">Leiden University</a>
			<a href="http://www.research.leiden.edu/">Research Portal</a>
			<a href="http://www.research.leiden.edu/research-profiles/">Leiden Research Profiles</a>
			<a href="{$oWebsite->getUrlMain()}">Datascience</a>
EOT;
        // Nog de laatste link?
        if ($oWebsite->getPageId() != 'home') {
            if ($oWebsite->getPage() != null) {
                echo ' <a href="#">' . $oWebsite->getPage()->getShortPageTitle($oWebsite) . '</a>';
            } else {
                echo ' <a href="#">' . $oWebsite->getPageTitle() . '</a>';
            }
        }
    }

    public function echo_copyright() {
        echo $this->websiteObject->getConfig()->get("copyright");
    }

    public function echo_menu() {
        $oWebsite = $this->websiteObject; //afkorting
        $oMenu = new Menus($oWebsite);
        echo $oMenu->get_as_html($oMenu->get_menu_top(new Categories($oWebsite, $oWebsite->getDatabase())));
        unset($oMenu);
    }

    /**
     * Called by theme. Echoes the page content.
     */
    public function echoPageContent() {
        $this->websiteObject->echoPageContent();
    }

    //Geeft een zoekformulier weer
    public function echo_search_form() {
        $oWebsite = $this->websiteObject;

        //Zoekwoord
        $keyword = "";
        if (isSet($_REQUEST['searchbox']))
            $keyword = htmlSpecialChars($_REQUEST['searchbox']);

        echo '<form id="searchform" name="searchform" action="' . $oWebsite->getUrlMain() . '" method="get">';
        echo '<input type="hidden" name="p" value="search" />';
        echo '<input type="search" size="21" name="searchbox" id="searchbox" value="' . $keyword . '" />';
        echo '<input type="submit" class="button" value="' . $oWebsite->t("main.search") . '" name="searchbutton" id="searchbutton" />';
        echo '</form>';
    }

    public function echoWidgets($area) {
        echo $this->getWidgets($area);
    }

    public function getWidgets($area) {
        $oWidgets = $this->widgets_object;
        return $oWidgets->getWidgetsSidebar($area);
    }

    /**
     * Returns whether the user viewing the page is logged in.
     * @return boolean True if logged in, false otherwise.
     */
    public function isLoggedIn() {
        return $this->websiteObject->isLoggedIn();
    }

    //Geeft de titel terug die boven aan de pagina, in de header, moet worden weergegeven. De paginatitel zit ingesloten in echo_page()
    public function getSiteTitle() {
        return $this->websiteObject->getSiteTitle();
    }

    //Geeft het type pagina terug, "HOME", "NORMAL" of "BACKSTAGE"
    public function getPageType() {
        return $this->websiteObject->getPageType();
    }

    //Geeft de map van de scripts terug als uri
    public function getUriLibraries() {
        return $this->websiteObject->getUriLibraries();
    }

    public function getUrlJavaScripts() {
        return $this->websiteObject->getUrlJavaScripts();
    }

    public function get_uri_theme() {
        return $this->websiteObject->getUriThemes() . $this->websiteObject->getConfig()->get("theme") . "/";
    }

    public function get_url_theme() {
        return $this->websiteObject->getUrlThemes() . $this->websiteObject->getConfig()->get("theme") . "/";
    }

}

?>