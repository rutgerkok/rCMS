<?php

$this->registerWidget(new WidgetRkokCalendar());

class WidgetRkokCalendar extends WidgetDefinition {
    const MAX_TITLE_LENGTH = 50;
    
    public function getWidget(Website $oWebsite, $id, $data) {
        $returnValue = "";
        
        // Title
        if(isSet($data["title"]) && strLen($data["title"]) > 0) {
            $returnValue.= "<h2>" . htmlSpecialChars($data["title"]) . "</h2>";
        }
        
        $oCal = new Calendar($oWebsite, $oWebsite->getDatabase());
        $returnValue.= '<h3>' . ucfirst(strftime('%B')) . ' ' . date('Y') . '</h3>'; // Current month and year
        $returnValue.= $oCal->get_calendar();
        $returnValue.= "\n" . '<p> <a class="arrow" href="' . $oWebsite->getUrlPage("calendar") . '">' . $oWebsite->t("calendar.calendar_for_twelve_months") . '</a> </p>'; //link voor jaarkalender
        return $returnValue;
    }

    public function getEditor(Website $oWebsite, $id, $data) {
        $max_title_length = self::MAX_TITLE_LENGTH;
        $title = isSet($data["title"])? $data["title"] : "";
        return <<<EOT
            <p>
                <label for="title_$id">{$oWebsite->t("widgets.title")}</label>:<br />
                <input type="text" name="title_$id" id="title_$id" value="$title" maxlength="$max_title_length" />
            </p>
EOT;
    }

    public function parseData(Website $oWebsite, $id) {
        $data = array();
        $data["title"] = $oWebsite->getRequestString("title_" . $id, "");
        if(strLen($data["title"]) > self::MAX_TITLE_LENGTH) {
            // Limit title length
            $oWebsite->addError($oWebsite->t("widgets.title") . " " . $oWebsite->tReplaced("errors.too_long_num", self::MAX_TITLE_LENGTH));
            $data["valid"] = false;
        }
        return $data;
    }

}

?>