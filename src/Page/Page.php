<?php

namespace Rcms\Page;

use Psr\Http\Message\ResponseInterface;
use Rcms\Core\Ranks;
use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Core\Website;
use Rcms\Template\Template;

/**
 * Represents a page on the website.
 *
 * First the getMinimumRank method is called to determine whether the user may
 * visit the page. If yes, the init method will be called. After that, any other
 * method may be called, even multiple times.
 */
abstract class Page {
    /**
     * Page type for most pages. You can get the type of a page using the
     * getPageType method.
     *
     * Page types are used for styling purposes. For authentication, see the
     * getMinimumRank method.
     */
    const TYPE_NORMAL = 2;
    /**
     * Pages in the admin area of the site have this type.
     */
    const TYPE_BACKSTAGE = 3;
    /**
     * Page type for the home page.
     */
    const TYPE_HOME = 4;

    /**
     * Initializes the page. This should fetch the data from the database,
     * validate the input and save it to the database. Will only be called when
     * the user has the rank specified by `getMinimumRank`.
     * @param Website $website The application object.
     * @param Request $request Request that caused this page to load.
     */
    public function init(Website $website, Request $request) {
        // Not abstract, as simple static pages don't need to load/save data
    }

    /**
     * Gets the page type. HOME, NORMAL or BACKSTAGE
     * $return string The page type.
     */
    public function getPageType() {
        return Page::TYPE_NORMAL;
    }

    /**
     * Gets the minimum rank required to view this page, like
     * Authentication::USER_RANK. If the user doesn't satisfy this rank, no
     * other methods on this class will be called.
     * @return int The minimum rank required to view this page.
     */
    public abstract function getMinimumRank();

    /**
     * Gets the title of this page. Empty titles are allowed. The returned
     * title must be unescaped.
     * @param Text $text The messages instance.
     * @return string The title of this page.
     */
    public abstract function getPageTitle(Text $text);

    /**
     * Gets a shorter title for this page, for example for in the breadcrumbs.
     * Empty titles are highly discouraged.
     * @param Text $text Request that caused this page to load.
     * @return string The short title of this page.
     */
    public function getShortPageTitle(Text $text) {
        return $this->getPageTitle($text);
    }

    /**
     * Returns the view of this page.
     * @param Text $text The messages instance.
     * @return Template|null A view, or null if not using a view (deprecated).
     */
    protected function getTemplate(Text $text) {
        return null;
    }

    /**
     * Gets all views on this page, in case this page consists of multiple
     * views. If only one view is used, this method simply wraps
     * {@link #getTemplate(Text)} in an one-element array. If no views are used,
     * the array will be empty. This behaviour is deprecated.
     * @param Text $text The messages instance.
     * @return Template[] Array of views. May be empty if this page is not using
     * views (deprecated).
     */
    public function getTemplates(Text $text) {
        // Fall back on method to get a single view
        $view = $this->getTemplate($text);

        if ($view === null) {
            // No view found, return empty array
            return [];
        }

        return [$view];
    }

    /**
     * Gets the HTML content of this page. This method is deprecated, use
     * getTemplate instead.
     * @return string The HTML content of this page.
     * @deprecated Use getTemplate instead.
     */
    public function getPageContent(Website $website, Request $request) {
        return "";
    }

    /**
     * Modifies the details of the HTTP response for this page.
     * @param ResponseInterface $response The response.
     * @return ResponseInterface The modified response.
     */
    public function modifyResponse(ResponseInterface $response) {
        return $response;
    }

}
