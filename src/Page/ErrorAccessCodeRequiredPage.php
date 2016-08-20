<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Text;
use Rcms\Template\AccessCodeEnterTemplate;

/**
 * Shown when an access code is required.
 */
class ErrorAccessCodeRequiredPage extends Page {

    public function getPageTitle(Text $text) {
        return $text->t("access_key.key_required");
    }

    public function getTemplate(Text $text) {
        return new AccessCodeEnterTemplate($text);
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

    public function getMinimumRank() {
        return Authentication::RANK_LOGGED_OUT;
    }

}
