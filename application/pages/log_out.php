<?php

class LoginPage extends Page {

    public function init(Website $oWebsite) {
        $oWebsite->getAuth()->log_out();
    }

    public function getPageTitle(Website $oWebsite) {
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
                <a href="{$oWebsite->getUrlMain()}" class="arrow">{$oWebsite->t("main.home")}</a>
            </p>
EOT;
    }

}

$this->registerPage(new LoginPage());
?>