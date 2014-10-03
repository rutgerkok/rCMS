<?php

namespace Rcms\Page;

use Rcms\Core\Article;
use Rcms\Core\ArticleEditor;
use Rcms\Core\ArticleRepository;
use Rcms\Core\Authentication;
use Rcms\Core\CategoryRepository;
use Rcms\Core\Editor;
use Rcms\Core\Exception\NotFoundException;
use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Core\RequestToken;
use Rcms\Core\Validate;

class EditArticlePage extends Page {

    /** @var ArticleEditor $article_editor */
    protected $article_editor;

    /** @var CategoryRepository $categories_object */
    protected $categories_object;
    protected $message; // Message at the top of the page
    protected $redirect; // Redirect link, page will redirect to this link
    protected $token; // Token, always set

    public function init(Request $request) {
        $oWebsite = $request->getWebsite();
        $article_id = $request->getParamInt(0);

        $articleRepository = new ArticleRepository($oWebsite);
        $article = $this->getArticle($articleRepository, $article_id);
        $article_editor = new ArticleEditor($oWebsite, $article);
        $this->article_editor = $article_editor;
        $this->categories_object = new CategoryRepository($oWebsite);

        // Validate token, then save new one to session
        $validToken = Validate::requestToken($request);
        $this->token = RequestToken::generateNew();
        $this->token->saveToSession();

        // Now check input
        if ($article_editor->processInput($_REQUEST, $this->categories_object)) {
            if ($request->hasRequestValue("submit") && $validToken) {
                // Try to save
                $article = $article_editor->getArticle();
                if ($articleRepository->save($article)) {
                    if ($article_id == 0) {
                        // New article created
                        $this->message = "<em>" . $oWebsite->t("main.article") . " " . $oWebsite->t("editor.is_created") . "</em>";
                    } else {
                        // Article updated
                        $this->message = "<em>" . $oWebsite->t("main.article") . " " . $oWebsite->t("editor.is_edited") . "</em>";
                    }
                    $this->message.= ' <a class="arrow" href="' . $oWebsite->getUrlPage("article", $article->id) . '">';
                    $this->message.= $oWebsite->t("articles.view") . "</a>";

                    // Check for redirect
                    if ($request->getRequestString("submit") == $oWebsite->t("editor.save_and_quit")) {
                        $this->redirect = htmlspecialchars_decode($oWebsite->getUrlPage("article", $article->id));
                    }
                } else {
                    $this->message = "<em>" . $oWebsite->t("main.article") . " " . $oWebsite->t("errors.not_saved") . "</em>";
                }
            }
        }
    }

    /**
     * Gets the article with the given id. If the id is 0, a new article is
     * created.
     * @param ArticleRepository $repository Repository to fetch articles from.
     * @param int $id Id of the article. Use 0 to create a new article.
     * @return Article The article.
     * @throws NotFoundException If no article exists with the given id.
     */
    protected function getArticle(ArticleRepository $repository, $id) {
        if ($id === 0) {
            return new Article();
        } else {
            return $repository->getArticleOrFail($id);
        }
    }

    public function getMinimumRank(Request $request) {
        return Authentication::$MODERATOR_RANK;
    }

    public function getPageTitle(Text $text) {
        $pageTitle = $text->t("articles.edit");
        if ($this->article_editor != null) {
            $articleTitle = $this->article_editor->getArticle()->title;
            if (!empty($articleTitle)) {
                // Editing existing article
                $pageTitle.= ' "' . htmlSpecialChars($articleTitle) . '"';
            } else {
                // New article
                $pageTitle = $text->t('articles.create');
            }
        }
        return $pageTitle;
    }

    public function getShortPageTitle(Text $text) {
        if ($this->article_editor != null) {
            $articleTitle = $this->article_editor->getArticle()->title;
            if (empty($articleTitle)) {
                // New article
                return $text->t("articles.create");
            }
        }
        return $text->t("articles.edit");
    }

