<?php

namespace Rcms\Page;

use Rcms\Core\Ranks;
use Rcms\Core\Text;
use Rcms\Template\Error404Template;

/**
 * The 404 page of the site.
 */
class Error404Page extends Page {
    public function getPageTitle(Text $text) {
        return $text->t("errors.404_page.title");
    }

    public function getTemplate(Text $text) {
        return new Error404Template($text);
    }
    
    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

    public function getMinimumRank() {
        return Ranks::LOGGED_OUT;
    }
}
