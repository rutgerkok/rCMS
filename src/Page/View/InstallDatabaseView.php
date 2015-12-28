<?php

namespace Rcms\Page\View;

use Rcms\Core\Text;

/**
 * Page displayed when the database has not yet been set up.
 */
final class InstallDatabaseView extends View {
    
    public function __construct(Text $text) {
        parent::__construct($text);
    }
    
    public function getText() {
        return <<<HTML
            <p>{$this->text->t("install.no_tables_yet")}</p>
            <a href="{$this->text->getUrlPage("install", null, array("action" => "install_database"))}" class="button primary_button">
                {$this->text->t("install.create_tables")}
            </a>
HTML;
    }
}