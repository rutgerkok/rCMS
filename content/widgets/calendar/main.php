<?php

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

class WidgetRkokCalendar extends WidgetDefinition {

    const MAX_TITLE_LENGTH = 50;

    public function getWidget(Website $oWebsite, $id, $data) {


        // Title
        $title = "";
        if (isSet($data["title"]) && strLen($data["title"]) > 0) {
            $title = "<h2>" . htmlSpecialChars($data["title"]) . "</h2>";
        }

        $now = new DateTime();
        $oArticles = new Articles($oWebsite);
        $articlesInMonth = $oArticles->getArticlesDataCalendarMonth($now);
        $calendar = new CalendarView($oWebsite, $now, $articlesInMonth);

        $monthName = ucFirst($calendar->getMonthName($now));
        $year = $now->format('Y');
        return <<<WIDGET
            $title
            <h3>$monthName $year</h3>
            {$calendar->getText()}
            <p>
                <a class="arrow" href="{$oWebsite->getUrlPage("calendar", $year)}">
                    {$oWebsite->tReplaced("calendar.calendar_for_year", $year)}
                </a>
            </p>
WIDGET;
    }

    public function getEditor(Website $oWebsite, $id, $data) {
        $max_title_length = self::MAX_TITLE_LENGTH;
        $title = isSet($data["title"]) ? $data["title"] : "";
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
        if (strLen($data["title"]) > self::MAX_TITLE_LENGTH) {
            // Limit title length
            $oWebsite->addError($oWebsite->t("widgets.title") . " " . $oWebsite->tReplaced("errors.too_long_num", self::MAX_TITLE_LENGTH));
            $data["valid"] = false;
        }
        return $data;
    }

}

// Register itself
$this->registerWidget(new WidgetRkokCalendar());
