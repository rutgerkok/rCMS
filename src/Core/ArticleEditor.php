<?php

namespace Rcms\Core;

use DateTime;

/**
 * Provides all method needed to build an article editor.
 */
class ArticleEditor {


    /** @var Article $articleObject The article being edited */
    private $articleObject;

    /**
     * Creates a new editor for the article.
     * @param Article $article The article object.
     */
    public function __construct(Article $article) {
        $this->articleObject = $article;
    }

    public function processInput(Text $text, Request $request, CategoryRepository $oCategories) {
        $article = $this->articleObject;
        $noErrors = true;

        // Title
        if ($request->hasRequestValue("article_title")) {
            $title = trim($request->getRequestString('article_title'));
            if (strLen($title) > Article::MAX_TITLE_LENGTH) {
                $text->addError($text->t("articles.title") . " " . $text->tReplaced("errors.is_too_long_num", Article::MAX_TITLE_LENGTH));
                $noErrors = false;
            }
            if (strLen($title) < Article::MIN_TITLE_LENGTH) {
                $text->addError($text->tReplacedKey("errors.please_enter_this", "articles.title", true));
                $noErrors = false;
            }
            $article->setTitle($title);
        }

        // Intro
        if ($request->hasRequestValue("article_intro")) {
            $intro = trim($request->getRequestString("article_intro"));
            if (strLen($intro) < Article::MIN_INTRO_LENGTH) {
                $text->addError($text->tReplacedKey("errors.please_enter_this", "articles.intro", true));
                $noErrors = false;
            }
            if (strLen($intro) > Article::MAX_INTRO_LENGTH) {
                $text->addError($text->t("articles.intro") . " " . $text->tReplaced("errors.is_too_long_num", Article::MAX_INTRO_LENGTH));
                $noErrors = false;
            }
            $article->setIntro($intro);
        }

        // Body
        if ($request->hasRequestValue("article_body")) {
            $body = trim($request->getRequestString("article_body"));
            if (strLen($body) < Article::MIN_BODY_LENGTH) {
                $text->addError($text->tReplacedKey("errors.please_enter_this", "articles.body", true));
                $noErrors = false;
            }
            if (strLen($body) > Article::MAX_BODY_LENGTH) {
                $text->addError($text->t("articles.body") . " " . $text->tReplaced("errors.is_too_long_num", Article::MAX_BODY_LENGTH));
                $noErrors = false;
            }
            $article->setBody($body);
        }

        // Category
        if ($request->hasRequestValue("article_category")) {
            $categoryId = (int) $request->getRequestString('article_category', 0);
            if ($categoryId == 0) {
                // Silent failure when category id is set to 0, as it is a default value
                $noErrors = false;
            } elseif (!$this->categoryExists($oCategories, $categoryId)) {
                $text->addError($text->t("main.category") . " " . $website->t("errors.not_found"));
                $noErrors = false;
            }
            $article->categoryId = $categoryId;
        }

        // Featured image
        if ($request->hasRequestValue("article_featured_image")) {
            $featuredImage = trim($request->getRequestString("article_featured_image"));
            if (strLen($featuredImage) > Article::MAX_FEATURED_IMAGE_URL_LENGTH) {
                $text->addError($text->t("articles.featured_image") . " " . $text->tReplaced("ërrors.is_too_long_num", Article::MAX_FEATURED_IMAGE_URL_LENGTH));
                $noErrors = false;
            }
            $article->featuredImage = $featuredImage;
        }

        // Pinned, hidden, comments
        if ($request->hasRequestValue("submit")) {
            $article->pinned = $request->hasRequestValue("article_pinned");
            $article->setHidden($request->hasRequestValue("article_hidden"));
            $article->showComments = $request->hasRequestValue("article_comments");
        }

        // Event date
        $eventDate = "";
        $eventTime = "";
        if ($request->hasRequestValue("article_eventdate")) {
            $eventDate = trim($request->getRequestString("article_eventdate"));
        }
        if ($request->hasRequestValue("article_eventtime") && $eventDate) {
            $eventTime = trim($request->getRequestString("article_eventtime"));
        }
        if (empty($eventDate) && $request->hasRequestValue("article_eventdate")) {
            // Field was made empty, so delete date on article
            $article->onCalendar = null;
        }
        if (!empty($eventDate)) {
            if (strtotime($eventDate) === false) {
                $text->addError($text->t("articles.event_date") . " " . $text->t("errors.not_correct"));
                $noErrors = false;
            } else {
                // Add date to article
                $article->onCalendar = new DateTime($eventDate . " " . $eventTime);
            }
        }

        return $noErrors;
    }

    private function categoryExists(CategoryRepository $repo, $id) {
        try {
            $repo->getCategory($id);
            return true;
        } catch (NotFoundException $ex) {
            return false;
        }
    }

    /**
     * Retrieves the current article object.
     * @return Article The article object.
     */
    public function getArticle() {
        return $this->articleObject;
    }

}
