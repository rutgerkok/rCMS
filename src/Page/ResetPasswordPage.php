<?php

namespace Rcms\Page;

use Rcms\Core\Link;
use Rcms\Core\PasswordResetter;
use Rcms\Core\Ranks;
use Rcms\Core\Request;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;
use Rcms\Core\User;
use Rcms\Core\Validate;
use Rcms\Core\Website;
use Rcms\Template\EmptyTemplate;
use Rcms\Template\PasswordResetTemplate;

/**
 * Page for setting a new password after the user clicked on a link in a mail.
 */
final class ResetPasswordPage extends Page {

    private $allowPasswordReset = false;
    private $completedPasswordReset = false;

    /**
     * @var User The user that is resetting his/her password.
     */
    private $user;
    /**
     *
     * @var RequestToken Session token protecting the request.
     */
    private $requestToken;

    public function init(Website $website, Request $request) {
        $userId = $request->getParamInt(0, 0);
        $this->user = $website->getUserRepository()->getById($userId);

        $passwordToken =  $request->getRequestString("token", "");
        $this->checkResetAllowed($passwordToken, $website->getText());

        if ($this->allowPasswordReset && Validate::requestToken($request)) {
            $this->changePassword($website, $request);
        }

        $this->requestToken = RequestToken::generateNew();
        $this->requestToken->saveToSession();
    }

    private function checkResetAllowed($givenToken, Text $text) {
        if (PasswordResetter::verify($this->user, $givenToken)) {
            $this->allowPasswordReset = true;
        } else {
            $text->addError($text->t("users.password.forgot.invalid_token"), Link::of(
                    $text->getUrlPage("forgot_password"), $text->t("errors.try_again")));
        }
    }

    private function changePassword(Website $website, Request $request) {
        $text = $website->getText();

        $password = $request->getRequestString("password", "");
        $password2 = $request->getRequestString("password2", "");
        if (Validate::password($password, $password2)) {
            $this->user->setPassword($password);
            $website->getUserRepository()->save($this->user);
            $text->addMessage($text->t("users.password") . " " . $text->t("editor.is_changed"), Link::of(
                    $text->getUrlPage("login"), $text->t("main.log_in")));
            $this->completedPasswordReset = true;
        } else {
            $text->addError($text->t("users.password") . " " . Validate::getLastError($text));
        }
    }

    public function getMinimumRank() {
        return Ranks::LOGGED_OUT;
    }

    public function getPageTitle(Text $text) {
        return $text->t("users.password.forgot");
    }

    public function getTemplate(Text $text) {
        if (!$this->allowPasswordReset || $this->completedPasswordReset) {
            return new EmptyTemplate($text);
        }
        return new PasswordResetTemplate($text, $this->user, $this->requestToken);
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

}
