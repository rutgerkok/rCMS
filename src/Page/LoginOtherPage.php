<?php

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

class LoginOtherPage extends Page {

    /**
     * @var User The new user.
     */
    private $newUser = null;

    public function init(Website $oWebsite) {
        $userId = $oWebsite->getRequestInt("id");

        // Fetch user
        $user = User::getById($oWebsite, $userId);
        if ($user === null || !$user->canLogIn()) {
            $oWebsite->addError($oWebsite->t("user.account") . " " . $oWebsite->t("errors.not_found"));
            return;
        }

        // Set user
        $this->newUser = $user;
        $oWebsite->getAuth()->setCurrentUser($user);
    }

    public function getPageType() {
        return "BACKSTAGE";
    }

    public function getPageTitle(Website $oWebsite) {
        return $oWebsite->t("main.log_in");
    }

    public function getView(Website $oWebsite) {
        if ($this->newUser === null) {
            // Just display the error
            return new LoggedInOtherView($oWebsite);
        }
        return new LoggedInOtherView($oWebsite, $this->newUser);
    }

    public function getMinimumRank(Website $oWebsite) {
        return Authentication::$ADMIN_RANK;
    }

}
