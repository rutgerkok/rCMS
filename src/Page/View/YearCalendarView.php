<?php

namespace Rcms\Page\View;

use DateTime;
use Rcms\Core\Text;

/**
 * Shows all calendars for a given year
 */
class YearCalendarView extends View {

    const LOOK_BACK_YEARS = 2;
    const LOOK_AHEAD_YEARS = 2;

    /** @var DateTime The year of the articles. */
    protected $year;

    /** @var Article[] All articles in that year. */
    protected $articlesInYear;
    
    /** @var boolean True to show edit links, false otherwise. */
    private $createLinks;

    public function __construct(Text $text, DateTime $year,
            array $articlesInYear, $createLinks) {
        parent::__construct($text);
        $this->year = $year;
        $this->articlesInYear = $articlesInYear;
        $this->createLinks = (boolean) $createLinks;
    }

    public function getText() {
        return <<<CALENDAR_PAGE
            {$this->getYearSelector()}
                
            <div>
                {$this->getCalendars()}
            </div>
CALENDAR_PAGE;
    }

    protected function getYearSelector() {
        $text = $this->text;
        $startYear = $this->year->format('Y') - self::LOOK_BACK_YEARS;
        $endYear = $this->year->format('Y') + self::LOOK_AHEAD_YEARS;


        $returnValue = <<<START
             <p class="lijn">  
START;

        for ($i = $startYear; $i <= $endYear; $i++) {
            if ($i == $this->year->format("Y")) {
                $returnValue.= <<<YEAR
                     <strong>$i</strong> 
YEAR;
            } else {
                $returnValue.= <<<YEAR
                     <a href="{$text->getUrlPage("calendar", $i)}">$i</a> 
YEAR;
            }
        }
        $returnValue.= <<<END
             </p>
END;
        return $returnValue;
    }

    protected function getCalendars() {
        $text = $this->text;

        $returnValue = "";
        for ($i = 1; $i <= 12; $i++) {
            $month = DateTime::createFromFormat("Y n", $this->year->format("Y") . ' ' . $i);
            $calendarView = new CalendarView($text, $month, $this->articlesInYear, $this->createLinks);
            $table = $calendarView->getText();

            $monthName = ucFirst($calendarView->getMonthName($month));
            $yearNumber = $month->format("Y");

            $returnValue.= <<<MONTH
                <div class="calender_month_wrapper">
                    <h3>$monthName $yearNumber</h3>
                    $table
                </div>
MONTH;
        }
        return $returnValue;
    }

}
