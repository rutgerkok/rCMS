<?php

$this->register_widget(new WidgetRkokCalendar());

class WidgetRkokCalendar extends WidgetDefinition {
    const MAX_TITLE_LENGTH = 50;
    
    public function get_widget(Website $oWebsite, $id, $data) {
        $return_value = "";
        
        // Title
        if(isset($data["title"]) && strlen($data["title"]) > 0) {
            $return_value.= "<h2>" . htmlspecialchars($data["title"]) . "</h2>";
        }
        
        $oCal = new Calendar($oWebsite, $oWebsite->get_database());
        $return_value.= '<h3>' . ucfirst(strftime('%B')) . ' ' . date('Y') . '</h3>'; // Current month and year
        $return_value.= $oCal->get_calendar(291);
        $return_value.= "\n" . '<p> <a class="arrow" href="' . $oWebsite->get_url_page("calendar") . '">' . $oWebsite->t("calendar.calendar_for_twelve_months") . '</a> </p>'; //link voor jaarkalender
        return $return_value;
    }

    public function get_editor(Website $oWebsite, $id, $data) {
        $max_title_length = self::MAX_TITLE_LENGTH;
        $title = isset($data["title"])? $data["title"] : "";
        return <<<EOT
            <p>
                <label for="title_$id">{$oWebsite->t("widgets.title")}</label>:<br />
                <input type="text" name="title_$id" id="title_$id" value="$title" maxlength="$max_title_length" />
            </p>
EOT;
    }

    public function parse_data(Website $oWebsite, $id) {
        $data = array();
        $data["title"] = $oWebsite->get_request_var("title_" . $id, "");
        if(strlen($data["title"]) > self::MAX_TITLE_LENGTH) {
            // Limit title length
            $oWebsite->add_error($oWebsite->t("widgets.title") . " " . $oWebsite->t_replaced("errors.too_long_num", self::MAX_TITLE_LENGTH));
            $data["valid"] = false;
        }
        return $data;
    }

}

?>