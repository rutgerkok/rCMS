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
        return <<<HTML
            <p>{$this->text->t("install.no_database_connection_explained")}</p>
            <p>
                <a class="button primary_button" href="{$this->text->getUrlPage("install")}">
                    {$this->text->t("install.retry_connection")}
                </a>
            </p>
HTML;
    }
}
