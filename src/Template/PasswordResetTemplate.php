<?php

namespace Rcms\Template;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;
use Rcms\Core\Validate;
use Rcms\Core\User;

/**
 * Form for entering a new password.
 */
final class PasswordResetTemplate extends Template {

    private $requestToken;
    private $user;

    public function __construct(Text $text, User $user, RequestToken $requestToken) {
        parent::__construct($text);
        $this->requestToken = $requestToken;
        $this->user = $user;
    }

    public function writeText(StreamInterface $stream) {
        $text = $this->text;
        $resetToken = $this->user->getExtraData(User::DATA_PASSWORD_RESET_TOKEN, "");

        $stream->write(<<<HTML
            <p>
                {$text->tReplaced("users.password.edit.explained", Validate::$MIN_PASSWORD_LENGTH)}
            </p>
            <form action="{$text->url("reset_password", $this->user->getId())}" method="post">
                <p>
                    <label for="password">{$text->t('users.password')}:</label><span class="required">*</span><br />
                    <input type="password" id="password" name="password" value="" /><br />
                    <label for="password2">{$text->t('users.password.repeat')}:</label><span class="required">*</span><br />
                    <input type="password" id="password2" name="password2" value="" /><br />
                </p>

                <p>
                    <input type="hidden" name="token" value="{$text->e($resetToken)}" />
                    <input type="hidden" name="{$text->e(RequestToken::FIELD_NAME)}" value="{$text->e($this->requestToken->getTokenString())}" />

                    <input type="submit" class="button primary_button" value="{$text->t('users.password.edit')}" />
                </p>
            </form>
HTML
                );
    }

}
