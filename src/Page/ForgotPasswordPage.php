<?php

namespace Rcms\Page;

use Rcms\Core\NotFoundException;
use Rcms\Core\PasswordResetter;
use Rcms\Core\Ranks;
use Rcms\Core\Request;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;
use Rcms\Core\Validate;
use Rcms\Core\Website;
use Rcms\Template\EmptyTemplate;
use Rcms\Template\PasswordForgotTemplate;

/**
 * A page for starting the password recovery process.
 */
final class ForgotPasswordPage extends Page {

    /**
     * @var string Entered e-mail address, may not be valid.
     */
    private $email = "";

    /**
     * @var RequestToken Token protecting the request.
     */
    private $requestToken;

    /**
     * @var bool True if the reset mail has been sent.
     */
    private $mailSent = false;

    public function init(Website $website, Request $request) {
        if (Validate::requestToken($request)) {
            $this->email = $request->getRequestString("user_email", "");
            $this->sendMail($website, $request);
        } else {
            // Try to prefill e-mail
            $user = $request->getCurrentUser();
            if ($user !== null) {
                // For users that are still logged in, but forgot their password
                $this->email = (string) $user->getEmail();
            }
        }

        $this->requestToken = RequestToken::generateNew();
        $this->requestToken->saveToSession();
    }

    private function sendMail(Website $website, Request $request) {
        $text = $website->getText();

        $userRepo = $website->getUserRepository();
        try {
            $user = $userRepo->getByEmail($this->email);
            $passwordResetter = new PasswordResetter($website, $request);
            $passwordResetter->sendPasswordReset($user);
            $text->addMessage($text->tReplaced("users.password.forgot.reset_sent", $text->e($user->getEmail())));
            $this->mailSent = true;
        } catch (NotFoundException $e) {
            $text->addError($text->t("users.password.forgot.unknown_email"));
        }
    }

    public function getMinimumRank() {
        return Ranks::LOGGED_OUT;
    }

    public function getPageTitle(Text $text) {
        return $text->t("users.password.forgot");
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

    public function getTemplate(Text $text) {
        if ($this->mailSent) {
            // The success message is enough
            return new EmptyTemplate($text);
        }
        return new PasswordForgotTemplate($text, $this->requestToken, $this->email);
    }

}
