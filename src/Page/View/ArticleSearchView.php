<?php

namespace Rcms\Page\View;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Text;

/**
 * Renders a list of articles with buttons to go the next or previous page
 */
class ArticleSearchView extends ArticleListView {

    protected $keyword;
    protected $totalNumberOfArticles;
    protected $pageNumber;
    protected $highestPageNumber;

    public function __construct(Text $text, $keyword, array $displayedArticles,
            $pageNumber, $totalNumberOfArticles, $highestPageNumber, $editLinks) {
        parent::__construct($text, $displayedArticles, 0, true, false, $editLinks);
        $this->keyword = $keyword;
        $this->totalNumberOfArticles = (int) $totalNumberOfArticles;
        $this->pageNumber = (int) $pageNumber;
        $this->highestPageNumber = (int) $highestPageNumber;
    }

    public function writeText(StreamInterface $stream) {
        $text = $this->text;
        $resultcount = $this->totalNumberOfArticles;

        if (count($this->articles) > 0) {
            // Display result count
            if ($resultcount == 1) {
                $stream->write("<p>" . $text->t('articles.search.result_found') . "</p>");
            } else {
                $stream->write("<p>" . $text->tReplaced('articles.search.results_found', $resultcount) . "</p>");
            }

            // Display articles
            $stream->write($this->getMenuBar());
            parent::writeText($stream);
            $stream->write($this->getMenuBar());
        } else {
            $stream->write('<p><em>' . $text->t('articles.search.no_results_found') . '</em></p>');
        }
    }

    protected function getMenuBar() {
        $text = $this->text;
        $keywordHtml = htmlSpecialChars($this->keyword);
        $page = $this->pageNumber;

        $returnValue = '<p class="result_selector_menu">';

        // Link to previous page
        if ($page > 0) {
            $returnValue.= ' <a class="arrow" href="';
            $returnValue.= $text->e($text->getUrlPage("search", 0, ["searchbox" => $keywordHtml, "page" => $page - 1]));
            $returnValue.= '">' . $text->t('articles.page.previous') . '</a> ';
        }

        // Current page (converting from zero-indexed to one-indexed)
        $returnValue.= $text->tReplaced('articles.page.current', $page + 1, $this->highestPageNumber + 1);

        // Next page
        if ($page < $this->highestPageNumber) {
            $returnValue.= ' <a class="arrow" href="';
            $returnValue.= $text->e($text->getUrlPage("search", 0, ["searchbox" => $keywordHtml, "page" => $page + 1]));
            $returnValue.= '">' . $text->t('articles.page.next') . '</a>';
        }

        $returnValue.= '</p>';

        return $returnValue;
    }

    public static function getStartNumber($page) {
        return $page * self::ARTICLES_PER_PAGE;
    }

}
