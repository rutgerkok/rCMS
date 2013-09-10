<?php

/**
 * Displays a single article.
 */
class ArticleView extends View {

    /** @var Article $article */
    protected $article;

    /** @var Comments $oComments */
    protected $oComments;

    /** @var Website $oWebsite */
    protected $oWebsite;

    /**
     * Creates a new article viewer.
     * @param Website $oWebsite The website object.
     * @param Article $article The article, or null if not found.
     * @param Comments $oComments The comments model, or null to disable comments.
     */
    public function __construct(Website $oWebsite, $article, Comments $oComments = null) {
        $this->article = $article;
        $this->oComments = $oComments;
        $this->oWebsite = $oWebsite;

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
        if (!empty($article->featuredImage))
            $returnValue.= '<p><img src="' . htmlSpecialChars($article->featuredImage) . '" alt="' . htmlSpecialChars($article->title) . '" /></p>';
        $returnValue.= '<p class="meta">';
        $returnValue.= $oWebsite->t('articles.created') . " <br />&nbsp;&nbsp;&nbsp;" . $article->created;
        if ($article->lastEdited)
            $returnValue.= " <br />  " . $oWebsite->t('articles.last_edited') . " <br />&nbsp;&nbsp;&nbsp;" . $article->lastEdited;
        $returnValue.= " <br /> " . $oWebsite->t('main.category') . ": " . $article->category;
        $returnValue.= " <br /> " . $oWebsite->t('articles.author') . ': ';
        $returnValue.= '<a href="' . $oWebsite->getUrlPage("account", $article->authorId) . '">' . $article->author . '</a>';
        if ($article->pinned)
            $returnValue.= "<br />" . $oWebsite->t('articles.pinned') . " "; //gepind
        if ($article->hidden)
            $returnValue.= "<br />" . $oWebsite->t('articles.hidden'); //verborgen
        if ($loggedIn && $article->showComments)
            $returnValue.= "<br />" . $oWebsite->t('comments.allowed'); //reacties
        $returnValue.= '</p>';
        if ($loggedIn) {
            $returnValue.= "<p style=\"clear:both\">";
            $returnValue.= '&nbsp;&nbsp;&nbsp;<a class="arrow" href="' . $oWebsite->getUrlPage("edit_article", $id) . '">' . $oWebsite->t('main.edit') . '</a>&nbsp;&nbsp;' . //edit
                    '<a class="arrow" href="' . $oWebsite->getUrlPage("delete_article", $id) . '">' . $oWebsite->t('main.delete') . '</a>'; //delete
            $returnValue.= "</p>";
        }
        if ($article->showComments) {
            $returnValue.= <<<EOT
                        <!-- AddThis Button BEGIN -->
                            <div class="addthis_toolbox addthis_default_style ">
                                <a class="addthis_button_facebook_like" fb:like:layout="buttonCount"></a>
                                <br /><br />
                                <a class="addthis_button_tweet"></a>
                                <br /><br />
                                <a class="addthis_button_google_plusone" g:plusone:size="medium"></a>
                                <br /><br />
                                <a class="addthisCounter addthis_pill_style"></a>
                            </div>
                            <script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=xa-50f99223106b78e7"></script>
                        <!-- AddThis Button END -->
EOT;
        }
        $returnValue.= '</div>';

        $returnValue.= '<div id="sidebar_page_content">';
        //artikel
        if ($loggedIn && $article->hidden)
            $returnValue.= '<p class="meta">' . $oWebsite->t('articles.is_hidden') . "<br /> \n" . $oWebsite->t('articles.hidden.explained') . '</p>';
        $returnValue.= '<p class="intro">' . htmlSpecialChars($article->intro) . '</p>';
        $returnValue.= $article->body;
        // Show comments
        if ($article->showComments && $oComments != null) {
            $comments = $oComments->get_comments_article($id);
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
            $returnValue.= '<p><a class="arrow" href="' . $oWebsite->getUrlPage("add_comment", $id) . '">' . $oWebsite->t("comments.add") . "</a></p>";
            // Show comments

            $current_user_id = $oWebsite->getCurrentUserId();
            $show_actions = $oWebsite->isLoggedInAsStaff();
            foreach ($comments as $comment) {
                if ($show_actions || $oComments->get_user_id($comment) == $current_user_id) {
                    $returnValue.= $oComments->get_comment_html($comment, true);
                } else {
                    $returnValue.= $oComments->get_comment_html($comment, false);
                }
            }
        }
        $returnValue.= '</div>';


        return $returnValue;
    }

}

?>
