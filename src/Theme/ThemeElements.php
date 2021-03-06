<?php

namespace Rcms\Theme;

use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;
use Rcms\Core\Ranks;
use Rcms\Core\CategoryRepository;
use Rcms\Core\Config;
use Rcms\Core\Link;
use Rcms\Core\LinkRepository;
use Rcms\Core\Request;
use Rcms\Core\Website;
use Rcms\Core\Widget\WidgetRepository;
use Rcms\Core\Widget\WidgetRunner;
use Rcms\Page\HomePage;
use Rcms\Page\Page;
use Rcms\Template\MenuTemplate;
use Rcms\Template\WidgetsColumnTemplate;

/**
 * The elements that appear inside a theme. All elements already escape their
 * text for use in HTML.
 */
final class ThemeElements {

    /**
     * @var Website The website instance.
     */
    private $website;

    /**
     * @var Page The page being rendered.
     */
    private $page;

    /**
     * @var Request The request of the client.
     */
    private $request;

    /**
     * @var UriInterface The URL of the theme.
     */
    private $themeUrl;

    /**
     * @var WidgetRepository|null Repository for the widgets on the sidebar.
     * Initialized when first needed.
     */
    private $widgetsRepo = null;

    public function __construct(Website $website, Page $page, Request $request, UriInterface $themeUrl) {
        $this->website = $website;
        $this->page = $page;
        $this->request = $request;
        $this->themeUrl = $themeUrl;
    }

    /**
     * Gets the url of the directory of the theme used to render this page.
     * @return UriInterface The url.
     */
    public function getUrlTheme() {
        return $this->themeUrl;
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
        $title = $this->website->getConfig()->get(Config::OPTION_SITE_TITLE);
        if ($this->website->getConfig()->get("append_page_title", false)) {
            if (!($this->page instanceof HomePage)) {
                $title.= " - " . $this->page->getShortPageTitle($this->website->getText());
            }
        }
        return $this->website->getText()->e($title);
    }

    /**
     * Gets the title for the &lt;title&gt; tag.
     * @return string The title.
     */
    public function getHeadTitle() {
        $title = $this->page->getPageTitle($this->website->getText());
        if (empty($title)) {
            $title = $this->page->getShortPageTitle($this->website->getText());
        }
        return $this->website->getText()->e($title);
    }

    /**
     * Echoes only the main content of the page, without any clutter.
     */
    public function writePageContent(StreamInterface $stream) {
        $text = $this->website->getText();

        // Title
        $title = $this->page->getPageTitle($this->website->getText());
        if (!empty($title)) {
            $stream->write("<h2>" . $text->e($title) . "</h2>\n");
        }

        // Fetch page content using deprecated method
        $pageContent = $this->page->getPageContent($this->website, $this->request);

        // Errors and confirmations
        $this->writeList($stream, $text->getErrors(), "error");
        $this->writeList($stream, $text->getConfirmations(), "confirmation");

        // Write page content
        foreach ($this->page->getTemplates($text) as $view) {
            $view->writeText($stream);
        }
        $stream->write($pageContent);
    }

    /**
     * Gets whether the user is currently logged in.
     * @return bool True if the user is logged in, false otherwise.
     */
    public function isLoggedIn() {
        return $this->request->hasRank(Ranks::USER);
    }

    /**
     * Gets the type of this page. (See constants in the Page class.)
     * @return int The type.
     */
    public function getPageType() {
        return $this->page->getPageType();
    }

    /**
     * Gets the Text instance, for example for translations.
     * @return Text The text instance.
     */
    public function getText() {
        return $this->website->getText();
    }

    private function writeList(StreamInterface $stream, array $messages, $cssClass) {
        $messageCount = count($messages);
        if ($messageCount == 0) {
            return;
        } elseif ($messageCount == 1) {
            $stream->write(<<<ERROR
                <div class="$cssClass">
                    <p>{$messages[0]}</p>
                </div>
ERROR
            );
        } else {
            $messages = "<li>" . join("</li><li>", $messages) . "</li>";
            $stream->write(<<<LIST
                <div class="$cssClass">
                    <p>
                        <ul>
                            $messages
                        </ul>
                    </p>
                </div>
LIST
            );
        }
    }

    /**
     * Echoes three &lt;li&gt; links representing the accounts menu.
     */
    public function writeAccountsMenu(StreamInterface $stream) {
        $website = $this->website;
        $request = $this->request;

        if ($request->hasRank(Ranks::ADMIN)) {
            // Logged in as admin
            $stream->write('<li><a class="arrow" href="' . $website->getUrlPage("admin") . '">' . $website->t("main.admin") . '</a></li>');
        }
        if ($request->hasRank(Ranks::USER)) {
            // Logged in
            $stream->write('<li><a class="arrow" href="' . $website->getUrlPage("account") . '">' . $this->website->t("main.my_account") . '</a></li>');
            $stream->write('<li><a class="arrow" href="' . $website->getUrlPage("logout") . '">' . $this->website->t("main.log_out") . '</a></li>');
        } else {
            // Not logged in
            if ($website->getConfig()->get("user_account_creation")) {
                // Show account creation link
                $stream->write('<li><a class="arrow" href="' . $website->getUrlPage("create_account") . '">' . $this->website->t("main.create_account") . '</a></li>');
            }
            $stream->write('<li><a class="arrow" href="' . $website->getUrlPage("login") . '">' . $this->website->t("main.log_in") . '</a></li>');
        }
    }

