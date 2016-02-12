<?php

namespace Rcms\Page;

use Rcms\Core\Text;
use Rcms\Page\View\EnterAccessCodeView;

/**
 * Shown when an access code is required.
 */
class ErrorAccessCodeRequiredPage extends Page {

    public function getPageTitle(Text $text) {
        return $text->t("access_key.key_required");
    }

    public function getView(Text $text) {
        return new EnterAccessCodeView($text);
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

}
