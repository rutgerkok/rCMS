<?php

namespace Rcms\Page\View;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Text;

/**
 * Page displayed when the database needs to be updated.
 */
final class UpdateCompletedView extends View {
    
    public function __construct(Text $text) {
        parent::__construct($text);
    }
    
    public function writeText(StreamInterface $stream) {
        $text = $this->text;
        $stream->write(<<<HTML
            <p>{$text->t("install.thanks_for_updating")}</p>
            <p>         
                <a href="{$text->e($text->getUrlMain())}" class="arrow">
                {$text->t("main.home")}
                </a>
            </p>
HTML
        );
    }
}
