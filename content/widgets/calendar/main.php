<?php

namespace Rkok\Extend\Widget;

use DateTime;

use Rcms\Core\ArticleRepository;
use Rcms\Core\Website;
use Rcms\Core\WidgetDefinition;
use Rcms\Page\View\CalendarView;

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

class WidgetCalendar extends WidgetDefinition {

    const MAX_TITLE_LENGTH = 50;

    public function getText(Website $website, $id, $data) {


        // Title
        $title = "";
        if (isSet($data["title"]) && strLen($data["title"]) > 0) {
            $title = "<h2>" . htmlSpecialChars($data["title"]) . "</h2>";
        }

        $now = new DateTime();
        $oArticles = new ArticleRepository($website);
        $articlesInMonth = $oArticles->getArticlesDataCalendarMonth($now);
        $calendar = new CalendarView($website->getText(), $now, $articlesInMonth, $website->isLoggedInAsStaff());

        $monthName = ucFirst($calendar->getMonthName($now));
        $year = $now->format('Y');
        return <<<WIDGET
            $title
            <h3>$monthName $year</h3>
            {$calendar->getText()}
            <p>
                <a class="arrow" href="{$website->getUrlPage("calendar", $year)}">
                    {$website->tReplaced("calendar.calendar_for_year", $year)}
                </a>
            </p>
WIDGET;
    }

    public function getEditor(Website $website, $id, $data) {
        $max_title_length = self::MAX_TITLE_LENGTH;
        $title = isSet($data["title"]) ? $data["title"] : "";
        return <<<EOT
            <p>
                <label for="title_$id">{$website->t("widgets.title")}</label>:<br />
                <input type="text" name="title_$id" id="title_$id" value="$title" maxlength="$max_title_length" />
            </p>
EOT;
    }

    public function parseData(Website $website, $id) {
        $data = array();
        $data["title"] = $website->getRequestString("title_" . $id, "");
        if (strLen($data["title"]) > self::MAX_TITLE_LENGTH) {
            // Limit title length
            $website->addError($website->t("widgets.title") . " " . $website->tReplaced("errors.too_long_num", self::MAX_TITLE_LENGTH));
            $data["valid"] = false;
        }
        return $data;
    }

}

// Register itself
$this->registerWidget(new WidgetCalendar());
