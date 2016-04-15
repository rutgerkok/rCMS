<?php

namespace Rcms\Extend\Widget;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Website;
use Rcms\Core\Widget\WidgetDefinition;
use Rcms\Page\View\Support\CKEditor;

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

class WidgetRkokText extends WidgetDefinition {

    public function writeText(StreamInterface $stream, Website $website, $id, $data) {
        if (!isSet($data["text"]) || !isSet($data["title"])) {
            return;
        }
        if (strLen($data["title"]) > 0) {
            $stream->write("<h2>" . htmlSpecialChars($data["title"]) . "</h2>\n");
        }
        $stream->write($data["text"]);
    }

    public function getEditor(Website $website, $id, $data) {
        $title = isSet($data["title"]) ? $data["title"] : "";
        $text = isSet($data["text"]) ? $data["text"] : "";
        $oEditor = new CKEditor($website->getText(), $website->getConfig(), $website->getThemeManager());

        // Title
        $textToDisplay = "<p>\n";
        $textToDisplay.= '<label for="title_' . $id . '">';
        $textToDisplay.= $website->t("widgets.title") . "</label>:<br />\n";
        $textToDisplay.= '<input type="text" name="title_' . $id . '" id="title_' . $id . '"';
        $textToDisplay.= 'value="' . htmlSpecialChars($title) . '" />' . "\n";
        $textToDisplay.= "</p>\n";

        // Text input
        $textToDisplay.= "<p>\n";
        $textToDisplay.= '<label for="text_' . $id . '">' . $website->t("editor.message") . "</label>:";
        $textToDisplay.= '<span class="required">*</span><br />' . "\n";
        $textToDisplay.= $oEditor->getEditor("text_" . $id, $text);
        $textToDisplay.= "</p>\n";
        return $textToDisplay;
    }

    public function parseData(Website $website, $id) {
        $return_array = [];

        // Title
        $return_array["title"] = $website->getRequestString("title_" . $id, "");
        $return_array["title"] = trim($return_array["title"]);

        // Text
        $return_array["text"] = $website->getRequestString("text_" . $id, "");
        $return_array["text"] = trim($return_array["text"]);
        if (strLen($return_array["text"]) == 0) {
            $website->addError($website->t("editor.message") . " " . $website->t("errors.not_entered"));
            $return_array["valid"] = false;
        }
        if (strip_tags($return_array["text"]) == $return_array["text"]) {
            // No HTML tags, add the needed <p> and <br />
            $return_array["text"] = "<p>" . nl2br($return_array["text"], true) . "</p>";
        }

        return $return_array;
    }

}

// Register itself
$this->registerWidget(new WidgetRkokText());
