<?php

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

/**
 * Displays a single article.
 */
class ArticleView extends View {

    /** @var Article $article */
    protected $article;

    /** @var Comments $oComments */
    protected $oComments;

    /**
     * Creates a new article viewer.
     * @param Website $oWebsite The website object.
     * @param Article $article The article, or null if not found.
     * @param Comments $oComments The comments model, or null to disable comments.
     */
    public function __construct(Website $oWebsite, $article, Comments $oComments = null) {
        parent::__construct($oWebsite);
        $this->article = $article;
        $this->oComments = $oComments;

        // Check if article exists
        if (!$this->article) {
            $oWebsite->addError($oWebsite->t('main.article') . ' ' . $oWebsite->t('errors.not_found'));
        } else {

            // Check if article is public
            if ($this->article->hidden && !$oWebsite->isLoggedInAsStaff()) {
                $oWebsite->addError($oWebsite->t('main.article') . ' ' . $oWebsite->t('errors.not_public'));
                $this->article = null;
            }
        }
    }

    public function getText() {
        if ($this->article) {
            return $this->getArticleTextFull($this->article, $this->oComments);
        } else {
            return "";
        }
    }

    public function getArticleTextFull(Article $article, Comments $oComments = null) {
        // Store some variables for later use
        $oWebsite = $this->oWebsite;
        $id = (int) $article->id;

        $returnValue = '';
        $loggedIn = $oWebsite->isLoggedInAsStaff();

        // Echo the sidebar
        $returnValue.= '<div id="sidebar_page_sidebar">';

        // Featured image
        if (!empty($article->featuredImage)) {
            $returnValue.= '<p><img src="' . htmlSpecialChars($article->featuredImage) . '" alt="' . htmlSpecialChars($article->title) . '" /></p>';
        }
        $returnValue.= '<p class="meta">';

        // Created and last edited
        $returnValue.= $oWebsite->t('articles.created') . " <br />&nbsp;&nbsp;&nbsp;" . $article->created;
        if ($article->lastEdited) {
            $returnValue.= " <br />  " . $oWebsite->t('articles.last_edited') . " <br />&nbsp;&nbsp;&nbsp;" . $article->lastEdited;
        }

        // Category
        $returnValue.= " <br /> " . $oWebsite->t('main.category') . ': ';
        $returnValue.= '<a href="' . $oWebsite->getUrlPage("category", $article->categoryId) . '">';
        $returnValue.= htmlSpecialChars($article->category) . '</a>';

        // Author
        $returnValue.= " <br /> " . $oWebsite->t('articles.author') . ': ';
        $returnValue.= '<a href="' . $oWebsite->getUrlPage("account", $article->authorId) . '">';
        $returnValue.= htmlSpecialChars($article->author) . '</a>';

        // Pinned, hidden, comments
        if ($article->pinned) {
            $returnValue.= "<br />" . $oWebsite->t('articles.pinned') . " ";
        }
        if ($article->hidden) {
            $returnValue.= "<br />" . $oWebsite->t('articles.hidden');
        }
        if ($loggedIn && $article->showComments) {
            $returnValue.= "<br />" . $oWebsite->t('comments.allowed');
        }

        // Edit, delete
        $returnValue.= '</p>';
        if ($loggedIn) {
            $returnValue.= "<p style=\"clear:both\">";
            $returnValue.= '&nbsp;&nbsp;&nbsp;<a class="arrow" href="' . $oWebsite->getUrlPage("edit_article", $id) . '">' . $oWebsite->t('main.edit') . '</a>&nbsp;&nbsp;' . //edit
                    '<a class="arrow" href="' . $oWebsite->getUrlPage("delete_article", $id) . '">' . $oWebsite->t('main.delete') . '</a>'; //delete
            $returnValue.= "</p>";
        }
        $returnValue.= '</div>';

        // Article
        $returnValue.= '<div id="sidebar_page_content">';
        if ($loggedIn && $article->hidden) {
            $returnValue.= '<p class="meta">' . $oWebsite->t('articles.is_hidden') . "<br /> \n" . $oWebsite->t('articles.hidden.explained') . '</p>';
        }
        $returnValue.= '<p class="intro">' . htmlSpecialChars($article->intro) . '</p>';
        $returnValue.= $article->body;

        // Comments
        if ($article->showComments && $oComments != null) {
            $comments = $oComments->getCommentsArticle($id);
            $commentCount = count($comments);

            // Title
            $returnValue.= '<h3 class="notable">' . $oWebsite->t("comments.comments");
            if ($commentCount > 0) {
                $returnValue.= ' (' . $commentCount . ')';
            }
            $returnValue.= "</h3>\n\n";

            // "No comments found" if needed
            if ($commentCount == 0) {
                $returnValue.= '<p><em>' . $oWebsite->t("comments.no_comments_found") . '</em></p>';
            }

            // Comment add link
            $returnValue.= '<p><a class="button primary_button" href="' . $oWebsite->getUrlPage("add_comment", $id) . '">' . $oWebsite->t("comments.add") . "</a></p>";

            // Show comments
            $commentTreeView = new CommentsTreeView($oWebsite, $comments, false);
            $returnValue .= $commentTreeView->getText();
        }
        $returnValue.= '</div>';


        return $returnValue;
    }

}
