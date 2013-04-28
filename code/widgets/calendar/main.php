<?php

$this->register_widget(new WidgetRkokCalendar());

class WidgetRkokCalendar extends WidgetDefinition {

    public function echo_widget(Website $oWebsite, $id, $data) {
        $oCal = new Calendar($oWebsite, $oWebsite->get_database());
        echo '<h3>' . $oWebsite->t("calendar.calendar_for") . ' ' . strftime('%B') . ' ' . date('Y') . '</h3>'; //huidige maand en jaar
        echo $oCal->get_calendar(291);
        echo "\n" . '<p> <a class="arrow" href="' . $oWebsite->get_url_page("calendar") . '">' . $oWebsite->t("calendar.calendar_for_twelve_months") . '</a> </p>'; //link voor jaarkalender
    }

    public function get_editor(Website $oWebsite, $id, $data) {
        
    }

    public function parse_data(Website $oWebsite, $id) {
        
    }

}

?>