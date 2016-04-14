<?php

namespace Rcms\Page\View;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Article;
use Rcms\Core\Category;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;
use Rcms\Page\View\Support\RichEditor;

/**
 * The main article editor view.
 */
class ArticleEditView extends View {

    /** @var Article Article being edited. */
    private $article;

    /** @var RequestToken Token used to protect the request. */
    private $requestToken;

    /** @var RichEditor A rich editor. */
    private $richEditor;

    /** @var Category[] All categories the article can be assigned to. */
    private $availableCategories;

    public function __construct(Text $text, Article $article,
            RequestToken $requestToken, RichEditor $richEditor,
            array $availableCategories) {
        parent::__construct($text);
        $this->article = $article;
        $this->requestToken = $requestToken;
        $this->richEditor = $richEditor;
        $this->availableCategories = $availableCategories;
    }

    public function writeText(StreamInterface $stream) {
        $text = $this->text;
        $article = $this->article;
        $title = htmlSpecialChars($article->getTitle());
        $intro = htmlSpecialChars($article->getIntro());
        $body = $article->getBody(); // Will be escaped by the get_editor method

        $tokenName = RequestToken::FIELD_NAME;
        $tokenHtml = htmlSpecialChars($this->requestToken->getTokenString());

        // Create form
        $stream->write(<<<ARTICLE_FORM
            <script type="text/javascript" src="{$text->getUrlJavaScript("datepicker")}"></script>
            <form action="{$text->e($text->getUrlPage("edit_article", $article->getId()))}" method="post">
                <p>
                    <label for="article_title">{$text->t("articles.title")}:<span class="required">*</span></label>
                    <br />
                    <input type="text" id="article_title" name="article_title" class="full_width" value="$title" />
                </p>
                <p>
                    {$text->t("main.fields_required")}
                </p>

                <div id="sidebar_page_sidebar">
                    <fieldset>
                        <legend>{$text->t("articles.featured_image")}</legend>
                        {$this->getFeaturedImageHtml()}
                    </fieldset>
                    <fieldset>
                        <legend>{$text->t("editor.other_options")}</legend>
                        {$this->getOtherOptionsHtml()}
                    </fieldset>
                    <fieldset>
                        <legend>{$text->t("articles.event_date")}</legend>
                        {$this->getEventDateHtml()}
                    </fieldset>
                </div>

                <div id="sidebar_page_content">
                    <p>
                        {$this->getButtons()}
                    </p>
                    <p>
                        <label for="article_intro">{$text->t("articles.intro")}:<span class="required">*</span></label>
                        <br />
                        <textarea id="article_intro" name="article_intro" rows="3" class="full_width">$intro</textarea>
                    </p>
                    <p>
                        <label for="article_body">{$text->t("articles.body")}:<span class="required">*</span></label>
                        <br />
                        {$this->richEditor->getEditor("article_body", $body)}
                    </p>
                    <p>
                        <input type="hidden" name="{$tokenName}" value="{$tokenHtml}" />
                        {$this->getButtons()}
                    </p>
                </div>

                <div style="clear:both"></div>
            </form>
ARTICLE_FORM
        );
    }

    private function getButtons() {
        $text = $this->text;
        $article = $this->article;
        return <<<BUTTONS
            <input type="submit" name="submit" class="button primary_button" value="{$text->t("editor.save")}" />
            <input type="submit" name="submit" class="button" value="{$text->t("editor.save_and_quit")}" />
            <a class="button" href="{$text->e($text->getUrlPage("article", $article->getId()))}">{$text->t("editor.quit")}</a>
BUTTONS;
    }

    private function getFeaturedImageHtml() {
        $featuredImageUrl = htmlSpecialChars($this->article->featuredImage);
        if ($featuredImageUrl) {
            $featuredImageTag = '<img src="' . $featuredImageUrl . '" />';
        } else {
            $featuredImageTag = '<em>' . $this->text->t("editor.no_image_selected") . '</em>';
        }

        return <<<FORM
            <p>
                <span id="article_editor_image">
                    $featuredImageTag
                </span>
                <br />
                {$this->richEditor->getImageChooser("article_featured_image", $featuredImageUrl)}
            </p>
FORM;
    }

    private function getEventDateHtml() {
        $text = $this->text;

        // Date and time
        $date = "";
        $time = "";
        if ($this->article->onCalendar !== null) {
            $date = $this->article->onCalendar->format("Y-m-d");
            $time = $this->article->onCalendar->format("H:i");
        }

        return <<<FORM
            <p>
                {$text->t("articles.event_date.explained")}
            </p>
            <p>
                <label for="article_eventdate">
                    {$text->t("calendar.date")}:
                    <br />
                    <input type="date" id="article_eventdate" name="article_eventdate" value="$date" style="width:10em" />
                    <input type="button" class="button" value="{$text->t("articles.event_date.select")}" onclick="showDatePicker()" />
                </label>
            </p>
            <p>
                <label for="article_eventtime">
                    {$text->t("calendar.time")}:
                    <br />
                    <input type="time" id="article_eventtime" name="article_eventtime" value="$time" style="width:10em" />
                </label>
            </p>
FORM;
    }

    private function getOtherOptionsHtml() {
        $hidden = $this->article->isHidden() ? 'checked="checked"' : '';
        $pinned = $this->article->pinned ? 'checked="checked"' : '';
        $show_comments = $this->article->showComments ? 'checked="checked"' : '';
        $text = $this->text;

        return <<<FORM
            <p>
                <label for="article_hidden" title="{$text->t("articles.hidden.explained")}" style="cursor:help">
                    <input type="checkbox" id="article_hidden" name="article_hidden" class="checkbox" $hidden />
                    {$text->t("articles.hidden")}
                </label>
                <br />
                <label for="article_pinned" title="{$text->t("articles.pinned.explained")}" style="cursor:help">
                    <input type="checkbox" id="article_pinned" name="article_pinned" class="checkbox" $pinned />
                    {$text->t("articles.pinned")}
                </label>
                <br />
                <label for="article_comments" title="{$text->t("comments.allow_explained")}" style="cursor:help">
                    <input type="checkbox" id="article_comments" name="article_comments" class="checkbox" $show_comments />
                    {$text->t("comments.comments")}
                </label>
            </p>
            <p>
                <label for="article_category">{$text->t("main.category")}<span class="required">*</span></label>
                <br />
                {$this->getCategoryListHtml()}
            </p>
FORM;
    }

    /**
     * Gets a HTML selector of all available categories, with the current
     * category already selected.
     * @return string The HTML selector.
     */
    private function getCategoryListHtml() {
        $articleCategoryId = $this->article->categoryId;
        $html = '<select name="article_category" id="article_category" class="button" style="width:100%">';

        foreach ($this->availableCategories as $category) {
            $categoryName = htmlSpecialChars($category->getName());
            $categoryId = $category->getId();
            $selected = $categoryId == $articleCategoryId ? 'selected="selected"' : "";

            $html.= <<<OPTION
                <option value="$categoryId" $selected>$categoryName</option>
OPTION;
        }

        $html.="</select>";
        return $html;
    }

}
