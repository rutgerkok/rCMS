<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Website;
use Rcms\Page\View\AdminPageView;

/**
 * Page with links to all admin tasks of the site
 */
class AdminPage extends Page {

    public function getPageType() {
        return "BACKSTAGE";
    }

    public function getPageTitle(Website $oWebsite) {
        return $oWebsite->t("main.admin");
    }

    public function getView(Website $oWebsite) {
        return new AdminPageView($oWebsite);
    }

    public function getMinimumRank(Website $oWebsite) {
        return Authentication::$ADMIN_RANK;
    }

}
