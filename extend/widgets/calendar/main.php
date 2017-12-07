<?php

namespace Rkok\Extend\Widget;

use DateTime;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\ArticleRepository;
use Rcms\Core\Authentication;
use Rcms\Core\Request;
use Rcms\Core\Website;
use Rcms\Core\Widget\WidgetDefinition;
use Rcms\Template\ArticleEventListTemplate;

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

class WidgetCalendar extends WidgetDefinition {

    const MAX_TITLE_LENGTH = 50;

    public function writeText(StreamInterface $stream, Website $website, Request $request, $id, $data) {
        $text = $website->getText();
        $isModerator = $request->hasRank($website, Authentication::RANK_MODERATOR);

        // Title
        $title = "";
        if (isSet($data["title"]) && strLen($data["title"]) > 0) {
            $title = "<h2>" . $text->e($data["title"]) . "</h2>";
        }
        $stream->write($title);

        $oArticles = new ArticleRepository($website->getDatabase(), $isModerator);
        $articles = $oArticles->getArticlesDataUpcomingEvents();
        $articlesTemplate = new ArticleEventListTemplate($text, $articles, $isModerator, 0, true);
        
        // Articles
        $articlesTemplate->writeText($stream);
    }

    public function getEditor(Website $website, $id, $data) {
        $max_title_length = self::MAX_TITLE_LENGTH;
        $title = isSet($data["title"]) ? $data["title"] : "";
        return <<<EOT
            <p>
                <label for="title_$id">{$website->t("widgets.title")}</label>:<br />
                <input type="text" name="title_$id" id="title_$id" value="$title" maxlength="$max_title_length" />
            </p>
EOT;
    }

    public function parseData(Website $website, $id) {
        $data = [];
        $data["title"] = $website->getRequestString("title_" . $id, "");
        if (strLen($data["title"]) > self::MAX_TITLE_LENGTH) {
            // Limit title length
            $website->addError($website->t("widgets.title") . " " . $website->tReplaced("errors.too_long_num", self::MAX_TITLE_LENGTH));
            $data["valid"] = false;
        }
        return $data;
    }

}

// Register itself
$this->registerWidget(new WidgetCalendar());