    public function getPageContent(Request $request) {
        $oWebsite = $request->getWebsite();
        $article_editor = $this->article_editor;
        $article = $article_editor->getArticle();

        $tokenName = RequestToken::FIELD_NAME;
        $tokenHtml = htmlSpecialChars($this->token->getTokenString());

        // Setup variables
        $oEditor = new Editor($oWebsite);
        $title = htmlSpecialChars($article->title);
        $intro = htmlSpecialChars($article->intro);
        $body = $article->body; // Escaped by the get_editor method
        $hidden = $article->hidden ? 'checked="checked"' : '';
        $pinned = $article->pinned ? 'checked="checked"' : '';
        $show_comments = $article->showComments ? 'checked="checked"' : '';
        $featured_image = htmlSpecialChars($article->featuredImage);
        if ($featured_image) {
            $featured_image_tag = '<img src="' . $featured_image . '" />';
        } else {
            $featured_image_tag = '<em>' . $oWebsite->t("editor.no_image_selected") . '</em>';
        }
        $cat_list = $this->get_category_list($article);

        // Date and time
        $date = "";
        $time = "";
        if ($article->onCalendar !== null) {
            $date = $article->onCalendar->format("Y-m-d");
            $time = $article->onCalendar->format("H:i");
        }

        // Message on top of the page
        $message_on_top_of_page = $this->message ? '<p>' . $this->message . "</p>" : "";
        if ($this->redirect) {
            $message_on_top_of_page.= '<script type="text/javascript">location.href = "' . $this->redirect . '";</script>';
        }

        // Create form
        // Should be put into a view
        $returnValue = <<<ARTICLE_FORM
            <!-- Date picker and CKFinder integration -->
            <script type="text/javascript" src="{$oWebsite->getUrlJavaScripts()}article_editor.js"></script>
            <script type="text/javascript" src="{$oWebsite->getConfig()->get("ckfinder_url")}ckfinder.js"></script>
            <script type="text/javascript">
                initialize("{$oWebsite->getConfig()->get("ckfinder_url")}");
            </script>  

            $message_on_top_of_page
            <p>
                {$oWebsite->t("main.fields_required")}
            </p>
            <form action="{$oWebsite->getUrlPage("edit_article", $article->id)}" method="post">
                <p>
                    <label for="article_title">{$oWebsite->t("articles.title")}:<span class="required">*</span></label>
                    <br />
                    <input type="text" id="article_title" name="article_title" class="full_width" value="$title" />
                </p>
                <div id="sidebar_page_sidebar">
                    <fieldset>
                        <legend>{$oWebsite->t("articles.featured_image")}</legend>
                        <p>
                            <span id="article_editor_image">
                                $featured_image_tag
                            </span>
                            <br />
                            <input name="article_featured_image" id="article_featured_image" type="text" class="full_width" value="$featured_image" onblur="updateImage(this.value)" />
                            <a onclick="browseServer()" class="arrow">{$oWebsite->t("main.edit")}</a>
                            <a onclick="clearImage()" class="arrow">{$oWebsite->t("main.delete")}</a>
                        </p>
                    </fieldset>
                    <fieldset>
                        <legend>{$oWebsite->t("editor.other_options")}</legend>
                        <p>
                            <label for="article_hidden" title="{$oWebsite->t("articles.hidden.explained")}" style="cursor:help">
                                <input type="checkbox" id="article_hidden" name="article_hidden" class="checkbox" $hidden />
                                {$oWebsite->t("articles.hidden")}
                            </label>
                            <br />
                            <label for="article_pinned" title="{$oWebsite->t("articles.pinned.explained")}" style="cursor:help">
                                <input type="checkbox" id="article_pinned" name="article_pinned" class="checkbox" $pinned />
                                {$oWebsite->t("articles.pinned")}
                            </label>
                            <br />
                            <label for="article_comments" title="{$oWebsite->t("comments.allow_explained")}" style="cursor:help">
                                <input type="checkbox" id="article_comments" name="article_comments" class="checkbox" $show_comments />
                                {$oWebsite->t("comments.comments")}
                            </label>
                        </p>
                        <p>
                            <label for="article_category">{$oWebsite->t("main.category")}<span class="required">*</span></label>
                            <br />
                            $cat_list
                         </p>
                     </fieldset>
                     <fieldset>
                        <legend>{$oWebsite->t("articles.event_date")}</legend>
                        <p>
                            {$oWebsite->t("articles.event_date.explained")}
                        </p>
                        <p>
                            <label for="article_eventdate">
                                {$oWebsite->t("editor.date")}:
                                <br />
                                <input type="text" id="article_eventdate" name="article_eventdate" value="$date" style="width:8em" />
                                <input type="button" class="button" value="{$oWebsite->t("articles.event_date.select")}" onclick="showDatePicker()" />
                            </label>
                        </p>
                        <p>
                            <label for="article_eventtime">
                                {$oWebsite->t("editor.time")}:
                                <br />
                                <input type="text" id="article_eventtime" name="article_eventtime" value="$time" style="width:8em" />
                            </label>
                        </p>
                    </fieldset>
                </div>
                <div id="sidebar_page_content">
                    <p>
                        <label for="article_intro">{$oWebsite->t("articles.intro")}:<span class="required">*</span></label>
                        <br />
                        <textarea id="article_intro" name="article_intro" rows="3" class="full_width">$intro</textarea>
                    </p>
                    <p>
                        <label for="article_body">{$oWebsite->t("articles.body")}:<span class="required">*</span></label>
                        <br />
                        {$oEditor->get_text_editor("article_body", $body)}
                    </p>
                    <p>
                        <input type="hidden" name="{$tokenName}" value="{$tokenHtml}" />
                        <input type="submit" name="submit" class="button primary_button" value="{$oWebsite->t("editor.save")}" />
                        <input type="submit" name="submit" class="button" value="{$oWebsite->t("editor.save_and_quit")}" />
                        <a class="button" href="{$oWebsite->getUrlPage("article", $article->id)}">{$oWebsite->t("editor.quit")}</a>
                    </p>
                </div>
                <div style="clear:both"></div>
            </form>

ARTICLE_FORM;
        return $returnValue;
    }

    public function getPageType() {
        return "BACKSTAGE";
    }

    protected function get_category_list(Article $article) {
        $article_category = $article->categoryId;
        $categories = $this->categories_object->getCategoriesArray();
        $cat_list = '<select name="article_category" id="article_category" class="button" style="width:100%">';
        foreach ($categories as $cat_id => $cat_name) {
            $cat_list.="<option value=\"$cat_id\" ";
            if ($article_category == $cat_id) {
                $cat_list.="selected=\"selected\" ";
            }
            $cat_list.=">$cat_name</option>\n";
        }
        $cat_list.="</select>";
        return $cat_list;
    }

}
