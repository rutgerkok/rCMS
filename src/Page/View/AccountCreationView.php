<?php

namespace Rcms\Page\View;

use Rcms\Core\RequestToken;
use Rcms\Core\Text;
use Rcms\Core\User;
use Psr\Http\Message\StreamInterface;

/**
 * View for the self-creation of a user account.
 */
final class AccountCreationView extends View {

    /**
     * @var User The user being created.
     */
    private $user;

    /**
     * @var RequestToken Token for protecting the request.
     */
    private $requestToken;

    public function __construct(Text $text, User $user, RequestToken $requestToken) {
        parent::__construct($text);

        $this->user = $user;
        $this->requestToken = $requestToken;
    }


    public function writeText(StreamInterface $stream) {
        $text = $this->text;
        $user = $this->user;

        $stream->write(<<<HTML
            <p>
                {$text->t("users.create.explained")}
            </p>
            <p>
                {$text->t("main.fields_required")}
            </p>

            <form action="{$text->url("create_account")}" method="post">
                <p>
                    <label for="creating_username">{$text->t("users.username")}<span class="required">*</span>:</label><br />
                    <input type="text" id="creating_username" name="creating_username" value="{$text->e($user->getUsername())}" /><br />

                    <label for="creating_display_name">{$text->t("users.display_name")}<span class="required">*</span>:</label><br />
                    <input type="text" id="creating_display_name" name="creating_display_name" value="{$text->e($user->getDisplayName())}" /><br />

                    <label for="creating_password1">{$text->t("users.password")}<span class="required">*</span>:</label><br />
                    <input type="password" id="creating_password1" name="creating_password1" value=""/><br />

                    <label for="creating_password2">{$text->t("users.password.repeat")}<span class="required">*</span>:</label><br />
                    <input type="password" id="creating_password2" name="creating_password2" value=""/><br />

                    <label for="creating_email">{$text->t("users.email")}:</label><br />
                    <input type="email" id="creating_email" name="creating_email" value="{$text->e($user->getEmail())}" /><br />
                </p>
                <p>
                    <input type="hidden" name="{$text->e(RequestToken::FIELD_NAME)}" value="{$text->e($this->requestToken->getTokenString())}" />
                    <input type="submit" value="{$text->t("main.create_account")}" class="button primary_button" />
                </p>
            </form>

HTML
                );
    }

}
