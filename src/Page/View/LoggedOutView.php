<?php

namespace Rcms\Page\View;

/**
 * Used on the logout page.
 */
class LoggedOutView extends View {

    public function getText() {
        $text = $this->text;
        return <<<EOT
            <h3>{$text->t('users.logged_out')}</h3>
            <p>{$text->t('users.succesfully_logged_out')}</p>
            <p>
                <a href="{$text->e($text->getUrlMain())}" class="arrow">{$text->t("main.home")}</a>
            </p>
EOT;
    }

}
