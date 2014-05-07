<?php

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

/**
 * Renders a list of articles with buttons to go the next or previous page
 */
class CalendarView extends View {

    const MONDAY = 1;

    /**
     * @var DateTime The month that this calender represents. 
     */
    protected $month;

    /**
     * @var Article[][] Articles on each day in the month.
     */
    protected $articlesByDay;

    /**
     * Constructs a new calendar view.
     * @param Website $oWebsite The website object.
     * @param DateTime $month The month to display.
     * @param Article[] $articlesInMonth All articles in the month, doesn't have to be in any order.
     */
    public function __construct(Website $oWebsite, DateTime $month,
            array $articlesInMonth) {
        parent::__construct($oWebsite);
        $this->month = $month;

        // Index by day in month (but only for articles actually in this month)
        $monthNumber = $month->format('n');
        $this->articlesByDay = array();
        foreach ($articlesInMonth as $article) {
            if ($article->onCalendar !== null && $article->onCalendar->format('n') === $monthNumber) {
                $dayNumber = (int) $article->onCalendar->format('j');
                $this->articlesByDay[$dayNumber][] = $article;
            }
        }
    }

    /**
     * Gets the start date for the calendar. This will be a monday before or at
     * the first day of the month.
     * @return DateTime The start date.
     */
    protected function getStartDate() {
        $dayInterval = new DateInterval("P1D");

        // Get first day of the month
        $startDate = DateTime::createFromFormat("Y-m-d", $this->month->format("Y-m") . "-1");

        // Get first day for table
        while ($startDate->format('w') != self::MONDAY) {
            $startDate->sub($dayInterval);
        }

        return $startDate;
    }

    /**
     * Gets the short names of all days in the week, like ["Mon", "Tue", ..].
     * The week will start at Monday.
     * @return string[] The names of all days in the week.
     */
    protected function getWeekDayNames() {
        $oWebsite = $this->oWebsite;
        $weekDayCodes = array("mon", "tue", "wed", "thu", "fri", "sat", "sun");
        $weekDayNames = array();
        foreach ($weekDayCodes as $weekDayCode) {
            $weekDayNames[] = $oWebsite->t("calendar.weekday." . $weekDayCode);
        }
        return $weekDayNames;
    }

    public function getText() {
        return <<<CALENDAR
            <table style="width:97%;max-width:291px">
                <tr>
                    {$this->getTableHeader()}
                </tr>
                {$this->getTableBody()}
            </table>
CALENDAR;
    }

    /**
     * Gets all days in the week in &lt;th&gt; tags, joined together.
     */
    protected function getTableHeader() {
        $returnValue = '';
        foreach ($this->getWeekDayNames() as $weekDayName) {
            $returnValue .= <<<DAY
                 <th>$weekDayName</th>  
DAY;
        }
        return $returnValue;
    }

    /**
     * Gets all rows that make up the body of the table.
     * @return string The body of the table.
     */
    protected function getTableBody() {
        $dayInterval = new DateInterval("P1D");
        $date = $this->getStartDate();
        $returnValue = '';
        while ($this->month->diff($date)->m <= 0) {
            // Add row
            $returnValue.= '<tr>';
            for ($i = 0; $i < 7; $i++) {
                // Add cell
                $returnValue.= $this->getDayCell($date, $this->month);

                // Increment day
                $date->add($dayInterval);
            }
            $returnValue.= '</tr>';
        }
        return $returnValue;
    }

