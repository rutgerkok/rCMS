<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Text;
use Rcms\Core\Request;

/**
 * Represents a page on the website.
 *
 * First the getMinimumRank method is called to determine whether the user may
 * visit the page. If yes, the init method will be called. After that, any other
 * method may be called, even multiple times.
 */
abstract class Page {

    /**
     * Initializes the page. This should fetch the data from the database,
     * validate the input and save it to the database.
     * @param Request $request Request that caused this page to load.
     */
    public function init(Request $request) {
        // Not abstract, as simple static pages don't need to load/save data
    }

    /**
     * Gets the page type. HOME, NORMAL or BACKSTAGE
     * $return string The page type.
     */
    public function getPageType() {
        return "NORMAL";
    }

    /**
     * Gets the minimum rank required to view this page, like
     * Authentication::$USER_RANK. If the user doesn't satisfy this rank, no
     * other methods on this class will be called.
     * @param Request $request Request that caused this page to load.
     * @return int The minimum rank required to view this page.
     */
    public function getMinimumRank(Request $request) {
        return Authentication::$LOGGED_OUT_RANK;
    }

    /**
     * Gets the title of this page. Empty titles are allowed.
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
     * @return View|null A view, or null if not using a view (deprecated).
     */
    protected function getView(Text $text) {
        return null;
    }

    /**
     * Gets all views on this page, in case this page consists of multiple
     * views. If only one view is used, this method simply wraps
     * {@link #getView(Messages)} in an one-element array. If no views are used,
     * the array will be empty. This behaviour is deprecated.
     * @param Text $text The messages instance.
     * @return View[] Array of views. May be empty if this page is not using
     * views (deprecated).
     */
    public function getViews(Text $text) {
        // Fall back on method to get a single view
        $view = $this->getView($text);

        if ($view === null) {
            // No view found, return empty array
            return array();
        }

        return array($view);
    }

    /**
     * Gets the HTML content of this page. Overriding this method is deprecated,
     * you should provide a view instead using {@link #getView(Request)}.
     * @return string The HTML content of this page.
     */
    public function getPageContent(Request $request) {
        $returnValue = "";
        $views = $this->getViews($request->getWebsite()->getText());
        foreach ($views as $view) {
            $returnValue.= $view->getText();
        }
        return $returnValue;
    }

}