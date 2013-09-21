<?php

// Protect against calling this script directly
if (!isset($this)) {
    die();
}

class EditArticlePage extends Page {

    /** @var ArticleEditor $article_editor */
    protected $article_editor;

    /** @var Categories $categories_object */
    protected $categories_object;
    protected $message; // Message at the top of the page
    protected $redirect; // Redirect link, page will redirect to this link

    public function init(Website $oWebsite) {
        if (isSet($_REQUEST["id"])) {
            $article_id = $_REQUEST["id"];
        } else {
            $article_id = 0;
        }

        try {
            $article_editor = new ArticleEditor($oWebsite, $article_id);
            $this->article_editor = $article_editor;
            $this->categories_object = new Categories($oWebsite);

            // Now check input
            if ($article_editor->processInput($_REQUEST, $this->categories_object)) {
                if (isSet($_REQUEST["submit"])) {
                    // Try to save
                    $article = $article_editor->getArticle();
                    if ($article->save($oWebsite->getDatabase())) {
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
                        if ($_REQUEST["submit"] == $oWebsite->t("editor.save_and_quit")) {
                            $this->redirect = $oWebsite->getUrlPage("article", $article->id);
                        }
                    } else {
                        $this->message = "<em>" . $oWebsite->t("main.article") . " " . $oWebsite->t("errors.not_saved") . "</em>";
                    }
                }
            }
        } catch (InvalidArgumentException $e) {
            $oWebsite->addError($oWebsite->t("main.article") . " " . $oWebsite->t("errors.not_found"));
        }
    }

    public function getMinimumRank(Website $oWebsite) {
        return Authentication::$MODERATOR_RANK;
    }

    public function getPageTitle(Website $oWebsite) {
        $page_title = $oWebsite->t("articles.edit");
        if ($this->article_editor != null) {
            $article_title = $this->article_editor->getArticle()->title;
            if ($article_title) {
                // Editing existing article
                $page_title.= ' "' . $article_title . '"';
            } else {
                // New article
                $page_title = $oWebsite->t('articles.create');
            }
        }
        return $page_title;
    }

    public function getShortPageTitle(Website $oWebsite) {
        return $oWebsite->t("articles.edit");
    }

    public function getPageContent(Website $oWebsite) {
        if ($this->article_editor == null) {
            return "";
        }
        $article_editor = $this->article_editor;
        $article = $article_editor->getArticle();

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
        $date_time = explode(" ", $article->onCalendar);
        if (count($date_time) == 2) {
            $date = $date_time[0];
            // Empty the default values
            if ($date == "0000-00-00") {
                $date = "";
            }
            $time = $date_time[1];
            if (strLen($time) > 5) {
                $time = substr($time, 0, 5); // Remove seconds
            }
            if ($time == "00:00") {
                $time = ""; // Remove 00:00 time
            }
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
            <script type="text/javascript" src="{$oWebsite->getSiteSetting("ckfinder_url")}ckfinder.js"></script>
            <script type="text/javascript">
                initialize("{$oWebsite->getSiteSetting("ckfinder_url")}");
            </script>  

            $message_on_top_of_page
            <p>
                {$oWebsite->t("main.fields_required")}
            </p>
            <form action="{$oWebsite->getUrlMain()}" method="post">
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
                        <input type="hidden" name="p" value="edit_article" />
                        <input type="hidden" name="id" value="{$article->id}" />
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
        $categories = $this->categories_object->getCategories();
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
