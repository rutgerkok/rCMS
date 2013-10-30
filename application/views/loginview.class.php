<?php

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

/**
 * View for the login screen.
 */
class LoginView extends View {

    protected $minimumRank;
    protected $customErrorMessage;

    /**
     * Creates a new login view.
     * @param Website $oWebsite The website instance.
     * @param int $minimumRank The minimum required rank to access this page.
     * This will be displayed on top of the page. Set this to
     * Authentication::$LOGGED_OUT_RANK to display no message on top of the
     * page.
     */
    public function __construct(Website $oWebsite, $minimumRank) {
        parent::__construct($oWebsite);
        $this->minimumRank = $minimumRank;
    }

    /**
     * Gets the error message to display on the login form, like "Wrong
     * password" or "You need to be logged in to view this page".
     * @return string The error message, or empty if there is no message.
     */
    protected function getLoginErrorMessage() {
        $oWebsite = $this->oWebsite;
        $oAuth = $oWebsite->getAuth();
        $errorMessage = "";
        if ($oAuth->hasLoginFailed()) {
            $errorMessage = $oWebsite->t("errors.invalid_login_credentials");
        } elseif ($oAuth->isHigherOrEqualRank($this->minimumRank, Authentication::$MODERATOR_RANK)) {
            $errorMessage = $oWebsite->t("users.must_be_logged_in_as_administrator");
        } elseif ($oAuth->isValidRankForAccounts($this->minimumRank)) {
            $errorMessage = $oWebsite->t("users.must_be_logged_in");
        }
        return $errorMessage;
    }

    public function getText() {
        $oWebsite = $this->oWebsite;
        $oAuth = $oWebsite->getAuth();
        $errorMessage = $this->getLoginErrorMessage($oAuth, $this->minimumRank);

        $loginText = $oWebsite->t("users.please_log_in");
        $returnValue = "";
        if ($errorMessage && $oWebsite->getErrorCount() == 0) {
            // Only display the standard error if there was no other error
            $returnValue.= <<<EOT
                <div class="error">
                    <p>$errorMessage</p>
                </div>
EOT;
        }
        $returnValue.= <<<EOT
            <form method="post" action="{$oWebsite->getUrlMain()}">
                    <h3>$loginText</h3>
                    <p>
                            <label for="user">{$oWebsite->t('users.username_or_email')}:</label> <br />
                            <input type="text" name="user" id="user" autofocus="autofocus" /> <br />
                            <label for="pass">{$oWebsite->t('users.password')}:</label> <br />
                            <input type="password" name="pass" id="pass" /> <br />

                            <input type="submit" value="{$oWebsite->t('main.log_in')}" class="button primary_button" />

EOT;
        // Repost all variables
        foreach ($_REQUEST as $key => $value) {
            if ($key != "user" && $key != "pass") {
                $returnValue.= '<input type="hidden" name="' . htmlSpecialChars($key) . '" value="' . htmlSpecialChars($value) . '" />';
            }
        }

        // End form and return it
        $returnValue.= <<<EOT
                    </p>
            </form>
EOT;
        return $returnValue;
    }

}
