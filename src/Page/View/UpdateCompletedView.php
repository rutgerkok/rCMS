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
        return <<<HTML
            <p>{$this->text->t("install.thanks_for_updating")}</p>
            <p>         
                <a href="{$this->text->getUrlMain()}" class="arrow">
                {$this->text->t("main.home")}
                </a>
            </p>
HTML;
    }
}
