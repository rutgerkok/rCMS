<?php

namespace Rcms\Page;

use Rcms\Core\Ranks;
use Rcms\Core\Config;
use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Core\RequestToken;
use Rcms\Core\Website;
use Rcms\Template\LoginFormTemplate;

/**
 * Page used when a login is required.
 */
class ErrorLoginRequiredPage extends Page {

    /**
     * @var int Minimum required rank.
     */
    private $minimumRank;
    /**
     * @var UriInterface Targed URL for the login form, including GET variables.
     */
    private $targetUrl;
    /**
     * @var array POST variables that must be outputted again, to avoid losing
     * data when the session has expired.
     */
    private $postVars;
    /** @var bool Whether a "Create new account" link is displayed. */
    private $canCreateAccounts;
    /** @var RequestToken Protection against CSRF */
    private $requestToken;

    public function __construct($minimumRank = Ranks::USER) {
        $this->minimumRank = $minimumRank;
    }

    public function init(Website $website, Request $request) {
        $psrRequest = $request->toPsr();
        $this->targetUrl = $psrRequest->getUri();
        $this->postVars = (array) $psrRequest->getParsedBody();
        $this->canCreateAccounts = $website->getConfig()->get(Config::OPTION_USER_ACCOUNT_CREATION);
        $this->requestToken = RequestToken::generateNew();
        $this->requestToken->saveToSession();
    }

    public function getPageTitle(Text $text) {
        return $text->t("main.log_in");
    }

    public function getTemplate(Text $text) {
        return new LoginFormTemplate($text, $this->targetUrl, $this->requestToken, 
                $this->postVars, $this->canCreateAccounts);
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

    public function getMinimumRank() {
        return Ranks::LOGGED_OUT;
    }
}
