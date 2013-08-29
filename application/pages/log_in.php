<?php

class LoginPage extends Page {

    public function getPageTitle(Website $oWebsite) {
        return $oWebsite->t("main.log_in") . '...';
    }

    public function get_short_page_title(Website $oWebsite) {
        return $oWebsite->t("main.log_in");
    }

    public function get_minimum_rank(Website $oWebsite) {
        return Authentication::$USER_RANK;
    }

    public function get_page_content(Website $oWebsite) {
        $admin_links = "";
        if ($oWebsite->isLoggedInAsStaff(true)) {
            $admin_links = <<<EOT
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
                $admin_links
            </p>
EOT;
    }

}

$this->registerPage(new LoginPage());
?>