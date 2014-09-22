<?php

namespace Rcms\Page\View;

use Rcms\Core\Text;

/**
 * Description of SearchFormView
 */
class SearchFormView extends View {

    protected $keyword;

    public function __construct(Text $text, $keyword) {
        parent::__construct($text);
        $this->keyword = $keyword;
    }

    public function getText() {
        $keywordEsc = htmlSpecialChars($this->keyword);
        $text = $this->text;
        return <<<SEARCHFORM
            <p>
                <form action="{$text->getUrlPage("search")}" method="GET">
                    <input name="searchbox" id="searchbox-big" value="$keywordEsc" />
                    <input type="submit" class="button primary_button" value="{$text->t("main.search")}" />
                </form>
            </p>
            <p>{$text->t("articles.search.explained")}</p>
SEARCHFORM;
    }

}
