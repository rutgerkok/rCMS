<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Exception\NotFoundException;
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
        $userRepo = $oWebsite->getAuth()->getUserRepository();
        $user = $userRepo->getById($userId);
        if (!$user->canLogIn()) {
            // Can't log in to deleted or banned users
            throw new NotFoundException();
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
