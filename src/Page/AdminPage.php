<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Request;
use Rcms\Core\Website;
use Rcms\Page\View\AdminPageView;

/**
 * Page with links to all admin tasks of the site
 */
class AdminPage extends Page {

    public function getPageType() {
        return "BACKSTAGE";
    }

    public function getPageTitle(Request $request) {
        return $request->getWebsite()->t("main.admin");
    }

    public function getView(Website $website) {
        return new AdminPageView($website);
    }

    public function getMinimumRank(Request $request) {
        return Authentication::$ADMIN_RANK;
    }

}
