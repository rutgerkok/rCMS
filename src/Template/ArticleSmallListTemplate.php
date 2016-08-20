<?php

namespace Rcms\Template;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Text;
use Rcms\Core\Article;

/**
 * Gets the articles as a list, like this:
 * 
 * <ul>
 *   <li>Item</li>
 *   <li>Item</li>
 * </ul>
 */
class ArticleSmallListTemplate extends Template {

    /**
     * @var Article[] List of articles.
     */
    protected $articles;

    /**
     * @var boolean True to show edit links, false otherwise.
     */
    protected $editLinks;
    protected $images;
    protected $archive;
    protected $mainCategoryId;

    /**
     * Creates a new view of a list of articles.
     * @param Text $text The website object.
     * @param Article[] $articles List of articles to display.
     * @param boolean $editLinks True to show edit links, false otherwise.
     * @param int $mainCategoryId The id of the category of the archive and the
     *     Add-article link. Use 0 if the articles are of multiple categories.
     * @param boolean $images Whether images should be shown.
     * @param boolean $archive Whether a link to the archive should be shown.
     */
    public function __construct(Text $text, $articles, $editLinks,
            $mainCategoryId = 0, $images = false, $archive = false) {
        parent::__construct($text);
        $this->articles = $articles;
        $this->editLinks = (boolean) $editLinks;
        $this->mainCategoryId = (int) $mainCategoryId;
        $this->images = (boolean) $images;
        $this->archive = (boolean) $archive;
    }

    public function writeText(StreamInterface $stream) {
        $stream->write($this->getArticlesList($this->articles, $this->mainCategoryId, $this->images, $this->archive));
    }

    public function getArticlesList($articles, $mainCategoryId, $images = false,
            $archive = false) {
        $text = $this->text;

        // Build article list
        $returnValue = '';
        if (count($articles) > 0) {
            $returnValue = '<ul class="linklist">';
            foreach ($articles as $article) {
                $returnValue.= $this->getArticleTextListEntry($article, $images);
            }
            $returnValue .= "</ul>\n";
        }

        // Add article link
        if ($this->editLinks) {
            $returnValue .= '<p><a class="arrow" href="' . $text->e($text->getUrlPage("edit_article", 0, ["article_category" => $mainCategoryId]));
            $returnValue .= '">' . $text->t("articles.create") . "</a></p>\n";
        }

        // Archive link
        if ($archive) {
            $returnValue.= '<p><a href="' . $text->e($text->getUrlPage("archive", $mainCategoryId) ). '" class="arrow">' . $text->t('articles.archive') . '</a></p>';
        }

        return $returnValue;
    }

    /** Returns a single article enclosed in li tags */
    public function getArticleTextListEntry(Article $article,
            $displayImage = false) {
        $text = $this->text;
        $returnValue = '<li><a href="' . $text->e($text->getUrlPage("article", $article->getId())) . '"';
        $returnValue.= 'title="' . htmlSpecialChars($article->getIntro()) . '">';
        if ($displayImage && !empty($article->featuredImage)) {
            $returnValue.= '<div class="linklist_icon_image"><img src="' . htmlSpecialChars($article->featuredImage) . '" alt="' . htmlSpecialChars($article->getTitle()) . '" /></div>';
        }
        $returnValue.= "<span>" . htmlSpecialChars($article->getTitle()) . "</span></a></li>\n";
        return $returnValue;
    }

}
