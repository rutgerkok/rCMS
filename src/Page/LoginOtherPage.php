<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Text;
use Rcms\Core\User;
use Rcms\Core\Request;
use Rcms\Page\View\LoggedInOtherView;

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

    public function getPageTitle(Text $text) {
        return $text->t("main.log_in");
    }

    public function getView(Text $text) {
        if ($this->newUser === null) {
            // Just display the error
            return new LoggedInOtherView($text);
        }
        return new LoggedInOtherView($text, $this->newUser);
    }

    public function getMinimumRank(Request $request) {
        return Authentication::$ADMIN_RANK;
    }

}
