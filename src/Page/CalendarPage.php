<?php

namespace Rcms\Page;

use DateTime;
use Rcms\Core\ArticleRepository;
use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Page\View\YearCalendarView;

class CalendarPage extends Page {

    const MIN_YEAR = 1500;
    const MAX_YEAR = 2800;

    /** @var Article[] Articles in a year. */
    protected $articlesInYear;

    /** @var DateTime The year of the articles. */
    private $year;
    private $yearNumber;

    private $showCreateLinks;

    public function init(Request $request) {
        $oWebsite = $request->getWebsite();
        $oArticles = new ArticleRepository($oWebsite);
        $yearNumber = $request->getParamInt(0, date('Y'));
        if ($yearNumber < self::MIN_YEAR || $yearNumber > self::MAX_YEAR) {
            $yearNumber = date('Y');
        }
        $this->year = DateTime::createFromFormat('Y', $yearNumber);
        $this->yearNumber = $yearNumber;

        $this->articlesInYear = $oArticles->getArticlesDataCalendarYear($this->year);
        $this->showCreateLinks = $oWebsite->isLoggedInAsStaff();
    }

    public function getPageTitle(Text $text) {
        return $text->tReplaced("calendar.calendar_for_year", $this->yearNumber);
    }

    public function getView(Text $text) {
        return new YearCalendarView($text, $this->year, $this->articlesInYear, $this->showCreateLinks);
    }

}
