<?php

$this->register_widget(new WidgetRkokCalendar());

class WidgetRkokCalendar extends WidgetDefinition {

    public function get_widget(Website $oWebsite, $id, $data) {
        $oCal = new Calendar($oWebsite, $oWebsite->get_database());
        $return_value = "";
        $return_value.= '<h3>' . $oWebsite->t("calendar.calendar_for") . ' ' . strftime('%B') . ' ' . date('Y') . '</h3>'; //huidige maand en jaar
        $return_value.= $oCal->get_calendar(291);
        $return_value.= "\n" . '<p> <a class="arrow" href="' . $oWebsite->get_url_page("calendar") . '">' . $oWebsite->t("calendar.calendar_for_twelve_months") . '</a> </p>'; //link voor jaarkalender
        return $return_value;
    }

    public function get_editor(Website $oWebsite, $id, $data) {
        return ""; // Left empty
    }

    public function parse_data(Website $oWebsite, $id) {
        // Left empty
    }

}

?>