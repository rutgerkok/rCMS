<?php

namespace Rcms\Template;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Article;
use Rcms\Core\Text;
use Rcms\Core\User;

/**
 * Displays a single article.
 */
class ArticleTemplate extends Template {

    /** @var Article $article */
    protected $article;

    /** @var Comment[] The comments */
    protected $comments;

    /** @var boolean True to display a link to edit this article. */
    private $editLink;
    
    /** @var User The user that is viewing the comments, may be null. */
    private $userTemplateingComments;

    /**
     * Creates a new article viewer.
     * @param Text $text The website object.
     * @param Article $article The article.
     * @param boolean $editLink True to display a link to edit/delete this article.
     * Which comments are editable depends on the $viewingComments parameter.
     * @param Comment[] $comments The comments for this article.
     * @param User|null $viewingComments User viewing the comments, may be null.
     * Edit/delete links for comments appear if this user matches the the author
     * of the comment, or if the user is a moderator.
     */
    public function __construct(Text $text, Article $article, $editLink,
            array $comments = [], User $viewingComments = null) {
        parent::__construct($text);
        $this->article = $article;
        $this->comments = $comments;
        $this->editLink = (boolean) $editLink;
        $this->userTemplateingComments = $viewingComments;
    }

    public function writeText(StreamInterface $stream) {
        if ($this->article) {
            $this->writeArticleTextFull($stream, $this->article, $this->comments);
        }
    }

    private function writeArticleTextFull(StreamInterface $stream, Article $article, array $comments) {
        // Store some variables for later use
        $text = $this->text;
        $id = $article->getId();

        $loggedIn = $this->editLink;

        // Echo the sidebar
        $stream->write('<div id="sidebar_page_sidebar">');

        // Featured image
        if (!empty($article->featuredImage)) {
            $stream->write('<p><img src="' . htmlSpecialChars($article->featuredImage) . '" alt="' . htmlSpecialChars($article->getTitle()) . '" /></p>');
        }
        $stream->write('<p class="meta">');

        // Created and last edited
        $stream->write($text->t('articles.created') . " <br />&nbsp;&nbsp;&nbsp;" . $text->formatDateTime($article->getDateCreated()));
        if ($article->getDateLastEdited()) {
            $stream->write(" <br />  " . $text->t('articles.last_edited') . " <br />&nbsp;&nbsp;&nbsp;" . $text->formatDateTime($article->getDateLastEdited()));
        }

        // Category
        $stream->write(" <br /> " . $text->t('main.category') . ': ');
        $stream->write('<a href="' . $text->e($text->getUrlPage("category", $article->categoryId)) . '">');
        $stream->write($text->e($article->category) . '</a>');

        // Author
        $stream->write(" <br /> " . $text->t('articles.author') . ': ');
        $stream->write('<a href="' . $text->e($text->getUrlPage("account", $article->authorId)) . '">');
        $stream->write($text->e($article->author) . '</a>');

        // Pinned, hidden, comments
        if ($article->pinned) {
            $stream->write("<br />" . $text->t('articles.pinned') . " ");
        }
        if ($article->isHidden()) {
            $stream->write("<br />" . $text->t('articles.hidden'));
        }
        if ($loggedIn && $article->showComments) {
            $stream->write("<br />" . $text->t('comments.allowed'));
        }

        // Edit, delete
        $stream->write('</p>');
        if ($loggedIn) {
            $stream->write("<p style=\"clear:both\">");
            $stream->write('&nbsp;&nbsp;&nbsp;<a class="arrow" href="' . $text->e($text->getUrlPage("edit_article", $id)) . '">' . $text->t('main.edit') . '</a>&nbsp;&nbsp;' .
                    '<a class="arrow" href="' . $text->e($text->getUrlPage("delete_article", $id)) . '">' . $text->t('main.delete') . '</a>');
            $stream->write("</p>");
        }
        $stream->write('</div>');

        // Article
        $stream->write('<div id="sidebar_page_content">');
        if ($loggedIn && $article->isHidden()) {
            $stream->write('<p class="meta">' . $text->t('articles.is_hidden') . "<br /> \n" . $text->t('articles.hidden.explained') . '</p>');
        }
        $stream->write('<p class="intro">' . $text->e($article->getIntro()) . '</p>');
        $stream->write($article->getBody());

        // Comments
        if ($article->showComments) {
            $commentCount = count($comments);

            // Title
            $stream->write('<h3 class="notable">' . $text->t("comments.comments"));
            if ($commentCount > 0) {
                $stream->write(' (' . $commentCount . ')');
            }
            $stream->write("</h3>\n\n");

            // "No comments found" if needed
            if ($commentCount == 0) {
                $stream->write('<p><em>' . $text->t("comments.no_comments_found") . '</em></p>');
            }

            // Comment add link
            $stream->write('<p><a class="button primary_button" href="' . $text->e($text->getUrlPage("add_comment", $id)) . '">' . $text->t("comments.add") . "</a></p>");

            // Show comments
            $commentTreeTemplate = new CommentsTreeTemplate($text, $comments, false, $this->userTemplateingComments);
            $commentTreeTemplate->writeText($stream);
        }
        $stream->write('</div>');
    }

}
