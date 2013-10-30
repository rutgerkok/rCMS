<?php

/**
 * Used on the logout page.
 */
class LoggedOutView extends View {
    
    public function getText() {
        $oWebsite = $this->oWebsite;
        return <<<EOT
            <h3>{$oWebsite->t('users.logged_out')}</h3>
            <p>{$oWebsite->t('users.succesfully_logged_out')}</p>
            <p>
                <a href="{$oWebsite->getUrlMain()}" class="arrow">{$oWebsite->t("main.home")}</a>
            </p>
EOT;
    }

}
