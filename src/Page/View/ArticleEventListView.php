<?php

namespace Rcms\Page\View;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Text;
use Rcms\Core\Article;

/**
 * Gets the articles as an event list, so with the event date displayed.
 */
final class ArticleEventListView extends View {

    /**
     * @var Article[] List of articles.
     */
    protected $articles;

    /**
     * @var boolean True to show edit links, false otherwise.
     */
    protected $editLinks;

    /**
     * Creates a new view of a list of articles.
     * @param Text $text The website object.
     * @param Article[] $articles List of articles to display.
     * @param boolean $editLinks True to show edit links, false otherwise..
     */
    public function __construct(Text $text, $articles, $editLinks) {
        parent::__construct($text);
        $this->articles = $articles;
        $this->editLinks = (boolean) $editLinks;
    }

    public function writeText(StreamInterface $stream) {
        $stream->write($this->getArticlesList());
    }

    private function getArticlesList() {
        $text = $this->text;

        // Build article list
        $returnValue = '';
        if (count($this->articles) > 0) {
            $returnValue = '<ul class="calendar_list">';
            foreach ($this->articles as $article) {
                $returnValue.= $this->getArticleTextListEntry($article);
            }
            $returnValue .= "</ul>\n";
        }

        // Add create new article link
        if ($this->editLinks) {
            $returnValue .= '<p><a class="arrow" href="' . $text->e($text->getUrlPage("edit_article", 0));
            $returnValue .= '">' . $text->t("articles.create") . "</a></p>\n";
        }
        
        // Write calendar links
        $year = (int) date("Y");
        $month = (int) date("n");
        $returnValue.= <<<HTML
            <p> 
                <a class="arrow" href="{$text->e($text->getUrlPage("calendar", $year))}">
                    {$text->tReplaced("calendar.calendar_for_year", $year)}
                </a>
HTML;
        if ($month === 12) {
            // It's December, include link for next year
            $returnValue.= <<<HTML
                <br>
                <a class="arrow" href="{$text->e($text->getUrlPage("calendar", $year + 1))}">
                    {$text->tReplaced("calendar.calendar_for_year", $year + 1)}
                </a>  
HTML;
        }
        $returnValue.= "</p>";  

        return $returnValue;
    }

    private function getArticleTextListEntry(Article $article) {
        $text = $this->text;
        if ($article->onCalendar === null) {
            return "";
        }
        $computerDateTime = $article->onCalendar->format("c");
        
        return <<<ARTICLE
            <li>
                <a href="{$text->e($text->getUrlPage("article", $article->getId()))}" class="date disguised_link">
                    <time datetime="{$text->e($computerDateTime)}">
                        {$text->formatEventDateTime($article->onCalendar, '<br />')}
                    </time>
                </a>
                <span class="title">
                    <a href="{$text->e($text->getUrlPage("article", $article->getId()))}">
                        {$text->e($article->getTitle())}
                    </a>
                    {$this->getArticleEditLinks($article)}
                </span>
            </li>
ARTICLE;
    }
    
    private function getArticleEditLinks(Article $article) {
        if (!$this->editLinks) {
            return "";
        }

        $text = $this->text;
        return <<<EDIT_LINKS
            <a class="arrow" href="{$text->e($text->getUrlPage("edit_article", $article->getId()))}">
                {$text->t("main.edit")}
            </a>
            <a class="arrow" href="{$text->e($text->getUrlPage("delete_article", $article->getId()))}">
                {$text->t("main.delete")}
            </a>
EDIT_LINKS;
    }

}
