<?php

namespace Rcms\Template;

use BadMethodCallException;
use Psr\Http\Message\StreamInterface;
use Rcms\Core\Article;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;

/**
 * Used on the logout page.
 */
class ArticleDeleteTemplate extends Template {

    const STATE_CONFIRMATION = 0;
    const STATE_DELETED = 1;
    const STATE_HIDDEN = 2;
    const STATE_ERROR = 3;

    /** @var Article The article being deleted. */
    protected $article;
    protected $state;
    protected $showAdminPageLink;
    /** @var RequestToken Token needed to delete articles. */
    protected $requestToken;

    public function __construct(Text $text, Article $articleToDelete,
            RequestToken $requestToken, $showAdminPageLink, $state) {
        parent::__construct($text);
        $this->article = $articleToDelete;
        $this->requestToken = $requestToken;
        $this->state = (int) $state;
        $this->showAdminPageLink = (boolean) $showAdminPageLink;
    }

    public function writeText(StreamInterface $stream) {
        switch ($this->state) {
            case self::STATE_CONFIRMATION:
                $stream->write($this->getConfirmationText());
                break;
            case self::STATE_ERROR:
                $stream->write($this->getErrorText());
                break;
            case self::STATE_HIDDEN:
                $stream->write($this->getMadeHiddenText());
                break;
            case self::STATE_DELETED:
                $stream->write($this->getDeletedText());
                break;
            default:
                throw new BadMethodCallException("Unknown display state " . $this->state);
        }
    }

    protected function getErrorText() {
        $text = $this->text;
        $errorMessage = "<p><em>";
        $errorMessage.= $text->t("main.article") . ' ' . $text->t("errors.is_not_removed");
        $errorMessage.= "</em></p>\n";
        $errorMessage.= $this->getConfirmationText();
        return $errorMessage;
    }

    protected function getDeletedText() {
        $text = $this->text;
        $returnValue = "<p>";
        $returnValue.= $text->t("articles.delete.done");
        $returnValue.= "</p>\n";

        $returnValue.= "<p>";
        $returnValue.= '<a class="arrow" href="' . $text->e($text->getUrlMain()) . '">' . $text->t("main.home") . '</a> ';
        if ($this->showAdminPageLink) {
            $returnValue.= '<a class="arrow" href="' . $text->e($text->getUrlMain()) . '">' . $text->t("main.admin") . '</a> ';
        }
        $returnValue.= "</p>";
        return $returnValue;
    }

    protected function getMadeHiddenText() {
        $text = $this->text;
        $article = $this->article;
        $returnValue = "<p>";
        $returnValue.= $text->t("articles.hide.done");
        $returnValue.= "</p>\n";

        $returnValue.= "<p>";
        $returnValue.= '<a class="arrow" href="' . $text->e($text->getUrlPage("article", $article->getId())) . '">' . $text->t("articles.view") . '</a> ';
        if ($this->showAdminPageLink) {
            $returnValue.= '<a class="arrow" href="' . $text->e($text->getUrlMain()) . '">' . $text->t("main.admin") . '</a> ';
        }
        $returnValue.= "</p>";
        return $returnValue;
    }

    protected function getConfirmationText() {
        $text = $this->text;
        $article = $this->article;
        $deleteUrlParams = ["action" => "delete", RequestToken::FIELD_NAME => $this->requestToken->getTokenString()];
        $returnValue = <<<EOT
            <p>{$text->t('articles.delete.confirm')}</p>
            <p>
                <a class="button dangerous_button" href="{$text->e($text->getUrlPage("delete_article", $article->getId(), $deleteUrlParams))}">
                    {$text->t("main.yes")}
                </a>
EOT;
        if (!$article->isHidden()) {
            $hideUrlParams = ["action" => "make_private", RequestToken::FIELD_NAME => $this->requestToken->getTokenString()];
            // Option to hide article is only relevant when the article
            // isn't already hidden
            $returnValue.= <<<EOT
                <a class="button" href="{$text->e($text->getUrlPage("delete_article", $article->getId(), $hideUrlParams))}">
                    {$text->t("articles.delete.make_hidden_instead")}
                </a>
EOT;
        }
        $returnValue.= <<<EOT
                <a class="button" href="{$text->e($text->getUrlPage("article", $article->getId()))}">
                    {$text->t("main.no")}
                </a>
            </p>
EOT;
        return $returnValue;
    }

}
