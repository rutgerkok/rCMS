<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\User;
use Rcms\Core\Request;
use Rcms\Core\Website;
use Rcms\Page\View\LoggedInOtherView;

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

class LoginOtherPage extends Page {

    /**
     * @var User The new user.
     */
    private $newUser = null;

    public function init(Request $request) {
        $oWebsite = $request->getWebsite();
        $userId = $request->getParamInt(0);

        // Fetch user
        $user = User::getById($oWebsite, $userId);
        if ($user === null || !$user->canLogIn()) {
            $oWebsite->addError($oWebsite->t("users.account") . " " . $oWebsite->t("errors.not_found"));
            return;
        }

        // Set user
        $this->newUser = $user;
        $oWebsite->getAuth()->setCurrentUser($user);
    }

    public function getPageType() {
        return "BACKSTAGE";
    }

    public function getPageTitle(Request $request) {
        return $request->getWebsite()->t("main.log_in");
    }

    public function getView(Website $oWebsite) {
        if ($this->newUser === null) {
            // Just display the error
            return new LoggedInOtherView($oWebsite);
        }
        return new LoggedInOtherView($oWebsite, $this->newUser);
    }

    public function getMinimumRank(Request $request) {
        return Authentication::$ADMIN_RANK;
    }

}
