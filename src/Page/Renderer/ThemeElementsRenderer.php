<?php

namespace Rcms\Page\Renderer;

use Rcms\Core\CategoryRepository;
use Rcms\Core\LinkRepository;
use Rcms\Core\Theme;
use Rcms\Core\Website;
use Rcms\Page\HomePage;
use Rcms\Page\View\WidgetsView;

/**
 * Context in which a theme operates. All methods that a theme can use for
 * outputting things are located here.
 */
class ThemeElementsRenderer {

    /** @var Website The website instance. */
    private $website;

    /** @var Theme The theme used to render the page. */
    private $theme;

    /**  @var PageRenderer The renderer of the page. */
    private $pageRenderer;

    public function __construct(Website $website, Theme $theme,
            PageRenderer $pageRenderer) {
        $this->website = $website;
        $this->theme = $theme;
        $this->pageRenderer = $pageRenderer;
    }

    /**
     * Renders the page. Must not be called by themes.
     * @param string $file Path to the file.
     */
    public function render() {
        require($this->website->getThemeManager()->getThemeFile($this->theme));
    }

    /**
     * Gets the url of the directory of the theme used to render this page.
     * @return The url.
     */
    public function getUrlTheme() {
        return $this->website->getThemeManager()->getUrlTheme($this->theme);
    }

    /**
     * Gets the URL of the directory with all JavaScript files.
     * @return string The URL.
     */
    public function getUrlJavaScripts() {
        return $this->website->getUrlJavaScripts();
    }

    /**
     * Gets the title for in headers.
     * @return string The title.
     */
    public function getHeaderTitle() {
        return $this->pageRenderer->getHeaderTitle();
    }

    /**
     * Echoes only the main content of the page, without any clutter.
     */
    public function echoPageContent() {
        // Title
        $title = $this->pageRenderer->getPageTitle();
        if (!empty($title)) {
            echo "<h2>" . htmlSpecialChars($title) . "</h2>\n";
        }

        // Fetch content first
        $content = $this->pageRenderer->getMainContent();

        // Errors and confirmations
        $text = $this->website->getText();
        $this->echoList($text->getErrors(), "error");
        $this->echoList($text->getConfirmations(), "confirmation");

        // Display page content
        echo $content;
    }

    /**
     * Gets whether the user is currently logged in.
     * @return bool True if the user is logged in, false otherwise.
     */
    public function isLoggedIn() {
        return $this->website->isLoggedIn();
    }

    /**
     * Gets the type of this page: HOME, NORMAL or BACKSTAGE.
     * @return string The type.
     */
    public function getPageType() {
        return $this->pageRenderer->getPageType();
    }

    /**
     * @deprecated Use `echoList($text->getErrors(), "error")` instead.
     */
    protected function echoErrors() {
        $website = $this->website;
        $errors = $website->getText()->getErrors();
        $this->echoList($errors, "error");
    }

    private function echoList(array $messages, $cssClass) {
        $messageCount = count($messages);
        if ($messageCount == 0) {
            return;
        } elseif ($messageCount == 1) {
            echo <<<ERROR
                <div class="$cssClass">
                    <p>{$messages[0]}</p>
                </div>
ERROR;
        } else {
            $messages = "<li>" . join("</li><li>", $messages) . "</li>";
            echo <<<LIST
                <div class="$cssClass">
                    <p>
                        <ul>
                            $messages
                        </ul>
                    </p>
                </div>
LIST;
        }
    }

    /**
     * Echoes three &lt;li&gt; links representing the accounts menu.
     */
    public function echoAccountsMenu() {
        $website = $this->website;

        if ($website->isLoggedInAsStaff(true)) {
            // Logged in as admin
            echo '<li><a href="' . $website->getUrlPage("admin") . '">' . $website->t("main.admin") . '</a></li>';
        }
        if ($website->isLoggedIn()) {
            // Logged in
            echo '<li><a href="' . $website->getUrlPage("account") . '">' . $this->website->t("main.my_account") . '</a></li>';
            echo '<li><a href="' . $website->getUrlPage("logout") . '">' . $this->website->t("main.log_out") . '</a></li>';
        } else {
            // Not logged in
            if ($website->getConfig()->get("user_account_creation")) {
                // Show account creation link
                echo '<li><a href="' . $website->getUrlPage("create_account") . '">' . $this->website->t("main.create_account") . '</a></li>';
            }
            echo '<li><a href="' . $website->getUrlPage("login") . '">' . $this->website->t("main.log_in") . '</a></li>';
        }
    }

    public function echoAccountLabel() {
        $website = $this->website;
        $user = $website->getAuth()->getCurrentUser();

        // Get welcome text
        if ($user == null) {
            // Logged out
            $welcome_text = $website->t("main.welcome_guest") . " ";
            $welcome_text.= '<a class="arrow" href="' . $website->getUrlPage("login") . '">';
            $welcome_text.= $website->t("main.log_in") . "</a>\n";
        } else {
            // Logged in
            $display_name = htmlSpecialChars($user->getDisplayName());
            $welcome_text = <<<EOT
                <a class="user_welcome_link" href="{$website->getUrlPage("account")}">
                    {$website->tReplaced("main.welcome_user", $display_name)}
                    <span class="username">(@{$user->getUsername()})</span>
                </a>
EOT;
        }
        echo "<p>" . $welcome_text . "</p>";
    }

    public function echoAccountBox($gravatarSize = 140) {
        $website = $this->website;
        $user = $website->getAuth()->getCurrentUser();

        if ($user == null) {
            // Nothing to display
            return;
        }

        // Get avatar url
        $avatarUrl = $user->getAvatarUrl($gravatarSize);

        // Display account box
        echo '<img id="account_box_gravatar" src="' . $avatarUrl . '" />';
        echo '<ul>';
        echo $this->echoAccountsMenu();
        echo '</ul>';
    }

    public function echoBreadcrumbs() {
        $website = $this->website;

        echo <<<EOT
			<a href="http://www.leiden.edu/" class="first">Leiden University</a>
			<a href="http://www.research.leiden.edu/">Research Portal</a>
			<a href="http://www.research.leiden.edu/research-profiles/">Leiden Research Profiles</a>
			<a href="{$website->getUrlMain()}">Datascience</a>
EOT;
        // Nog de laatste link?
        if (!($this->pageRenderer->getPage() instanceof HomePage)) {
            $title = $this->pageRenderer->getShortPageTitle();
            echo '<a href="#">' . $title . '</a>';
        }
    }

    public function echoCopyright() {
        echo $this->website->getConfig()->get("copyright");
    }

    public function echoTopMenu() {
        $website = $this->website;
        $oMenu = new LinkRepository($website);
        echo $oMenu->getAsHtml($oMenu->getMenuTop(new CategoryRepository($website)));
    }

    //Geeft een zoekformulier weer
    public function echoSearchForm() {
        $website = $this->website;

        // Last entered search term
        $keyword = htmlSpecialChars($website->getRequestString("searchbox"));

        // Echo the form
        echo <<<SEARCH
            <form id="searchform" name="searchform" action="{$website->getUrlMain()}" method="get">
                <input type="hidden" name="p" value="search" />
                <input type="search" size="21" name="searchbox" id="searchbox" value="{$keyword}" />
                <input type="submit" class="button" value="{$website->t("main.search")}" name="searchbutton" id="searchbutton" />
            </form>
SEARCH;
    }

    public function echoWidgets($area) {
        $widgetsView = new WidgetsView($this->website->getText(), $this->website->getWidgets(), $area);
        echo $widgetsView->getText();
    }

}
