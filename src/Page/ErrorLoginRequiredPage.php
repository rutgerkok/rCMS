<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Config;
use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Core\Website;
use Rcms\Template\LoginFormTemplate;

/**
 * Page used when a login is required.
 */
class ErrorLoginRequiredPage extends Page {

    private $errorMessage;
    private $minimumRank;
    private $targetUrl;
    private $postVars;
    private $canCreateAccounts;

    public function __construct($minimumRank = Authentication::RANK_USER) {
        $this->minimumRank = $minimumRank;
    }

    public function init(Website $website, Request $request) {
        $auth = $request->getAuth($website->getUserRepository());
        $this->errorMessage = $auth->getLoginError($website->getText(), $this->minimumRank);
        $psrRequest = $request->toPsr();
        $this->targetUrl = $psrRequest->getUri();
        $this->postVars = (array) $psrRequest->getParsedBody();
        $this->canCreateAccounts = $website->getConfig()->get(Config::OPTION_USER_ACCOUNT_CREATION);
    }

    public function getPageTitle(Text $text) {
        return $text->t("main.log_in");
    }

    public function getTemplate(Text $text) {
        return new LoginFormTemplate($text, $this->targetUrl, $this->postVars,
                $this->errorMessage, $this->canCreateAccounts);
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

    public function getMinimumRank() {
        return Authentication::RANK_LOGGED_OUT;
    }
}
