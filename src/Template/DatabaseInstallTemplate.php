<?php

namespace Rcms\Template;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Text;

/**
 * Page displayed when the database has not yet been set up.
 */
final class DatabaseInstallTemplate extends Template {
    
    public function __construct(Text $text) {
        parent::__construct($text);
    }
    
    public function writeText(StreamInterface $stream) {
        $text = $this->text;
        $stream->write(<<<HTML
            <p>{$text->t("install.no_tables_yet")}</p>
            <a href="{$text->e($text->getUrlPage("install", null, ["action" => "install_database"]))}" class="button primary_button">
                {$text->t("install.create_tables")}
            </a>
HTML
        );
    }
}
