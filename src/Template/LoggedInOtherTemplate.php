<?php

namespace Rcms\Template;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\User;
use Rcms\Core\Text;

/**
 * Shows that the user is now logged in as someone else.
 */
class LoggedInOtherTemplate extends Template {

    /**
     * @var User|null The user that just logged in.
     */
    private $newUser;

    public function __construct(Text $text, User $newUser = null) {
        parent::__construct($text);

        $this->newUser = $newUser;
    }

    public function writeText(StreamInterface $stream) {
        if ($this->newUser === null) {
            $stream->write($this->getErrorText());
        } else {
            $stream->write($this->getSuccessText());
        }
    }

    protected function getErrorText() {
        $text = $this->text;

        return <<<MESSAGE
            <p>
                {$text->t('users.account')} {$text->t('errors.not_found')}
            </p>
            <p>
                <a class="arrow" href="{$text->e($text->getUrlMain())}">{$text->t("main.home")}</a>
            </p>
MESSAGE;
    }

    protected function getSuccessText() {
        $text = $this->text;
        $user = $this->newUser;

        return <<<MESSAGE
            <p>
                {$text->tReplaced('users.succesfully_loggedIn.other', htmlSpecialChars($user->getDisplayName()))}
            </p>
            <p>
                <a class="arrow" href="{$text->e($text->getUrlMain())}">{$text->t("main.home")}</a>
            </p>
MESSAGE;
    }

}