    public function writeAccountLabel(StreamInterface $stream) {
        $text = $this->website->getText();
        $user = $this->request->getCurrentUser();

        // Get welcome text
        if ($user == null) {
            // Logged out
            $stream->write($text->t("main.welcome_guest") . " ");
            $stream->write('<a class="arrow" href="' . $text->e($text->getUrlPage("login")) . '">');
            $stream->write($text->t("main.log_in") . "</a>\n");
        } else {
            // Logged in
            $displayName = htmlSpecialChars($user->getDisplayName());
            $stream->write(<<<EOT
                <a class="user_welcome_link" href="{$text->e($text->getUrlPage("account"))}">
                    {$text->tReplaced("main.welcome_user", $displayName)}
                    <span class="username">(@{$text->e($user->getUsername())})</span>
                </a>
EOT
            );
        }
    }

    public function writeAccountBox(StreamInterface $stream, $gravatarSize = 140) {
        $user = $this->request->getCurrentUser();

        if ($user == null) {
            // Nothing to display
            return;
        }

        // Get avatar url
        $avatarUrl = $user->getAvatarUrl($gravatarSize);

        // Display account box
        $stream->write('<img id="account_box_gravatar" src="' . $avatarUrl . '" />');
        $stream->write('<ul>');
        $this->writeAccountsMenu($stream);
        $stream->write('</ul>');
    }

    public function writeBreadcrumbs(StreamInterface $stream) {
        $text = $this->website->getText();

        $stream->write(<<<EOT
			<a href="http://www.leiden.edu/" class="first">Leiden University</a>
			<a href="http://www.research.leiden.edu/">Research Portal</a>
			<a href="http://www.research.leiden.edu/research-profiles/">Leiden Research Profiles</a>
			<a href="{$text->getUrlMain()}">Datascience</a>
EOT
        );
        // Nog de laatste link?
        if (!($this->page instanceof HomePage)) {
            $title = $this->page->getShortPageTitle($text);
            $stream->write('<a href="#">' . $text->e($title) . '</a>');
        }
    }

    public function getCopyright() {
        return $this->website->getConfig()->get(Config::OPTION_COPYRIGHT);
    }

    public function writeTopMenu(StreamInterface $stream) {
        $website = $this->website;
        $text = $website->getText();
        $config = $website->getConfig();

        $links = [];
        $links[] = Link::of($text->getUrlMain(), $text->t("main.home"));

        if ($config->isDatabaseUpToDate()) {
            $menuId = (int) $config->get(Config::OPTION_MAIN_MENU_ID, 0);
            if ($menuId === 0) {
                $categoriesRepo = new CategoryRepository($website->getDatabase());
                $links = array_merge($links, $categoriesRepo->getCategoryLinks($text));
            } else {
                $linkRepo = new LinkRepository($website->getDatabase());
                $links = array_merge($links, $linkRepo->getLinksByMenu($menuId));
            }
        }
        $menuTemplate = new MenuTemplate($website->getText(), $links);
        $menuTemplate->writeText($stream);
    }

    /**
     * Displays a search form.
     * @param StreamInterface $stream Stream to write to.
     */
    public function writeSearchForm(StreamInterface $stream) {
        $text = $this->website->getText();

        // Last entered search term
        $keywordHtml = $text->e($this->request->getRequestString("searchbox", ""));

        // Echo the form
        $stream->write(<<<SEARCH
            <form id="searchform" name="searchform" action="{$text->e($text->getUrlPage("search"))}" method="get">
                <input type="search" size="21" name="searchbox" id="searchbox" value="{$keywordHtml}" />
                <input type="submit" class="button" value="{$text->t("main.search")}" name="searchbutton" id="searchbutton" />
            </form>
SEARCH
        );
    }

    public function writeWidgets(StreamInterface $stream, $area) {
        if (!$this->website->getConfig()->isDatabaseUpToDate()) {
            return;
        }
        $editLinks = $this->request->hasRank(Ranks::ADMIN);
        if ($this->widgetsRepo === null) {
            $this->widgetsRepo = new WidgetRepository($this->website);
        }
        $widgets = $this->widgetsRepo->getWidgetsInDocumentWithId($area);
        $widgetRunner = new WidgetRunner($this->website, $this->request);
        $widgetsTemplate = new WidgetsColumnTemplate($this->website->getText(), $area, $widgetRunner, $widgets, $editLinks);
        $widgetsTemplate->writeText($stream);
    }
}