    /**
     * Gets the code for a single cell.
     * @param DateTime $date The day.
     * @param DateTime $calendarMonth The month the calendar is displaying.
     * @return string The code for the cell.
     */
    protected function getDayCell(DateTime $date, DateTime $calendarMonth) {
        $inOtherMonth = $date->format('n') !== $calendarMonth->format('n');
        $dayNumber = (int) $date->format('j');

        // Tooltip
        $onMouseOverAttr = "";
        $tooltip = "";
        $tooltipIdAttr = "";
        if (!$inOtherMonth) {
            $tooltip = $this->getTooltip($date);
            $tooltipId = "tooltip_" . $date->format("Ymd");
            $tooltipIdAttr = 'id="' . $tooltipId . '"';
            $onMouseOverAttr = 'onmouseover="tooltip(this, document.getElementById(\'' . $tooltipId . '\').innerHTML)"';
        }

        return <<<CELL
            <td class="{$this->getCellClasses($date, $calendarMonth)}" {$onMouseOverAttr}>
                {$dayNumber}
 
                <div class="tooltip_contents" {$tooltipIdAttr}>
                    {$tooltip}
                </div>
            </td>
CELL;
    }

    /**
     * Gets the CSS class(es) for the given day in the month.
     * Note: may be called for dates outside the current month, the "gray"
     * dates.
     * @param DateTime $date The day.
     * @param DateTime $calendarMonth The month the calendar is displaying.
     * @return string The class(es). Multiple classes are separated with a space.
     */
    protected function getCellClasses(DateTime $date, DateTime $calendarMonth) {
        $inOtherMonth = $date->format('n') !== $calendarMonth->format('n');
        $cellClass = $inOtherMonth ? 'calendar_other_month' : 'calendar_current_month';
        $dayNumber = (int) $date->format('j');

        // Highlight cells with articles
        if (!$inOtherMonth && isSet($this->articlesByDay[$dayNumber])) {
            $cellClass .= " calendar_active_date";
        }

        return $cellClass;
    }

    /**
     * Gets the localized name of the given month.
     * @param DateTime $month The month to look up.
     */
    public function getMonthName(DateTime $month) {
        return $this->oWebsite->t("calendar.month." . strToLower($month->format("F")));
    }

    /**
     * Gets the code for inside the tooltip displayed for a cell. This method
     * will never be called for dates outside the current month, the "gray"
     * dates.
     * @param DateTime $date The date.
     * @return string The code.
     */
    protected function getTooltip(DateTime $date) {
        $oWebsite = $this->oWebsite;
        $dayNumber = (int) $date->format('j');

        $tooltip = "<h3>{$dayNumber} {$this->getMonthName($date)} {$date->format('Y')}</h3>";

        // Add all articles
        $tooltip.= $this->getTooltipArticleList($date);

        // End of tooltip
        if ($oWebsite->isLoggedInAsStaff()) {
            $tooltip.= <<<TOOLTIP_END
                <p>
                    <a class="arrow" href="{$oWebsite->getUrlPage("edit_article", 0, array(
                        "article_eventdate" => $date->format("Y-m-d"),
                        "article_eventtime" => "12:00"))}">
                        {$oWebsite->t("articles.create")}
                    </a>
                </p>
TOOLTIP_END;
        }
        return $tooltip;
    }

    /**
     * Gets the HTML list of the articles on the given day. This method
     * will never be called for dates outside the current month, the "gray"
     * dates.
     * @param DateTime $date The day.
     * @return string The HTML list, or a message if there are none.
     */
    protected function getTooltipArticleList(DateTime $date) {
        $oWebsite = $this->oWebsite;
        $dayNumber = (int) $date->format('j');

        if (!isSet($this->articlesByDay[$dayNumber])) {
            return "<p><em>" . $oWebsite->t("calendar.no_activities_today") . "</em></p>";
        }

        $articles = $this->articlesByDay[$dayNumber];
        $tooltip = "<ul>";
        foreach ($articles as $article) {
            $title = htmlSpecialChars($article->title);
            $intro = htmlSpecialChars($article->intro);
            $tooltip.= <<<TOOLTIP_ELEMENT
                <li title="{$intro}">
                    <a href="{$oWebsite->getUrlPage("article", $article->id)}">
                        {$title}
                    </a>
                </li>
TOOLTIP_ELEMENT;
        }
        $tooltip.= "</ul>";
        return $tooltip;
    }

}
