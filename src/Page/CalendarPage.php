<?php

namespace Rcms\Page;

use DateTime;
use Rcms\Core\Articles;
use Rcms\Core\Request;
use Rcms\Core\Website;
use Rcms\Page\View\YearCalendarView;

class CalendarPage extends Page {

    const MIN_YEAR = 1500;
    const MAX_YEAR = 2800;

    /** @var Article[] Articles in a year. */
    protected $articlesInYear;

    /** @var DateTime The year of the articles. */
    protected $year;
    private $yearNumber;

    public function init(Request $request) {
        $oWebsite = $request->getWebsite();
        $oArticles = new Articles($oWebsite);
        $yearNumber = $request->getParamInt(0, date('Y'));
        if ($yearNumber < self::MIN_YEAR || $yearNumber > self::MAX_YEAR) {
            $yearNumber = date('Y');
        }
        $this->year = DateTime::createFromFormat('Y', $yearNumber);
        $this->yearNumber = $yearNumber;

        $this->articlesInYear = $oArticles->getArticlesDataCalendarYear($this->year);
    }

    public function getPageTitle(Request $request) {
        return $request->getWebsite()->tReplaced("calendar.calendar_for_year", $this->yearNumber);
    }

    public function getView(Website $website) {
        return new YearCalendarView($website, $this->year, $this->articlesInYear);
    }

}
