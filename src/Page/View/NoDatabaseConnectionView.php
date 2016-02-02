<?php

namespace Rcms\Page\View;

use Rcms\Core\Text;

/**
 * Page displayed when the database has not yet been set up.
 */
final class NoDatabaseConnectionView extends View {
    
    public function __construct(Text $text) {
        parent::__construct($text);
    }
    
    public function getText() {
        $text = $this->text;

        return <<<HTML
            <p>{$text->t("install.no_database_connection_explained")}</p>
            <p>
                <a class="button primary_button" href="{$text->e($text->getUrlPage("install"))}">
                    {$text->t("install.retry_connection")}
                </a>
            </p>
HTML;
    }
}
