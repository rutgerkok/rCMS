<?php

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

class CalendarPage extends Page {

    /** @var Article[] Articles in a year. */
    protected $articlesInYear;
    
    /** @var DateTime The year of the articles. */
    protected $year;
    
    private $yearNumber;
    
    public function init(Website $oWebsite) {
        $oArticles = new Articles($oWebsite);
        $yearNumber = $oWebsite->getRequestInt("id", date('Y'));
        if ($yearNumber < 1500 || $yearNumber > 2800) {
            $yearNumber = date('Y');
        }
        $this->year = DateTime::createFromFormat('Y', $yearNumber);
        $this->yearNumber = $yearNumber;

        $this->articlesInYear = $oArticles->getArticlesDataCalendarYear($this->year);
    }

    public function getPageTitle(Website $oWebsite) {
        return $oWebsite->tReplaced("calendar.calendar_for_year", $this->yearNumber);
    }

    public function getView(Website $oWebsite) {
        return new YearCalendarView($oWebsite, $this->year, $this->articlesInYear);
    }

}
