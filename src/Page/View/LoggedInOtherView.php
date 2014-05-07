<?php

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

/**
 * Shows that the user is now logged in as someone else.
 */
class LoggedInOtherView extends View {

    /**
     * @var User|null The user that just logged in.
     */
    private $newUser;

    public function __construct(Website $oWebsite, User $newUser = null) {
        parent::__construct($oWebsite);

        $this->newUser = $newUser;
    }

    public function getText() {
        if ($this->newUser === null) {
            return $this->getErrorText();
        } else {
            return $this->getSuccessText();
        }
    }

    protected function getErrorText() {
        $oWebsite = $this->oWebsite;

        return <<<MESSAGE
            <p>
                {$oWebsite->t('users.account')} {$oWebsite->t('errors.not_found')}
            </p>
            <p>
                <a class="arrow" href="{$oWebsite->getUrlMain()}">{$oWebsite->t("main.home")}</a>
            </p>
MESSAGE;
    }

    protected function getSuccessText() {
        $oWebsite = $this->oWebsite;
        $user = $this->newUser;

        return <<<MESSAGE
            <p>
                {$oWebsite->tReplaced('users.succesfully_loggedIn.other', htmlSpecialChars($user->getDisplayName()))}
            </p>
            <p>
                <a class="arrow" href="{$oWebsite->getUrlMain()}">{$oWebsite->t("main.home")}</a>
            </p>
MESSAGE;
    }

}
