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
        $text = $this->text;
        return <<<HTML
            <p>{$text->t("install.no_tables_yet")}</p>
            <a href="{$text->e($text->getUrlPage("install", null, array("action" => "install_database")))}" class="button primary_button">
                {$text->t("install.create_tables")}
            </a>
HTML;
    }
}
