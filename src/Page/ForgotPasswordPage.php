<?php

namespace Rcms\Page;

use Rcms\Core\Ranks;
use Rcms\Core\Request;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;
use Rcms\Core\Validate;
use Rcms\Core\Website;
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

    public function init(Website $website, Request $request) {
        if (Validate::requestToken($request)) {
            $this->email = $request->getRequestString("user_email", "");
            // Send mail
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

    public function getMinimumRank() {
        return Ranks::LOGGED_OUT;
    }

    public function getPageTitle(Text $text) {
        return $text->t("users.password.forgot");
    }

    public function getPageType() {
        return Page::TYPE_NORMAL;
    }

    public function getTemplate(Text $text) {
        return new PasswordForgotTemplate($text, $this->requestToken, $this->email);
    }

}
