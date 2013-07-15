<?php

class LoginPage extends Page {

    public function init(Website $oWebsite) {
        $oWebsite->get_authentication()->log_out();
    }

    public function get_page_title(Website $oWebsite) {
        return $oWebsite->t("main.log_out") . '...';
    }

    public function get_short_page_title(Website $oWebsite) {
        return $oWebsite->t("main.log_out");
    }

    public function get_page_content(Website $oWebsite) {
        return <<<EOT
            <h3>{$oWebsite->t('users.logged_out')}</h3>
            <p>{$oWebsite->t('users.succesfully_logged_out')}</p>
            <p>
                <a href="{$oWebsite->get_url_main()}" class="arrow">{$oWebsite->t("main.home")}</a>
            </p>
EOT;
    }

}

$this->register_page(new LoginPage());
?>