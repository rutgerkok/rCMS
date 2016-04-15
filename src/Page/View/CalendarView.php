<?php

namespace Rcms\Page\View;

use Psr\Http\Message\StreamInterface;
use DateTime;
use DateInterval;
use Rcms\Core\Text;

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
     * @var boolean True to show edit links, false otherwise.
     */
    private $editLinks;

    /**
     * Constructs a new calendar view.
     * @param Text $text The website object.
     * @param DateTime $month The month to display.
     * @param Article[] $articlesInMonth All articles in the month, doesn't have to be in any order.
     * @param boolean $editLinks True to show edit links, false otherwise.
     */
    public function __construct(Text $text, DateTime $month,
            array $articlesInMonth, $editLinks) {
        parent::__construct($text);
        $this->month = $month;
        $this->editLinks = (boolean) $editLinks;

        // Index by day in month (but only for articles actually in this month)
        $monthNumber = $month->format('n');
        $this->articlesByDay = [];
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
        $text = $this->text;
        $weekDayCodes = ["mon", "tue", "wed", "thu", "fri", "sat", "sun"];
        $weekDayNames = [];
        foreach ($weekDayCodes as $weekDayCode) {
            $weekDayNames[] = $text->t("calendar.weekday." . $weekDayCode);
        }
        return $weekDayNames;
    }

    public function writeText(StreamInterface $stream) {
        $stream->write(<<<CALENDAR
            <table style="width:97%;max-width:291px">
                <tr>
                    {$this->getTableHeader()}
                </tr>
                {$this->getTableBody()}
            </table>
CALENDAR
        );
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
        // Always use six rows for consistency with other calendars that may be
        // on the page. Six rows are always enough to display all dates.
        for ($i = 0; $i < 6; $i++) {
            // Add row
            $returnValue.= '<tr>';
            for ($j = 0; $j < 7; $j++) {
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
        return $this->text->t("calendar.month." . strToLower($month->format("F")));
    }

    /**
     * Gets the code for inside the tooltip displayed for a cell. This method
     * will never be called for dates outside the current month, the "gray"
     * dates.
     * @param DateTime $date The date.
     * @return string The code.
     */
    protected function getTooltip(DateTime $date) {
        $text = $this->text;
        $dayNumber = (int) $date->format('j');

        $tooltip = "<h3>{$dayNumber} {$this->getMonthName($date)} {$date->format('Y')}</h3>";

        // Add all articles
        $tooltip.= $this->getTooltipArticleList($date);

        // End of tooltip
        if ($this->editLinks) {
            $tooltip.= <<<TOOLTIP_END
                <p>
                    <a class="arrow" href="{$text->e($text->getUrlPage("edit_article", null, [
                        "article_eventdate" => $date->format("Y-m-d"),
                        "article_eventtime" => "12:00"]))}">
                        {$text->t("articles.create")}
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
        $text = $this->text;
        $dayNumber = (int) $date->format('j');

        if (!isSet($this->articlesByDay[$dayNumber])) {
            return "<p><em>" . $text->t("calendar.no_activities_today") . "</em></p>";
        }

        $articles = $this->articlesByDay[$dayNumber];
        $tooltip = "<ul>";
        foreach ($articles as $article) {
            $title = htmlSpecialChars($article->getTitle());
            $intro = htmlSpecialChars($article->getIntro());
            $tooltip.= <<<TOOLTIP_ELEMENT
                <li title="{$intro}">
                    <a href="{$text->e($text->getUrlPage("article", $article->getId()))}">
                        {$title}
                    </a>
                </li>
TOOLTIP_ELEMENT;
        }
        $tooltip.= "</ul>";
        return $tooltip;
    }

}
