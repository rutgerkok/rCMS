<?php

namespace Rcms\Extend\Widget;

use Rcms\Core\Comments;
use Rcms\Core\Validate;
use Rcms\Core\Website;
use Rcms\Core\WidgetDefinition;
use Rcms\Page\View\CommentsSmallView;

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

class WidgetComments extends WidgetDefinition {

    const MIN_COMMENTS = 1;
    const MAX_COMMENTS = 30;
    const DEFAULT_COMMENTS = 4;

    public function getWidget(Website $oWebsite, $id, $data) {
        $title = htmlSpecialChars($data["title"]);
        $amount = (int) $data["amount"];
        
        $commentLookup = new Comments($oWebsite);
        $latestComments = $commentLookup->getCommentsLatest($amount);
        $view = new CommentsSmallView($oWebsite, $latestComments);

        $textToDisplay = '<h2>' . $title . "</h2>\n";
        $textToDisplay.= $view->getText();
        return $textToDisplay;
    }

    public function getEditor(Website $oWebsite, $id, $data) {
        $titleUnescaped = isSet($data["title"]) ? $data["title"] : $oWebsite->t("comments.comments");
        $title = htmlSpecialChars($titleUnescaped);
        $amount = isSet($data["amount"])? max(1, (int) $data["amount"]) : self::DEFAULT_COMMENTS;
        $minComments = self::MIN_COMMENTS;
        $maxComments = self::MAX_COMMENTS;
        return <<<FORM
            <p>
                <label for="title_$id">{$oWebsite->t("widgets.title")}:</label><br />
                <input type="text" name="title_$id" id="title_$id" value="$title" />
            </p>

            <p>
                <label for="amount_$id">{$oWebsite->t("editor.comment.count")}:</label><span class="required">*</span><br />
                <input type="number" name="amount_$id" id="amount_$id" min="$minComments" max="$maxComments" value="$amount" />
            </p>
FORM;
    }

    public function parseData(Website $oWebsite, $id) {
        $settingsArray = array();

        // Title
        $settingsArray["title"] = trim($oWebsite->getRequestString("title_" . $id, ""));

        // Amount
        $settingsArray["amount"] = $oWebsite->getRequestInt("amount_" . $id, 5);
        $amount = $settingsArray["amount"];
        if (!Validate::range($amount, self::MIN_COMMENTS, self::MAX_COMMENTS)) {
            $settingsArray["valid"] = false;
            $oWebsite->addError($oWebsite->t("editor.comment.count") . " " . Validate::getLastError($oWebsite));
        }

        return $settingsArray;
    }

}

// Register itself
$this->registerWidget(new WidgetComments());
