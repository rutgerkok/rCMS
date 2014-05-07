<?php

namespace Rcms\Page\View;

use Rcms\Core\Article;
use Rcms\Core\Website;

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

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

    public function __construct(Website $oWebsite, Article $articleToDelete,
            $state) {
        parent::__construct($oWebsite);
        $this->article = $articleToDelete;
        $this->state = (int) $state;
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
        $oWebsite = $this->oWebsite;
        $errorMessage = "<p><em>";
        $errorMessage.= $oWebsite->t("main.article") . ' ' . $oWebsite->t("errors.is_not_removed");
        $errorMessage.= "</em></p>\n";
        $errorMessage.= $this->getConfirmationText();
        return $errorMessage;
    }

    protected function getDeletedText() {
        $oWebsite = $this->oWebsite;
        $returnValue = "<p>";
        $returnValue.= $oWebsite->t("editor.article.delete.done");
        $returnValue.= "</p>\n";

        $returnValue.= "<p>";
        $returnValue.= '<a class="arrow" href="' . $oWebsite->getUrlMain() . '">' . $oWebsite->t("main.home") . '</a> ';
        if ($oWebsite->isLoggedInAsStaff(true)) {
            $returnValue.= '<a class="arrow" href="' . $oWebsite->getUrlMain() . '">' . $oWebsite->t("main.admin") . '</a> ';
        }
        $returnValue.= "</p>";
        return $returnValue;
    }

    protected function getMadeHiddenText() {
        $oWebsite = $this->oWebsite;
        $article = $this->article;
        $returnValue = "<p>";
        $returnValue.= $oWebsite->t("editor.article.hidden.done");
        $returnValue.= "</p>\n";

        $returnValue.= "<p>";
        $returnValue.= '<a class="arrow" href="' . $oWebsite->getUrlPage("article", $article->id) . '">' . $oWebsite->t("articles.view") . '</a> ';
        if ($oWebsite->isLoggedInAsStaff(true)) {
            $returnValue.= '<a class="arrow" href="' . $oWebsite->getUrlMain() . '">' . $oWebsite->t("main.admin") . '</a> ';
        }
        $returnValue.= "</p>";
        return $returnValue;
    }

    protected function getConfirmationText() {
        $oWebsite = $this->oWebsite;
        $article = $this->article;
        $returnValue = <<<EOT
            <p>{$oWebsite->t('editor.article.delete.confirm')}</p>
            <p>
                <a class="button primary_button" href="{$oWebsite->getUrlPage("delete_article", $article->id, array("action" => "delete"))}">
                    {$oWebsite->t("main.yes")}
                </a>
EOT;
        if (!$article->hidden) {
            // Option to hide article is only relevant when the article
            // isn't already hidden
            $returnValue.= <<<EOT
                <a class="button" href="{$oWebsite->getUrlPage("delete_article", $article->id, array("action" => "make_private"))}">
                    {$oWebsite->t("editor.article.delete.make_hidden_instead")}
                </a>
EOT;
        }
        $returnValue.= <<<EOT
                <a class="button" href="{$oWebsite->getUrlPage("article", $article->id)}">
                    {$oWebsite->t("main.no")}
                </a>
            </p>
EOT;
        return $returnValue;
    }

}
