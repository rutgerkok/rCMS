<?php

namespace Rcms\Page\View;

use DateTime;
use Psr\Http\Message\StreamInterface;
use Rcms\Core\Text;

/**
 * Like CalendarView, but without tooltips and with clickable dates. This
 * implementation assumes that the date picker is opened in a popup window, and
 * that the parent window has a receiveDate JavaScript function with a single
 * parameter: a JavaScript Date object that represents the clicked date.
 */
class DatePickerView extends CalendarView {

    const LOOKBACK_YEARS = 4;
    const LOOKAHEAD_YEARS = 8;

    public function __construct(Text $text, DateTime $month,
            array $articlesInMonth) {
        // Edit links are never displayed, as the tooltips are not rendered at
        // all
        parent::__construct($text, $month, $articlesInMonth, false);
    }

    protected function getDayCell(DateTime $date, DateTime $calendarMonth) {
        $dayNumber = (int) $date->format('j');

        return <<<CELL
            <td class="{$this->getCellClasses($date, $calendarMonth)}"
                onclick="sendAndClose({$dayNumber})"
                style="cursor:pointer">
                {$dayNumber}
            </td>
CELL;
    }

    /**
     * Gets a select-tag that allows the user to select a year.
     * @return string The select-tag.
     */
    protected function getYearSelector() {
        $selectedYear = (int) $this->month->format('Y');

        $yearList = '<select id="year" name="year">';
        $currentYear = date('Y');
        for ($i = $currentYear - self::LOOKBACK_YEARS; $i < $currentYear + self::LOOKAHEAD_YEARS; $i++) {
            $selected = $i === $selectedYear ? 'selected="selected"' : '';
            $yearList.= '<option ' . $selected . ' value="' . $i . '">' . $i . '</option>';
        }
        $yearList.= '</select>';
        return $yearList;
    }

    /**
     * Gets a select-tag that allows the user to select a month.
     * @return string The select-tag.
     */
    protected function getMonthSelector() {
        $selectedMonth = (int) $this->month->format('n');

        $monthList = '<select id="month" name="month">';
        for ($i = 1; $i <= 12; $i++) {
            $month = DateTime::createFromFormat("n", $i);
            $selected = $selectedMonth === $i ? 'selected="selected"' : '';
            $monthList.= '<option ' . $selected . ' value="' . $i . '">' . $this->getMonthName($month) . '</option>';
        }
        $monthList.= '</select>';
        return $monthList;
    }

    public function writeText(StreamInterface $stream) {
        $stream->write(<<<TEXT
            <script type="text/javascript">
                {$this->getSendAndCloseFunction()}
            </script>
        
            <form action="datepicker.php" method="post" onchange="this.submit()">
                <p class="result_selector_menu">
                    {$this->getMonthSelector()}
                    {$this->getYearSelector()}
                </p>
            </form>
TEXT
        );
        parent::writeText($stream);
    }

    /**
     * Gets the JavaScript that provides the sendAndClose(int dayInMonth)
     * function.
     * @return string The JavaScript.
     */
    protected function getSendAndCloseFunction() {
        $selectedMonth = (int) $this->month->format('n');
        $javascriptMonth = $selectedMonth - 1; /* PHP uses 1-12, JS uses 0-11 */
        $selectedYear = (int) $this->month->format('Y');

        return <<<TEXT
            function sendAndClose(day) {
                var month = {$javascriptMonth};
                var year = {$selectedYear};
                var date = new Date(year, month, day, 0, 0, 0, 0);
                window.opener.receiveDate(date);
                window.close();
            }
TEXT;
    }

}
