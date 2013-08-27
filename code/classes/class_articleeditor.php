<?php

/**
 * Provides all method needed to build an article editor.
 */
class ArticleEditor { 
    /** @var Website $website_object The website */
    private $website_object;
    
    /** @var Article $article_object The article being edited */
    private $article_object;

    /** @var Database $database_object The database to fetch the article from */
    private $database_object;

    /**
     * Creates a new editor for the article.
     * @param Website $website The website object.
     * @param Article|int $article The article object or the article id. Use id
     * 0 or leave out this argument to create a new article.
     * @throws InvalidArgumentException If the article is not a number or
     * article object, or if the id is not 0 and no article with that id exists.
     */
    public function __construct(Website $website, $article = 0) {
        $this->website_object = $website;
        $this->database_object = $website->get_database();

        if ($article instanceof Article) {
            $this->article_object = $article;
        } elseif (is_numeric($article)) {
            if ($article == 0) {
                // Creating a new article
                $data = array("", time(), 0, "", "", 0, "", 0, "", false, false);
                $this->article_object = new Article(0, $data);
            } else {
                // Loading existing article, may throw exception
                $this->article_object = new Article($article, $website->get_database());
            }
        } else {
            throw new InvalidArgumentException('$article must be an Article object or an article id');
        }
    }

    public function process_input($input_array, Categories $oCategories) {
        $oWebsite = $this->website_object;
        $article = $this->article_object;
        $sent = isset($input_array["submit"]);
        $no_errors = true;

        // Title
        if (isset($input_array['article_title'])) {
            $title = trim($oWebsite->get_request_string('article_title'));
            if (strlen($title) > 100) {
                $oWebsite->add_error($oWebsite->t("articles.title") . " " . $oWebsite->t_replaced("errors.is_too_long_num", 100));
                $no_errors = false;
            }
            if (strlen($title) < 2) {
                $oWebsite->add_error($oWebsite->t_replaced_key("errors.please_enter_this", "articles.title", true));
                $no_errors = false;
            }
            $article->title = $title;
        }

        // Intro
        if (isset($input_array['article_intro'])) {
            $intro = trim($oWebsite->get_request_string('article_intro'));
            if (strlen($intro) < 2) {
                $oWebsite->add_error($oWebsite->t_replaced_key("errors.please_enter_this", "articles.intro", true));
                $no_errors = false;
            }
            if (strlen($intro) > 325) {
                $oWebsite->add_error($oWebsite->t("articles.intro") . " " . $oWebsite->t_replaced("errors.is_too_long_num", 325));
                $no_errors = false;
            }
            $article->intro = $intro;
        }

        // Body
        if (isset($input_array['article_body'])) {
            $body = trim($oWebsite->get_request_string('article_body'));
            if (strlen($body) < 9) {
                $oWebsite->add_error($oWebsite->t_replaced_key("errors.please_enter_this", "articles.body", true));
                $no_errors = false;
            }
            if (strlen($body) > 65535) {
                $oWebsite->add_error($oWebsite->t("articles.body") . " " . $oWebsite->t_replaced_key("errors.is_too_long_num", 65535));
                $no_errors = false;
            }
            $article->body = $body;
        }

        // Category
        if (isset($input_array['article_category'])) {
            $category_id = (int) $oWebsite->get_request_string('article_category', 0);
            if(!$oCategories->get_category_name($category_id)) {
                $oWebsite->add_error($oWebsite->t("main.category") . " " . $oWebsite->t("errors.not_found"));
                $no_errors = false;
            }
            $article->category_id = $category_id;
        }

        // Featured image
        if (isset($input_array['article_featured_image'])) {
            $featured_image = trim($oWebsite->get_request_string('article_featured_image'));
            if (strlen($featured_image) > 150) {
                $oWebsite->add_error($oWebsite->t("articles.featured_image") . " " . $oWebsite->t_replaced("ërrors.is_too_long_num", 150));
                $no_errors = false;
            }
            $article->featured_image = $featured_image;
        }

        // Pinned
        if (isset($input_array['article_pinned'])) {
            $article->pinned = true;
        } elseif ($sent) {
            $article->pinned = false;
        }

        // Hidden
        if (isset($input_array['article_hidden'])) {
            $article->hidden = true;
        } elseif ($sent) {
            $article->hidden = false;
        }

        // Event date
        $event_date = "";
        if (isset($input_array['article_eventdate'])) {
            $event_date = trim($oWebsite->get_request_string('article_eventdate'));
        }
        if (isset($input_array['article_eventtime']) && $event_date) {
            $event_time = trim($oWebsite->get_request_string('article_eventtime'));
            $article->on_calendar = $event_date . " " . $event_time;
        }
        if($event_date) {
            if(strtotime($event_date) === false) {
                $oWebsite->add_error($oWebsite->t("articles.event_date") . " " . $oWebsite->t_replaced("ërrors.not_correct"));
                $no_errors = false;
            }
        }
        
        // Comments
        if (isset($input_array['article_comments'])) {
            $article->show_comments = true;
        } elseif ($sent) {
            $article->show_comments = false;
        }
        
        return $no_errors;
    }

    /**
     * Retrieves the current article object.
     * @return Article The article object.
     */
    public function get_article() {
        return $this->article_object;
    }

}

?>
