<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Text;
use Rcms\Page\View\Error404View;

/**
 * The 404 page of the site.
 */
class Error404Page extends Page {
    public function getPageTitle(Text $text) {
        return $text->t("errors.404_page.title");
    }

    public function getView(Text $text) {
        return new Error404View($text);
    }
    
    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

    public function getMinimumRank() {
        return Authentication::RANK_LOGGED_OUT;
    }
}
