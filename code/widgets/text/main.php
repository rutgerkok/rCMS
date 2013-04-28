<?php

$this->register_widget(new WidgetRkokText());

class WidgetRkokText extends WidgetDefinition {

    public function echo_widget(Website $oWebsite, $id, $data) {
        if (!isset($data["text"]) || !isset($data["title"]) || !isset($data["with_html_tags"])) {
            return;
        }
        if (strlen($data["title"]) > 0) {
            echo "<h2>" . $data["title"] . "</h2>\n";
        }
        if (!$data["with_html_tags"]) {
            echo "<p>" . nl2br(htmlentities($data["text"])) . "</p>\n";
        } else {
            echo $data["text"];
        }
    }

    public function get_editor(Website $oWebsite, $id, $data) {
        $with_html_tags = isset($data["with_html_tags"]) ? (boolean) $data["with_html_tags"] : true;
        $title = isset($data["title"]) ? $data["title"] : "";
        $text = isset($data["text"]) ? $data["text"] : "";

        // Title
        $text_to_display = "<p>\n";
        $text_to_display.= '<label for="title_' . $id . '">';
        $text_to_display.= $oWebsite->t("widgets.title") . "</label>:<br />\n";
        $text_to_display.= '<input type="text" name="title_' . $id . '" id="title_' . $id . '"';
        $text_to_display.= 'value="' . $title . '" />' . "\n";
        $text_to_display.= "</p>\n";

        // HTML radio buttons
        $text_to_display.= "<p>\n";
        $text_to_display.= $oWebsite->t("editor.with_html_tags") . ":";
        $text_to_display.= '<span class="required">*</span> ' . "\n";
        $text_to_display.= $this->get_radio_button("with_html_tags_" . $id, $oWebsite->t("main.yes"), 1, $with_html_tags);
        $text_to_display.= $this->get_radio_button("with_html_tags_" . $id, $oWebsite->t("main.no"), 0, !$with_html_tags);
        $text_to_display.= "</p>\n";

        // Text input
        $text_to_display.= "<p>\n";
        $text_to_display.= '<label for="text_' . $id . '">' . $oWebsite->t("editor.message") . "</label>:";
        $text_to_display.= '<span class="required">*</span><br />' . "\n";
        $text_to_display.= '<textarea name="text_' . $id . '" id="text_' . $id . '" rows="20" cols="100">';
        $text_to_display.= $text;
        $text_to_display.= '</textarea>';
        $text_to_display.= "</p>\n";
        return $text_to_display;
    }

    public function parse_data(Website $oWebsite, $id) {
        $return_array = array();

        // Title
        $return_array["title"] = isset($_REQUEST['title_' . $id]) ? $_REQUEST['title_' . $id] : "";
        $return_array["title"] = htmlentities(trim($return_array["title"]));

        // Text
        $return_array["text"] = isset($_REQUEST['text_' . $id]) ? $_REQUEST['text_' . $id] : "";
        $return_array["text"] = htmlentities(trim($return_array["text"]));
        if (strlen($return_array["text"]) == 0) {
            $oWebsite->add_error($oWebsite->t("editor.message") . " " . $oWebsite->t("errors.not_entered"));
            $return_array["valid"] = false;
        }

        // HTML tags
        if (isset($_REQUEST['with_html_tags_' . $id])) {
            $return_array["with_html_tags"] = (boolean) $_REQUEST['with_html_tags_' . $id];
        } else {
            $oWebsite->add_error('"' . $oWebsite->t("editor.with_html_tags") . '" ' . $oWebsite->t("errors.not_found"));
            $return_array["valid"] = false;
        }
        return $return_array;
    }

    private function get_radio_button($name, $label, $value, $selected) {
        $id = $name . "_" . $value;
        $text_to_display = '<label for="' . $id . '">';
        $text_to_display.= '<input id="' . $id . '" class="checkbox"';
        $text_to_display.= 'type="radio" name="' . $name . '" value="' . $value . '"';
        if ($selected) {
            $text_to_display.= ' checked="checked"';
        }
        $text_to_display.= ' />' . $label . "</label> ";
        return $text_to_display;
    }

}

?>