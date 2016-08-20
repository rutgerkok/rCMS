<?php

namespace Rcms\Template;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Category;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;

/**
 * Page for deleting a category.
 */
final class CategoryDeleteTemplate extends Template {

    /**
     * @var Category The category being deleted.
     */
    private $category;

    /**
     * @var RequestToken Token used to protect the request.
     */
    private $requestToken;

    public function __construct(Text $text, Category $category,
            RequestToken $requestToken) {
        parent::__construct($text);

        $this->category = $category;
        $this->requestToken = $requestToken;
    }

    public function writeText(StreamInterface $stream) {
        $text = $this->text;

        $stream->write(<<<HTML
            <p>{$text->t("categories.delete.are_you_sure")}</p>
            <blockquote>
                <h3 class="notable">{$text->e($this->category->getName())}</h3>
                {$this->category->getDescriptionHtml()}
            </blockquote>
            <form method="post" action="{$text->url("delete_category", $this->category->getId())}">
                <p>
                    <input type="hidden" name="{$text->e(RequestToken::FIELD_NAME)}" value="{$text->e($this->requestToken->getTokenString())}" />
                    <input type="submit" class="button dangerous_button" value="{$text->t("editor.delete_permanently")}" />
                    <a class="button" href="{$text->url("category_list")}">{$text->t("main.cancel")}</a>
                </p>
            </form>
HTML
        );
    }

}
