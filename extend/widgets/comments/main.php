<?php

namespace Rcms\Extend\Widget;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\CommentRepository;
use Rcms\Core\Request;
use Rcms\Core\Validate;
use Rcms\Core\Website;
use Rcms\Core\Widget\WidgetDefinition;
use Rcms\Template\CommentsSmallTemplate;

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

class WidgetComments extends WidgetDefinition {

    const MIN_COMMENTS = 1;
    const MAX_COMMENTS = 30;
    const DEFAULT_COMMENTS = 4;

    public function writeText(StreamInterface $stream, Website $website, Request $request, $id, $data) {
        $title = htmlSpecialChars($data["title"]);
        $amount = (int) $data["amount"];
        
        $commentLookup = new CommentRepository($website->getDatabase());
        $latestComments = $commentLookup->getCommentsLatest($amount);
        $view = new CommentsSmallTemplate($website->getText(), $latestComments);

        $stream->write('<h2>' . $title . "</h2>\n");
        $view->writeText($stream);
    }

    public function getEditor(Website $website, $id, $data) {
        $titleUnescaped = isSet($data["title"]) ? $data["title"] : $website->t("comments.comments");
        $title = htmlSpecialChars($titleUnescaped);
        $amount = isSet($data["amount"])? max(1, (int) $data["amount"]) : self::DEFAULT_COMMENTS;
        $minComments = self::MIN_COMMENTS;
        $maxComments = self::MAX_COMMENTS;
        return <<<FORM
            <p>
                <label for="title_$id">{$website->t("widgets.title")}:</label><br />
                <input type="text" name="title_$id" id="title_$id" value="$title" />
            </p>

            <p>
                <label for="amount_$id">{$website->t("comments.count")}:</label><span class="required">*</span><br />
                <input type="number" name="amount_$id" id="amount_$id" min="$minComments" max="$maxComments" value="$amount" />
            </p>
FORM;
    }

    public function parseData(Website $website, $id) {
        $settingsArray = [];

        // Title
        $settingsArray["title"] = trim($website->getRequestString("title_" . $id, ""));

        // Amount
        $settingsArray["amount"] = $website->getRequestInt("amount_" . $id, 5);
        $amount = $settingsArray["amount"];
        if (!Validate::range($amount, self::MIN_COMMENTS, self::MAX_COMMENTS)) {
            $settingsArray["valid"] = false;
            $website->addError($website->t("comments.count") . " " . Validate::getLastError($website));
        }

        return $settingsArray;
    }

}

// Register itself
$this->registerWidget(new WidgetComments());
