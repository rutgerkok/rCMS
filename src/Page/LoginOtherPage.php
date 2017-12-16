<?php

namespace Rcms\Page;

use Psr\Http\Message\ResponseInterface;

use Rcms\Core\Ranks;
use Rcms\Core\NotFoundException;
use Rcms\Core\Text;
use Rcms\Core\User;
use Rcms\Core\UserSession;
use Rcms\Core\Request;
use Rcms\Core\Website;
use Rcms\Template\LoggedInOtherTemplate;

class LoginOtherPage extends Page {

    /**
     * @var User The new user.
     */
    private $newUser;
    
    /**
     * @var UserSession For logging the new user in.
     */
    private $userSession;

    public function init(Website $website, Request $request) {
        $this->userSession = new UserSession($website);

        // Fetch user
        $userId = $request->getParamInt(0);
        $this->newUser = $website->getUserRepository()->getById($userId);
        if (!$this->newUser->canLogIn()) {
            throw new NotFoundException();
        }
    }
    
    public function modifyResponse(ResponseInterface $response) {
        return $this->userSession->withUserInSession($response, $this->newUser);
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
        return Ranks::ADMIN;
    }

}
