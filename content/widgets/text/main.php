<?php

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

$this->registerWidget(new WidgetRkokText());

class WidgetRkokText extends WidgetDefinition {
    /*
     * Implementation detail:
     * (HTML) tags are saved unfiltered in the database, but filtered when displayed.
     */

    public function getWidget(Website $oWebsite, $id, $data) {
        if (!isSet($data["text"]) || !isSet($data["title"])) {
            return "";
        }
        $returnValue = "";
        if (strLen($data["title"]) > 0) {
            $returnValue.= "<h2>" . htmlSpecialChars($data["title"]) . "</h2>\n";
        }
        $returnValue.= $data["text"];
        return $returnValue;
    }

    public function getEditor(Website $oWebsite, $id, $data) {
        $title = isSet($data["title"]) ? $data["title"] : "";
        $text = isSet($data["text"]) ? $data["text"] : "";
        $oEditor = new Editor($oWebsite);

        // Title
        $textToDisplay = "<p>\n";
        $textToDisplay.= '<label for="title_' . $id . '">';
        $textToDisplay.= $oWebsite->t("widgets.title") . "</label>:<br />\n";
        $textToDisplay.= '<input type="text" name="title_' . $id . '" id="title_' . $id . '"';
        $textToDisplay.= 'value="' . htmlSpecialChars($title) . '" />' . "\n";
        $textToDisplay.= "</p>\n";

        // Text input
        $textToDisplay.= "<p>\n";
        $textToDisplay.= '<label for="text_' . $id . '">' . $oWebsite->t("editor.message") . "</label>:";
        $textToDisplay.= '<span class="required">*</span><br />' . "\n";
        $textToDisplay.= $oEditor->get_text_editor("text_" . $id, $text);
        $textToDisplay.= "</p>\n";
        return $textToDisplay;
    }

    public function parseData(Website $oWebsite, $id) {
        $return_array = array();

        // Title
        $return_array["title"] = $oWebsite->getRequestString("title_" . $id, "");
        $return_array["title"] = trim($return_array["title"]);

        // Text
        $return_array["text"] = $oWebsite->getRequestString("text_" . $id, "");
        $return_array["text"] = trim($return_array["text"]);
        if (strLen($return_array["text"]) == 0) {
            $oWebsite->addError($oWebsite->t("editor.message") . " " . $oWebsite->t("errors.not_entered"));
            $return_array["valid"] = false;
        }
        if (strip_tags($return_array["text"]) == $return_array["text"]) {
            // No HTML tags, add the needed <p> and <br />
            $return_array["text"] = "<p>" . nl2br($return_array["text"], true) . "</p>";
        }

        return $return_array;
    }

}

?>