<?php

namespace Rcms\Page\Renderer;

use Rcms\Core\CategoryRepository;
use Rcms\Core\Link;
use Rcms\Core\Request;
use Rcms\Core\Theme;
use Rcms\Core\Website;
use Rcms\Core\Widget\WidgetRepository;

use Rcms\Page\HomePage;
use Rcms\Page\Page;
use Rcms\Page\View\MenuView;
use Rcms\Page\View\WidgetsPageView;

/**
 * Context in which a theme operates. All methods that a theme can use for
 * outputting things are located here.
 */
class PageRenderer {

    /** @var Website The website instance. */
    private $website;

    /** @var Theme The theme used to render the page. */
    private $theme;

    /** @var Page The renderer of the page. */
    private $page;

    /** @var Request The request. */
    private $request;

    /**
     * @var WidgetRepository|null Repository for the widgets on the sidebar.
     * Initialized when first needed. 
     */
    private $widgetsRepo = null;

    public function __construct(Website $website, Request $request, Page $page) {
        $this->website = $website;
        $this->request = $request;
        $this->theme = $website->getThemeManager()->getCurrentTheme();
        $this->page = $page;
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
        $title = $this->website->getSiteTitle();
        if ($this->website->getConfig()->get("append_page_title", false)) {
            if (!($this->page instanceof HomePage)) {
                $title.= " - " . $this->page->getShortPageTitle();
            }
        }
        return $title;
    }

    /**
     * Echoes only the main content of the page, without any clutter.
     */
    public function echoPageContent() {
        // Title
        $title = $this->page->getPageTitle($this->website->getText());
        if (!empty($title)) {
            echo "<h2>" . htmlSpecialChars($title) . "</h2>\n";
        }

        // Fetch content first
        $content = $this->page->getPageContent($this->website, $this->request);

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
     * Gets the type of this page. (See constants in the Page class.)
     * @return int The type.
     */
    public function getPageType() {
        return $this->page->getPageType();
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
            echo '<li><a class="arrow" href="' . $website->getUrlPage("admin") . '">' . $website->t("main.admin") . '</a></li>';
        }
        if ($website->isLoggedIn()) {
            // Logged in
            echo '<li><a class="arrow" href="' . $website->getUrlPage("account") . '">' . $this->website->t("main.my_account") . '</a></li>';
            echo '<li><a class="arrow" href="' . $website->getUrlPage("logout") . '">' . $this->website->t("main.log_out") . '</a></li>';
        } else {
            // Not logged in
            if ($website->getConfig()->get("user_account_creation")) {
                // Show account creation link
                echo '<li><a class="arrow" href="' . $website->getUrlPage("create_account") . '">' . $this->website->t("main.create_account") . '</a></li>';
            }
            echo '<li><a class="arrow" href="' . $website->getUrlPage("login") . '">' . $this->website->t("main.log_in") . '</a></li>';
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
        echo $welcome_text;
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
        if (!($this->page instanceof HomePage)) {
            $title = $page->getShortPageTitle();
            echo '<a href="#">' . $title . '</a>';
        }
    }

    public function echoCopyright() {
        echo $this->website->getConfig()->get("copyright");
    }

    public function echoTopMenu() {
        $website = $this->website;
        

        $links = array();
        $links[] = Link::of($website->getUrlMain(), $website->t("main.home"));

        if ($website->getConfig()->isDatabaseUpToDate()) {
            $categoriesRepo = new CategoryRepository($website);
            $categories = $categoriesRepo->getCategories();
            foreach ($categories as $category) {
                if ($category->isStandardCategory()) {
                    continue; // Don't display "No categories"
                }
                $links[] = Link::of(
                                $website->getUrlPage("category", $category->getId()), $category->getName()
                );
            }
        }
        $menuView = new MenuView($website->getText(), $links);
        echo $menuView->getText();
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
        $editLinks = $this->website->isLoggedInAsStaff(true);
        if ($this->widgetsRepo === null) {
            $this->widgetsRepo = new WidgetRepository($this->website);
        }
        $widgets = $this->widgetsRepo->getWidgetsInDocumentWithId($area);

        $widgetsView = new WidgetsPageView($this->website->getText(), $area, $this->website->getWidgets(), $widgets, $editLinks);
        echo $widgetsView->getText();
    }

}
