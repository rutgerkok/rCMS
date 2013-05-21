<?php

$this->register_widget(new WidgetRkokText());

class WidgetRkokText extends WidgetDefinition {
    /*
     * Implementation detail:
     * (HTML) tags are saved unfiltered in the database, but filtered when displayed.
     */
    
    
    public function get_widget(Website $oWebsite, $id, $data) {
        if (!isset($data["text"]) || !isset($data["title"])) {
            return "";
        }
        $return_value = "";
        if (strlen($data["title"]) > 0) {
            $return_value.= "<h2>" . htmlspecialchars($data["title"]) . "</h2>\n";
        }
        $return_value.= $data["text"];
        return $return_value;
    }

    public function get_editor(Website $oWebsite, $id, $data) {
        $title = isset($data["title"]) ? $data["title"] : "";
        $text = isset($data["text"]) ? $data["text"] : "";
        $oEditor = new Editor($oWebsite);

        // Title
        $text_to_display = "<p>\n";
        $text_to_display.= '<label for="title_' . $id . '">';
        $text_to_display.= $oWebsite->t("widgets.title") . "</label>:<br />\n";
        $text_to_display.= '<input type="text" name="title_' . $id . '" id="title_' . $id . '"';
        $text_to_display.= 'value="' . htmlspecialchars($title) . '" />' . "\n";
        $text_to_display.= "</p>\n";

        // Text input
        $text_to_display.= "<p>\n";
        $text_to_display.= '<label for="text_' . $id . '">' . $oWebsite->t("editor.message") . "</label>:";
        $text_to_display.= '<span class="required">*</span><br />' . "\n";
        $text_to_display.= $oEditor->get_text_editor("text_" . $id, $text);
        $text_to_display.= "</p>\n";
        return $text_to_display;
    }

    public function parse_data(Website $oWebsite, $id) {
        $return_array = array();

        // Title
        $return_array["title"] = $oWebsite->get_request_var("title_" . $id, "");
        $return_array["title"] = trim($return_array["title"]);

        // Text
        $return_array["text"] = $oWebsite->get_request_var("text_" . $id, "");
        $return_array["text"] = trim($return_array["text"]);
        if (strlen($return_array["text"]) == 0) {
            $oWebsite->add_error($oWebsite->t("editor.message") . " " . $oWebsite->t("errors.not_entered"));
            $return_array["valid"] = false;
        }
        if(strip_tags($return_array["text"]) == $return_array["text"]) {
            // No HTML tags, add the needed <p> and <br />
            $return_array["text"] = "<p>" . nl2br($return_array["text"], true) . "</p>";
        }

        return $return_array;
    }
}

?>