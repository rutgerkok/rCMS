<?php

namespace Rcms\Page;

use Psr\Http\Message\ResponseInterface;

use Rcms\Core\Ranks;
use Rcms\Core\Request;
use Rcms\Core\Text;
use Rcms\Core\UserSession;
use Rcms\Core\Website;

use Rcms\Template\LoggedOutTemplate;

class LogoutPage extends Page {
    
    private $userSession;

    public function init(Website $website, Request $request) {
        $this->userSession = new UserSession($website);
    }
    
    public function modifyResponse(ResponseInterface $response) {
        return $this->userSession->logout($response);
    }

    public function getPageTitle(Text $text) {
        return $text->t("main.log_out") . '...';
    }

    public function getShortPageTitle(Text $text) {
        return $text->t("main.log_out");
    }

    public function getTemplate(Text $text) {
        return new LoggedOutTemplate($text);
    }
    
    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

    public function getMinimumRank() {
        return Ranks::LOGGED_OUT;
    }

}
