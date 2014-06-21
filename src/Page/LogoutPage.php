<?php

namespace Rcms\Page;

use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Page\View\LoggedOutView;

class LogoutPage extends Page {

    public function init(Request $request) {
        $request->getWebsite()->getAuth()->logOut();
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
        return "BACKSTAGE";
    }

}
