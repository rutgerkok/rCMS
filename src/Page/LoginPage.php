<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Core\Website;
use Rcms\Page\View\LoggedInView;
use Rcms\Page\View\LoginView;

class LoginPage extends Page {

    private $loggedIn;
    private $loggedInAsAdmin;
    private $errorMessage;
    private $request;

    public function init(Website $website, Request $request) {
        $this->request = $request;

        // Handle login ourselves
        // (Using the provided getMinimumRank helper gives an ugly
        // "You need to be logged in to view this page" message.)
        $this->loggedIn = $website->getAuth()->check(Authentication::RANK_USER, false);
        $this->loggedInAsAdmin = $website->isLoggedInAsStaff(true);
        if (!$this->loggedIn) {
            $this->errorMessage = $this->getLoginErrorMessage($website->getText(), $website->getAuth());
        }
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

    public function getView(Text $text) {
        if ($this->loggedIn) {
            return new LoggedInView($text, $this->loggedInAsAdmin);
        } else {
            // Return a login view, but without the "Must be logged in" message
            // at the top.
            return new LoginView($text, $this->request, $this->errorMessage);
        }
    }

}
