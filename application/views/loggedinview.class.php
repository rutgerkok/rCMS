<?php

/**
 * Used on the login page when an user has successfully logged in.
 */
class LoggedInView extends View {

    public function getText() {
        $oWebsite = $this->oWebsite;
        $adminLinks = "";
        if ($oWebsite->isLoggedInAsStaff(true)) {
            $adminLinks = <<<EOT
                    <br />
                    <a href="{$oWebsite->getUrlPage("account_management")}" class="arrow">{$oWebsite->t("main.account_management")}</a>
                    <br />
                    <a href="{$oWebsite->getUrlPage("admin")}" class="arrow">{$oWebsite->t("main.admin")}</a>
EOT;
        }

        return <<<EOT
                <h3>{$oWebsite->t('users.loggedIn')}</h3>
                <p>{$oWebsite->t('users.succesfully_loggedIn')}</p>
                <p>
                    <a href="{$oWebsite->getUrlMain()}" class="arrow">{$oWebsite->t("main.home")}</a>
                    <br />
                    <a href="{$oWebsite->getUrlPage("account")}" class="arrow">{$oWebsite->t("main.my_account")}</a>
                    $adminLinks
                </p>
EOT;
    }

}
