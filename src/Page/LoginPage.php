<?php

namespace Rcms\Page;

use Rcms\Core\Ranks;
use Rcms\Core\Config;
use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Core\Website;
use Rcms\Template\LoggedInTemplate;
use Rcms\Template\LoginFormTemplate;

class LoginPage extends Page {

    private $loggedIn;
    private $loggedInAsAdmin;
    private $canCreateAccounts;

    public function init(Website $website, Request $request) {
        $this->request = $request;

        // Handle login ourselves using views
        // (Using the provided getMinimumRank helper gives an ugly
        // "You need to be logged in to view this page" message.)
        $this->loggedIn = $request->hasRank(Ranks::USER);
        $this->loggedInAsAdmin = $request->hasRank(Ranks::ADMIN);
        $this->canCreateAccounts = (bool) $website->getConfig()->get(Config::OPTION_USER_ACCOUNT_CREATION);
    }

    public function getPageTitle(Text $text) {
        return $text->t("main.log_in") . '...';
    }

    public function getShortPageTitle(Text $text) {
        return $text->t("main.log_in");
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

    public function getTemplate(Text $text) {
        if ($this->loggedIn) {
            return new LoggedInTemplate($text, $this->loggedInAsAdmin);
        } else {
            // Return a login view, but without the "Must be logged in" message
            // at the top.
            return new LoginFormTemplate($text, $text->getUrlPage("login"), [],
                    $this->canCreateAccounts);
        }
    }

    public function getMinimumRank() {
        return Ranks::LOGGED_OUT;
    }

}
