<?php

namespace Rcms\Page\View;

use DateTime;
use Rcms\Core\Website;

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

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

    public function __construct(Website $oWebsite, DateTime $year,
            array $articlesInYear) {
        parent::__construct($oWebsite);
        $this->year = $year;
        $this->articlesInYear = $articlesInYear;
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
        $oWebsite = $this->oWebsite;
        $startYear = $this->year->format('Y') - self::LOOK_BACK_YEARS;
        $endYear = $this->year->format('Y') + self::LOOK_AHEAD_YEARS;
        
        
        $text = <<<START
             <p class="lijn">  
START;
        
        
        for ($i = $startYear; $i <= $endYear; $i++) {
            if ($i == $this->year->format("Y")) {
                $text.= <<<YEAR
                     <strong>$i</strong> 
YEAR;
            } else {
                $text.= <<<YEAR
                     <a href="{$oWebsite->getUrlPage("calendar", $i)}">$i</a> 
YEAR;
            }
        }
        $text.= <<<END
             </p>
END;
        return $text;
    }
    
    protected function getCalendars() {
        $oWebsite = $this->oWebsite;
        
        $text = "";
        for ($i = 1; $i <= 12; $i++) {
            $month = DateTime::createFromFormat("Y n", $this->year->format("Y") . ' ' . $i);
            $calendarView = new CalendarView($oWebsite, $month, $this->articlesInYear);
            $table = $calendarView->getText();
            
            $monthName = ucFirst($calendarView->getMonthName($month));
            $yearNumber = $month->format("Y");
            
            $text.= <<<MONTH
                <div class="calender_month_wrapper">
                    <h3>$monthName $yearNumber</h3>
                    $table
                </div>
MONTH;
        }
        return $text;
    }

}
