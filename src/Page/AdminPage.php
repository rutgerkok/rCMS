<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Request;
use Rcms\Core\Text;
use Rcms\Page\View\AdminPageView;

/**
 * Page with links to all admin tasks of the site
 */
class AdminPage extends Page {

    public function getPageType() {
        return "BACKSTAGE";
    }

    public function getPageTitle(Text $text) {
        return $text->t("main.admin");
    }

    public function getView(Text $text) {
        return new AdminPageView($text);
    }

    public function getMinimumRank(Request $request) {
        return Authentication::$ADMIN_RANK;
    }

}