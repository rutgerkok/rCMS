<?php

namespace Rcms\Page\View;

use Rcms\Core\Article;
use Rcms\Core\Text;

/**
 * Used on the logout page.
 */
class ArticleDeleteView extends View {

    const STATE_CONFIRMATION = 0;
    const STATE_DELETED = 1;
    const STATE_HIDDEN = 2;
    const STATE_ERROR = 3;

    /** @var Article $article The article being deleted. */
    protected $article;
    protected $state;
    protected $showAdminPageLink;

    public function __construct(Text $text, Article $articleToDelete,
            $showAdminPageLink, $state) {
        parent::__construct($text);
        $this->article = $articleToDelete;
        $this->state = (int) $state;
        $this->showAdminPageLink = (boolean) $showAdminPageLink;
    }

    public function getText() {
        switch ($this->state) {
            case self::STATE_CONFIRMATION:
                return $this->getConfirmationText();
            case self::STATE_ERROR:
                return $this->getErrorText();
            case self::STATE_HIDDEN:
                return $this->getMadeHiddenText();
            case self::STATE_DELETED:
                return $this->getDeletedText();
        }
        throw new BadMethodCallException("Unknown display state " . $this->state);
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
        $returnValue.= $text->t("editor.article.delete.done");
        $returnValue.= "</p>\n";

        $returnValue.= "<p>";
        $returnValue.= '<a class="arrow" href="' . $text->getUrlMain() . '">' . $text->t("main.home") . '</a> ';
        if ($this->showAdminPageLink) {
            $returnValue.= '<a class="arrow" href="' . $text->getUrlMain() . '">' . $text->t("main.admin") . '</a> ';
        }
        $returnValue.= "</p>";
        return $returnValue;
    }

    protected function getMadeHiddenText() {
        $text = $this->text;
        $article = $this->article;
        $returnValue = "<p>";
        $returnValue.= $text->t("editor.article.hidden.done");
        $returnValue.= "</p>\n";

        $returnValue.= "<p>";
        $returnValue.= '<a class="arrow" href="' . $text->getUrlPage("article", $article->id) . '">' . $text->t("articles.view") . '</a> ';
        if ($this->showAdminPageLink) {
            $returnValue.= '<a class="arrow" href="' . $text->getUrlMain() . '">' . $text->t("main.admin") . '</a> ';
        }
        $returnValue.= "</p>";
        return $returnValue;
    }

    protected function getConfirmationText() {
        $text = $this->text;
        $article = $this->article;
        $returnValue = <<<EOT
            <p>{$text->t('editor.article.delete.confirm')}</p>
            <p>
                <a class="button primary_button" href="{$text->getUrlPage("delete_article", $article->id, array("action" => "delete"))}">
                    {$text->t("main.yes")}
                </a>
EOT;
        if (!$article->hidden) {
            // Option to hide article is only relevant when the article
            // isn't already hidden
            $returnValue.= <<<EOT
                <a class="button" href="{$text->getUrlPage("delete_article", $article->id, array("action" => "make_private"))}">
                    {$text->t("editor.article.delete.make_hidden_instead")}
                </a>
EOT;
        }
        $returnValue.= <<<EOT
                <a class="button" href="{$text->getUrlPage("article", $article->id)}">
                    {$text->t("main.no")}
                </a>
            </p>
EOT;
        return $returnValue;
    }

}
