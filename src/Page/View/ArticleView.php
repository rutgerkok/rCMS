<?php

namespace Rcms\Page\View;

use Rcms\Core\Article;
use Rcms\Core\CommentRepository;
use Rcms\Core\Text;

/**
 * Displays a single article.
 */
class ArticleView extends View {

    /** @var Article $article */
    protected $article;

    /** @var Comment[] The comments */
    protected $comments;

    /** @var boolean True to display a link to edit this article. */
    private $editLink;

    /**
     * Creates a new article viewer.
     * @param Text $text The website object.
     * @param Article $article The article.
     * @param boolean $editLink True to display a link to edit this article.
     * @param Comment[] $comments The comments for this article.
     */
    public function __construct(Text $text, Article $article, $editLink,
            array $comments = array()) {
        parent::__construct($text);
        $this->article = $article;
        $this->comments = $comments;
        $this->editLink = (boolean) $editLink;
    }

    public function getText() {
        if ($this->article) {
            return $this->getArticleTextFull($this->article, $this->comments);
        } else {
            return "";
        }
    }

    public function getArticleTextFull(Article $article,
            array $comments = array()) {
        // Store some variables for later use
        $text = $this->text;
        $id = (int) $article->id;

        $returnValue = '';
        $loggedIn = $this->editLink;

        // Echo the sidebar
        $returnValue.= '<div id="sidebar_page_sidebar">';

        // Featured image
        if (!empty($article->featuredImage)) {
            $returnValue.= '<p><img src="' . htmlSpecialChars($article->featuredImage) . '" alt="' . htmlSpecialChars($article->title) . '" /></p>';
        }
        $returnValue.= '<p class="meta">';

        // Created and last edited
        $returnValue.= $text->t('articles.created') . " <br />&nbsp;&nbsp;&nbsp;" . $text->formatDateTime($article->created);
        if ($article->lastEdited) {
            $returnValue.= " <br />  " . $text->t('articles.last_edited') . " <br />&nbsp;&nbsp;&nbsp;" . $text->formatDateTime($article->lastEdited);
        }

        // Category
        $returnValue.= " <br /> " . $text->t('main.category') . ': ';
        $returnValue.= '<a href="' . $text->getUrlPage("category", $article->categoryId) . '">';
        $returnValue.= htmlSpecialChars($article->category) . '</a>';

        // Author
        $returnValue.= " <br /> " . $text->t('articles.author') . ': ';
        $returnValue.= '<a href="' . $text->getUrlPage("account", $article->authorId) . '">';
        $returnValue.= htmlSpecialChars($article->author) . '</a>';

        // Pinned, hidden, comments
        if ($article->pinned) {
            $returnValue.= "<br />" . $text->t('articles.pinned') . " ";
        }
        if ($article->hidden) {
            $returnValue.= "<br />" . $text->t('articles.hidden');
        }
        if ($loggedIn && $article->showComments) {
            $returnValue.= "<br />" . $text->t('comments.allowed');
        }

        // Edit, delete
        $returnValue.= '</p>';
        if ($loggedIn) {
            $returnValue.= "<p style=\"clear:both\">";
            $returnValue.= '&nbsp;&nbsp;&nbsp;<a class="arrow" href="' . $text->getUrlPage("edit_article", $id) . '">' . $text->t('main.edit') . '</a>&nbsp;&nbsp;' . //edit
                    '<a class="arrow" href="' . $text->getUrlPage("delete_article", $id) . '">' . $text->t('main.delete') . '</a>'; //delete
            $returnValue.= "</p>";
        }
        $returnValue.= '</div>';

        // Article
        $returnValue.= '<div id="sidebar_page_content">';
        if ($loggedIn && $article->hidden) {
            $returnValue.= '<p class="meta">' . $text->t('articles.is_hidden') . "<br /> \n" . $text->t('articles.hidden.explained') . '</p>';
        }
        $returnValue.= '<p class="intro">' . htmlSpecialChars($article->intro) . '</p>';
        $returnValue.= $article->body;

        // Comments
        if ($article->showComments) {
            $commentCount = count($comments);

            // Title
            $returnValue.= '<h3 class="notable">' . $text->t("comments.comments");
            if ($commentCount > 0) {
                $returnValue.= ' (' . $commentCount . ')';
            }
            $returnValue.= "</h3>\n\n";

            // "No comments found" if needed
            if ($commentCount == 0) {
                $returnValue.= '<p><em>' . $text->t("comments.no_comments_found") . '</em></p>';
            }

            // Comment add link
            $returnValue.= '<p><a class="button primary_button" href="' . $text->getUrlPage("add_comment", $id) . '">' . $text->t("comments.add") . "</a></p>";

            // Show comments
            $commentTreeView = new CommentsTreeView($text, $comments, false);
            $returnValue .= $commentTreeView->getText();
        }
        $returnValue.= '</div>';


        return $returnValue;
    }

}
