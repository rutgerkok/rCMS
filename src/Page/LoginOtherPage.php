<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\NotFoundException;
use Rcms\Core\Text;
use Rcms\Core\User;
use Rcms\Core\Request;
use Rcms\Core\Website;
use Rcms\Template\LoggedInOtherTemplate;

class LoginOtherPage extends Page {

    /**
     * @var User The new user.
     */
    private $newUser = null;

    public function init(Website $website, Request $request) {
        $userId = $request->getParamInt(0);

        // Fetch user
        $userRepo = $website->getUserRepository();
        $user = $userRepo->getById($userId);
        if (!$user->canLogIn()) {
            // Can't log in to deleted or banned users
            throw new NotFoundException();
        }

        // Set user
        $this->newUser = $user;
        $request->getAuth($userRepo)->setCurrentUser($user);
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

    public function getPageTitle(Text $text) {
        return $text->t("main.log_in");
    }

    public function getTemplate(Text $text) {
        if ($this->newUser === null) {
            // Just display the error
            return new LoggedInOtherTemplate($text);
        }
        return new LoggedInOtherTemplate($text, $this->newUser);
    }

    public function getMinimumRank() {
        return Authentication::RANK_ADMIN;
    }

}
