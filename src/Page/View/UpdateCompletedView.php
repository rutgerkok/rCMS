<?php

namespace Rcms\Page\View;

use Rcms\Core\Text;

/**
 * Page displayed when the database needs to be updated.
 */
final class UpdateCompletedView extends View {
    
    public function __construct(Text $text) {
        parent::__construct($text);
    }
    
    public function getText() {
        $text = $this->text;
        return <<<HTML
            <p>{text->t("install.thanks_for_updating")}</p>
            <p>         
                <a href="{$text->e($text->getUrlMain())}" class="arrow">
                {$text->t("main.home")}
                </a>
            </p>
HTML;
    }
}
