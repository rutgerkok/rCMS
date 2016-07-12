<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Core\Website;
use Rcms\Page\View\LoginView;

/**
 * Page used when a login is required.
 */
class ErrorLoginRequiredPage extends Page {
    
    private $errorMessage;
    private $minimumRank;
    private $request;

    public function __construct($minimumRank = Authentication::RANK_USER) {
        $this->minimumRank = $minimumRank;
    }

    public function init(Website $website, Request $request) {
        $this->errorMessage = $website->getAuth()->getLoginError($this->minimumRank);
        $this->request = $request;
    }

    public function getPageTitle(Text $text) {
        return $text->t("main.log_in");
    }

    public function getView(Text $text) {
        return new LoginView($text, $this->request, $this->errorMessage);
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

    public function getMinimumRank() {
        return Authentication::RANK_LOGGED_OUT;
    }
}
