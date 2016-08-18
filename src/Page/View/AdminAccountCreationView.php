<?php

namespace Rcms\Page\View;

use Rcms\Core\RequestToken;
use Rcms\Core\Text;
use Rcms\Core\User;
use Psr\Http\Message\StreamInterface;

/**
 * View for the self-creation of a user account.
 */
final class AdminAccountCreationView extends View {

    /**
     * @var User The user being created.
     */
    private $user;

    /**
     *
     * @var string[] Untranslated rank names, indexed by rank id.
     */
    private $allRanks;

    /**
     * @var RequestToken Token for protecting the request.
     */
    private $requestToken;

    /**
     *
     * @param Text $text The text object.
     * @param User $user The user being added.
     * @param string[] $allRanks Untranslated rank names, indexed by rank id.
     * @param RequestToken $requestToken Token for protecting the request.
     */
    public function __construct(Text $text, User $user, array $allRanks, RequestToken $requestToken) {
        parent::__construct($text);

        $this->user = $user;
        $this->allRanks = $allRanks;
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

            <form action="{$text->url("create_account_admin")}" method="post">
                <p>
                    <label for="creating_username">{$text->t("users.username")}<span class="required">*</span>:</label><br />
                    <input type="text" id="creating_username" name="creating_username" value="{$text->e($user->getUsername())}" /><br />

                    <label for="creating_display_name">{$text->t("users.display_name")}<span class="required">*</span>:</label><br />
                    <input type="text" id="creating_display_name" name="creating_display_name" value="{$text->e($user->getDisplayName())}" /><br />

                    <label for="creating_password">{$text->t("users.password")}<span class="required">*</span>:</label><br />
                    <input type="password" id="creating_password" name="creating_password" value=""/><br />

                    <label for="creating_email">{$text->t("users.email")}:</label><br />
                    <input type="email" id="creating_email" name="creating_email" value="{$text->e($user->getEmail())}" /><br />

                    <label for="creating_email">{$text->t("users.rank")}:<span class="required">*</span>:</label><br />
                    {$this->getRankSelection()}
                </p>
                <p>
                    <input type="hidden" name="{$text->e(RequestToken::FIELD_NAME)}" value="{$text->e($this->requestToken->getTokenString())}" />
                    <input type="submit" value="{$text->t("main.create_account")}" class="button primary_button" />
                    <a class="button" href="{$text->url("account_management")}">
                        {$text->t("main.cancel")}
                    </a>
                </p>
            </form>

HTML
        );
    }

    private function getRankSelection() {
        $text = $this->text;

        $html = '<select id="creating_rank" name="creating_rank">';
        $selectedRank = $this->user->getRank();
        foreach ($this->allRanks as $rankId => $rankName) {
            $html.= '<option value="' . $text->e($rankId) . '"';
            if ($selectedRank === $rankId) {
                $html.= ' selected="selected"';
            }
            $html.= '>' . $text->t($rankName) . "</option>";
        }
        $html.= '</select>';

        return $html;
    }

}
