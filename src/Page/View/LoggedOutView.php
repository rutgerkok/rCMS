<?php

namespace Rcms\Page\View;

use Psr\Http\Message\StreamInterface;

/**
 * Used on the logout page.
 */
class LoggedOutView extends View {

    public function writeText(StreamInterface $stream) {
        $text = $this->text;
        $stream->write(<<<EOT
            <h3>{$text->t('users.logged_out')}</h3>
            <p>{$text->t('users.succesfully_logged_out')}</p>
            <p>
                <a href="{$text->e($text->getUrlMain())}" class="arrow">{$text->t("main.home")}</a>
            </p>
EOT
        );
    }

}
