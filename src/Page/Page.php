<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Request;
use Rcms\Core\Website;

abstract class Page {

    /**
     * Called before any output is done. Can be used to set cookies, for example
     * @param Request $request Request that caused this page to load.
     */
    public function init(Request $request) {
        
    }

    /**
     * Gets the page type. HOME, NORMAL or BACKSTAGE
     * $return string The page type.
     */
    public function getPageType() {
        return "NORMAL";
    }

    /**
     * Gets the minimum rank required to view this page, like Authentication::$USER_RANK.
     * @param Request $request Request that caused this page to load.
     * @return int The minimum rank required to view this page.
     */
    public function getMinimumRank(Request $request) {
        return Authentication::$LOGGED_OUT_RANK;
    }

    /**
     * Gets the title of this page. Empty titles are allowed.
     * @param Request $request Request that caused this page to load.
     * @return string The title of this page.
     */
    public abstract function getPageTitle(Request $request);

    /**
     * Gets a shorter title for this page, for example for in the breadcrumbs.
     * Empty titles are highly discouraged.
     * @param Request $request Request that caused this page to load.
     * @return string The short title of this page.
     */
    public function getShortPageTitle(Request $request) {
        return $this->getPageTitle($request);
    }

    /**
     * Returns the view of this page.
     * @param Website $website The website instance.
     * @return View|null A view, or null if not using a view (deprecated).
     */
    protected function getView(Website $website) {
        return null;
    }

    /**
     * Gets all views on this page. 
     * @param Website $website The website instance.
     * @return View[] Array of views. May be empty if this page is not using
     * views (deprecated).
     */
    public function getViews(Website $website) {
        // Fall back on method to get a single view
        $view = $this->getView($website);

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
        $views = $this->getViews($request->getWebsite());
        foreach ($views as $view) {
            $returnValue.= $view->getText();
        }
        return $returnValue;
    }

}
