<?php

namespace Rcms\Page;

use Rcms\Core\Ranks;
use Rcms\Core\Request;
use Rcms\Core\Text;
use Rcms\Core\Website;

use Rcms\Template\AdminPageTemplate;

/**
 * Page with links to all admin tasks of the site
 */
class AdminPage extends Page {

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

    public function getPageTitle(Text $text) {
        return $text->t("main.admin");
    }

    public function getTemplate(Text $text) {
        return new AdminPageTemplate($text);
    }

    public function getMinimumRank() {
        return Ranks::ADMIN;
    }

}
