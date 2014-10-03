<?php

namespace Rcms\Core;

use DateTime;

/**
 * Provides all method needed to build an article editor.
 */
class ArticleEditor {

    /** @var Website $websiteObject The website */
    private $websiteObject;

    /** @var Article $articleObject The article being edited */
    private $articleObject;

    /**
     * Creates a new editor for the article.
     * @param Website $website The website object.
     * @param Article $article The article object.
     */
    public function __construct(Website $website, Article $article) {
        $this->websiteObject = $website;
        $this->articleObject = $article;
    }

    public function processInput($inputArray, CategoryRepository $oCategories) {
        $oWebsite = $this->websiteObject;
        $article = $this->articleObject;
        $sent = isSet($inputArray["submit"]);
        $noErrors = true;

        // Title
        if (isSet($inputArray['article_title'])) {
            $title = trim($oWebsite->getRequestString('article_title'));
            if (strLen($title) > 100) {
                $oWebsite->addError($oWebsite->t("articles.title") . " " . $oWebsite->tReplaced("errors.is_too_long_num", 100));
                $noErrors = false;
            }
            if (strLen($title) < 2) {
                $oWebsite->addError($oWebsite->tReplacedKey("errors.please_enter_this", "articles.title", true));
                $noErrors = false;
            }
            $article->title = $title;
        }

        // Intro
        if (isSet($inputArray['article_intro'])) {
            $intro = trim($oWebsite->getRequestString('article_intro'));
            if (strLen($intro) < 2) {
                $oWebsite->addError($oWebsite->tReplacedKey("errors.please_enter_this", "articles.intro", true));
                $noErrors = false;
            }
            if (strLen($intro) > 325) {
                $oWebsite->addError($oWebsite->t("articles.intro") . " " . $oWebsite->tReplaced("errors.is_too_long_num", 325));
                $noErrors = false;
            }
            $article->intro = $intro;
        }

        // Body
        if (isSet($inputArray['article_body'])) {
            $body = trim($oWebsite->getRequestString('article_body'));
            if (strLen($body) < 9) {
                $oWebsite->addError($oWebsite->tReplacedKey("errors.please_enter_this", "articles.body", true));
                $noErrors = false;
            }
            if (strLen($body) > 65535) {
                $oWebsite->addError($oWebsite->t("articles.body") . " " . $oWebsite->tReplaced("errors.is_too_long_num", 65535));
                $noErrors = false;
            }
            $article->body = $body;
        }

        // Category
        if (isSet($inputArray['article_category'])) {
            $categoryId = (int) $oWebsite->getRequestString('article_category', 0);
            if ($categoryId == 0) {
                // Silent failure when category id is set to 0
                $noErrors = false;
            } elseif (!$oCategories->getCategoryName($categoryId)) {
                $oWebsite->addError($oWebsite->t("main.category") . " " . $oWebsite->t("errors.not_found"));
                $noErrors = false;
            }
            $article->categoryId = $categoryId;
        }

        // Featured image
        if (isSet($inputArray['article_featured_image'])) {
            $featuredImage = trim($oWebsite->getRequestString('article_featured_image'));
            if (strLen($featuredImage) > 150) {
                $oWebsite->addError($oWebsite->t("articles.featured_image") . " " . $oWebsite->tReplaced("ërrors.is_too_long_num", 150));
                $noErrors = false;
            }
            $article->featuredImage = $featuredImage;
        }

        // Pinned
        if (isSet($inputArray['article_pinned'])) {
            $article->pinned = true;
        } elseif ($sent) {
            $article->pinned = false;
        }

        // Hidden
        if (isSet($inputArray['article_hidden'])) {
            $article->hidden = true;
        } elseif ($sent) {
            $article->hidden = false;
        }

        // Event date
        $eventDate = "";
        $eventTime = "";
        if (isSet($inputArray['article_eventdate'])) {
            $eventDate = trim($oWebsite->getRequestString('article_eventdate'));
        }
        if (isSet($inputArray['article_eventtime']) && $eventDate) {
            $eventTime = trim($oWebsite->getRequestString('article_eventtime'));
        }
        if (empty($eventDate) && isSet($inputArray['article_eventdate'])) {
            // Field was made empty, so delete date on article
            $article->onCalendar = null;
        }
        if (!empty($eventDate)) {
            if (strtotime($eventDate) === false) {
                $oWebsite->addError($oWebsite->t("articles.event_date") . " " . $oWebsite->t("errors.not_correct"));
                $noErrors = false;
            } else {
                // Add date to article
                $article->onCalendar = new DateTime($eventDate . " " . $eventTime);
            }
        }

        // Comments
        if (isSet($inputArray['article_comments'])) {
            $article->showComments = true;
        } elseif ($sent) {
            $article->showComments = false;
        }

        return $noErrors;
    }

    /**
     * Retrieves the current article object.
     * @return Article The article object.
     */
    public function getArticle() {
        return $this->articleObject;
    }

}
