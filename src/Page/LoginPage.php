<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Config;
use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Core\Website;
use Rcms\Template\LoggedInTemplate;
use Rcms\Template\LoginFormTemplate;

class LoginPage extends Page {

    private $loggedIn;
    private $loggedInAsAdmin;
    private $errorMessage;
    private $canCreateAccounts;

    public function init(Website $website, Request $request) {
        $this->request = $request;

        // Handle login ourselves
        // (Using the provided getMinimumRank helper gives an ugly
        // "You need to be logged in to view this page" message.)
        $auth = $request->getAuth($website->getUserRepository());
        $this->loggedIn = $auth->check($website->getText(), Authentication::RANK_MODERATOR);
        $this->loggedInAsAdmin = $request->hasRank($website, Authentication::RANK_ADMIN);
        if (!$this->loggedIn) {
            $this->errorMessage = $this->getLoginErrorMessage($website->getText(), $request->getAuth($website->getUserRepository()));
        }
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

    /**
     * Gets the error message to display on the login form, like "Wrong
     * password" or "You need to be logged in to view this page".
     * @param Text $text The text object, used for translations.
     * @param Authentication $oAuth The authentication object.
     * @return string The error message, or empty if there is no message.
     */
    protected function getLoginErrorMessage(Text $text, Authentication $oAuth) {
        if ($oAuth->hasLoginFailed()) {
            return $text->t("errors.invalid_login_credentials");
        }
        return "";
    }

    public function getTemplate(Text $text) {
        if ($this->loggedIn) {
            return new LoggedInTemplate($text, $this->loggedInAsAdmin);
        } else {
            // Return a login view, but without the "Must be logged in" message
            // at the top.
            return new LoginFormTemplate($text, $text->getUrlPage("login"), [],
                    $this->errorMessage, $this->canCreateAccounts);
        }
    }

    public function getMinimumRank() {
        return Authentication::RANK_LOGGED_OUT;
    }

}
