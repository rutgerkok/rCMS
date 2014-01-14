<?php

class Themes {

    const THEME_INFO_FILE_NAME = "info.txt";

    private $websiteObject; //bewaart het website-object
    private $widgetsObject; //bewaart het widgets-object
    private $theme;

    public function __construct(Website $oWebsite) {
        $this->websiteObject = $oWebsite;
        $this->widgetsObject = new Widgets($oWebsite);

        // Load theme info file
        $this->theme = $this->loadTheme($oWebsite->getConfig()->get("theme"));
    }

    /**
     * Loads the theme with the given directory name.
     * @param string $themeName The directory name, like "my_theme".
     * @return Theme The loaded theme.
     * @throws BadMethodCallException If no theme with that name exists.
     */
    private function loadTheme($themeName) {
        $themeDirectory = $this->websiteObject->getUriThemes() . $themeName . "/";
        $themeInfoFile = $themeDirectory . self::THEME_INFO_FILE_NAME;
        return new Theme($themeName, $themeInfoFile);
    }

    /**
     * Gets the theme with the given name.
     * @param string $directoryName Name of the directory of the theme,
     *  like "my_theme".
     * @return Theme|null The theme, or null if not found.
     */
    public function getTheme($directoryName) {
        if ($this->theme->getName() == $directoryName) {
            return $this->theme;
        }
        if ($this->themeExists($directoryName)) {
            return loadTheme($directoryName);
        }
        return null;
    }

    /**
     * Gets whether a theme with that name exists on this site.
     * @param string $directoryName Name of the directory of the theme, like
     *  "my_theme".
     * @return boolean Whether that theme exists.
     */
    public function themeExists($directoryName) {
        return is_dir($this->websiteObject->getUriThemes() . $directoryName);
    }

    /**
     * Called by Website. Echoes the whole page.
     */
    public function output() {
        if (file_exists($this->getUriTheme() . "main.php")) {
            require($this->getUriTheme() . "main.php");
        } else {
            die("<code>" . $this->getUriTheme() . "main.php</code> was not found! Theme is missing/incomplete.");
        }
    }

    /**
     * Gets the theme currently used on the site.
     * @return Theme The theme.
     */
    public function getCurrentTheme() {
        return $this->theme;
    }

    /**
     * Gets the uri of theme directory.
     * @param Theme $theme The theme to get the uri for, use null for the
     *  current theme.
     * @return string The uri.
     */
    public function getUriTheme($theme = null) {
        if ($theme == null) {
            $theme = $this->theme;
        }
        return $this->websiteObject->getUriThemes() . $theme->getName() . "/";
    }

    /**
     * Gets the url of theme directory.
     * @param Theme $theme The theme to get the url for, use null for the
     *  current theme.
     * @return string The uri.
     */
    public function getUrlTheme($theme = null) {
        if ($theme == null) {
            $theme = $this->theme;
        }
        return $this->websiteObject->getUrlThemes() . $theme->getName() . "/";
    }
    
    // Below this line are the methods for the individual themes to use to
    // display all the dynamic content on the page.

    /**
     * Gets the HTML of all widgets in the given widget area.
     * @param int $area The widget area, starting at 2. 1 is used for the
     *  widgets on the home page.
     * @return string The widgets.
     */
    public function getWidgetsHTML($area) {
        return $this->widgetsObject->getWidgetsHTML($area);
    }

    /**
     * Echoes three &lt;li&gt; links representing the accounts menu.
     */
    public function echoAccountsMenu() {
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

    public function echoAccountLabel() {
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

    public function echoAccountBox($gravatar_size = 140) {
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
        echo $this->echoAccountsMenu();
        echo '</ul>';
    }

    public function echoBreadcrumbs() {
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

    public function echoCopyright() {
        echo $this->websiteObject->getConfig()->get("copyright");
    }

    public function echoTopMenu() {
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
    public function echoSearchForm() {
        $oWebsite = $this->websiteObject;

        // Last entered search term
        $keyword = htmlSpecialChars($oWebsite->getRequestString("searchbox"));

        // Echo the form
        echo '<form id="searchform" name="searchform" action="' . $oWebsite->getUrlMain() . '" method="get">';
        echo '<input type="hidden" name="p" value="search" />';
        echo '<input type="search" size="21" name="searchbox" id="searchbox" value="' . $keyword . '" />';
        echo '<input type="submit" class="button" value="' . $oWebsite->t("main.search") . '" name="searchbutton" id="searchbutton" />';
        echo '</form>';
    }

    public function echoWidgets($area) {
        echo $this->widgetsObject->getWidgetsHTML($area);
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

}
