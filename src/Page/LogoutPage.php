<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Core\Website;

use Rcms\Page\View\LoggedOutView;

class LogoutPage extends Page {

    public function init(Website $website, Request $request) {
        $website->getAuth()->logOut();
    }

    public function getPageTitle(Text $text) {
        return $text->t("main.log_out") . '...';
    }

    public function getShortPageTitle(Text $text) {
        return $text->t("main.log_out");
    }

    public function getView(Text $text) {
        return new LoggedOutView($text);
    }
    
    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

    public function getMinimumRank() {
        return Authentication::RANK_LOGGED_OUT;
    }

}
