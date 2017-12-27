<?php

namespace Rcms\Template;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;

/**
 * form for resetting the user password.
 */
final class PasswordForgotTemplate extends Template {

    /**
     * @var RequestToken Token protecting the request.
     */
    private $requestToken;

    /**
     * @var string E-mail address.
     */
    private $email;

    public function __construct(Text $text, RequestToken $requestToken, $email = "") {
        parent::__construct($text);
        $this->requestToken = $requestToken;
        $this->email = (string) $email;
    }

    public function writeText(StreamInterface $stream) {
        $text = $this->text;
        $stream->write(<<<HTML
            <p>
                {$text->t("users.password.forgot.explained")}
            </p>
            <form id="password_reset" method="POST" action="{$text->url("forgot_password")}">
                <p>
                    <label for="pasword_reset__email">{$text->t("users.email")}<span class="required">*</span>:</label>
                    <br />
                    <input type="email" name="user_email" id="password_reset__email" value="{$text->e($this->email)}" />
                </p>
                <p>
                    <input type="hidden" name="{$text->e(RequestToken::FIELD_NAME)}" value="{$text->e($this->requestToken->getTokenString())}" />
                    <input type="submit" class="button primary_button" value="{$text->t("users.password.request")}" />
                </p>
            </form>
HTML
                );
    }

}
