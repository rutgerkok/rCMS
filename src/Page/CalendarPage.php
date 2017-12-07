<?php

namespace Rcms\Page;

use DateTime;
use Rcms\Core\Authentication;
use Rcms\Core\ArticleRepository;
use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Core\Website;
use Rcms\Template\YearCalendarTemplate;

class CalendarPage extends Page {

    const MIN_YEAR = 1500;
    const MAX_YEAR = 2800;

    /** @var Article[] Articles in a year. */
    protected $articlesInYear;

    /** @var DateTime The year of the articles. */
    private $year;
    private $yearNumber;

    private $isModerator;

    public function init(Website $website, Request $request) {
        $this->isModerator = $request->hasRank($website, Authentication::RANK_MODERATOR);
      
        $oArticles = new ArticleRepository($website->getDatabase(), $this->isModerator);
        $yearNumber = $request->getParamInt(0, date('Y'));
        if ($yearNumber < self::MIN_YEAR || $yearNumber > self::MAX_YEAR) {
            $yearNumber = date('Y');
        }
        $this->year = DateTime::createFromFormat('Y', $yearNumber);
        $this->yearNumber = $yearNumber;

        $this->articlesInYear = $oArticles->getArticlesDataCalendarYear($this->year);
    }

    public function getPageTitle(Text $text) {
        return $text->tReplaced("calendar.calendar_for_year", $this->yearNumber);
    }

    public function getTemplate(Text $text) {
        return new YearCalendarTemplate($text, $this->year, $this->articlesInYear, $this->isModerator);
    }

    public function getMinimumRank() {
        return Authentication::RANK_LOGGED_OUT;
    }

}
