<?php

namespace Rcms\Page\View;

use Rcms\Core\Category;
use Rcms\Core\CategoryRepository;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;
use Rcms\Page\View\Support\RichEditor;
use Psr\Http\Message\StreamInterface;

/**
 * A form for editing a category.
 */
final class CategoryEditView extends View {

    /**
     * @var Category The category being edited.
     */
    private $category;

    /**
     * @var RequestToken Token for editing the request.
     */
    private $requestToken;

    /** @var RichEditor A rich editor. */
    private $richEditor;

    public function __construct(Text $text, Category $category,
            RichEditor $richEditor, RequestToken $requestToken) {
        parent::__construct($text);

        $this->category = $category;
        $this->requestToken = $requestToken;
        $this->richEditor = $richEditor;
    }

    public function writeText(StreamInterface $stream) {
        $text = $this->text;

        $stream->write(<<<HTML
            <p>
                {$text->t("main.fields_required")}
            </p>
            <form method="post" action="{$text->url("edit_category", $this->category->getId())}">
                <p>
                    <label for="category_name">{$text->t("categories.name")}:</label>
                    <span class="required">*</span>
                    <br />
                    <input type="text" id="category_name" name="category_name"
                        maxlength="{$text->e(CategoryRepository::NAME_MAX_LENGTH)}"
                        value="{$text->e($this->category->getName())}" />
                </p>
                <p>
                    <label for="category_description">{$text->t("categories.description")}:</label>
                    <br />
                    {$this->richEditor->getEditor("category_description", $this->category->getDescriptionHtml())}
                </p>
                <p>
                    <input type="hidden" name="{$text->e(RequestToken::FIELD_NAME)}"
                        value="{$text->e($this->requestToken->getTokenString())}" />
                    <input type="submit" class="button primary_button" value="{$text->t("editor.save")}" />
                    <a class="button" href="{$text->url("category_list")}">
                        {$text->t("main.cancel")}
                    </a>
                </p>
            </form>
HTML
        );
    }

}
