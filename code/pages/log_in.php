<?php

class LoginPage extends Page {

    public function get_page_title(Website $oWebsite) {
        return $oWebsite->t("main.log_in") . '...';
    }

    public function get_short_page_title(Website $oWebsite) {
        return $oWebsite->t("main.log_in");
    }

    public function get_minimum_rank() {
        return Authentication::$USER_RANK;
    }

    public function get_page_content(Website $oWebsite) {
        return <<<EOT
            <h3>{$oWebsite->t('users.logged_in')}</h3>
            <p>{$oWebsite->t('users.succesfully_logged_in')}</p>
            <p>
                <a href="{$oWebsite->get_url_main()}" class="arrow">{$oWebsite->t("main.home")}</a>
                <br />
                <a href="{$oWebsite->get_url_page("account_management")}" class="arrow">{$oWebsite->t("main.account_management")}</a>
            </p>
EOT;
    }

}

$this->register_page(new LoginPage());
?>