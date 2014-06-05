<?php

namespace Rcms\Page\View;

use Rcms\Core\Article;
use Rcms\Core\Website;

/**
 * A view that displays the article titles grouped by month and year
 */
class ArticleArchiveView extends View {

    protected $selectedCategory;
    protected $selectedYear;

    /**
     * @var Article[] All articles of this year.
     */
    protected $articles;

    /**
     * @var int[] All categories included in the menu bar of this archive.
     */
    protected $categories;

    /**
     * @var int[] Number of articles in each year, $year => $count
     */
    protected $articleCountInYears;

    /**
     * Creates a new view for displaying the article archive.
     * @param Website $oWebsite The website object.
     * @param Article[] $articles Articles to display.
     * @param int[] $categories Ids of the categories included in the menu bar.
     * @param int[] $articleCountInYears $year => $articlesInThatYear
     * @param int $selectedCategory Id of the currently selected category.
     * @param int $selectedYear Id of the currently selected year.
     */
    public function __construct(Website $oWebsite, $articles, $categories,
            $articleCountInYears, $selectedCategory, $selectedYear) {
        $this->articles = $articles;
        $this->categories = $categories;
        $this->articleCountInYears = $articleCountInYears;
        $this->selectedCategory = (int) $selectedCategory;
        $this->selectedYear = (int) $selectedYear;
        parent::__construct($oWebsite);
    }

    /**
     * Gets the markup for the category selector of the menu.
     * @return string The markup for the category selector.
     */
    protected function getCategoriesMenu() {
        $oWebsite = $this->oWebsite;
        $textToDisplay = '';

        // Any category
        if ($this->selectedCategory == 0) {
            $textToDisplay.= '<strong>' . $oWebsite->t("categories.all") . "</strong>&nbsp;&nbsp;\n";
        } else {
            $textToDisplay.= '<a href="' . $oWebsite->getUrlPage("archive", 0, array("year" => $this->selectedYear)) . '">';
            $textToDisplay.= $oWebsite->t("categories.all") . "</a>&nbsp;&nbsp;\n";
        }
        // Other categories
        foreach ($this->categories as $id => $categoryName) {
            if ($id == $this->selectedCategory) {
                $textToDisplay.= '<strong>' . $categoryName . "</strong>&nbsp;&nbsp;\n";
            } else {
                $textToDisplay.= '<a href="' . $oWebsite->getUrlPage("archive", $id, array("year" => $this->selectedYear)) . '">';
                $textToDisplay.= $categoryName . "</a>&nbsp;&nbsp;\n";
            }
        }
        return $textToDisplay;
    }

    /**
     * Gets the part of the menu that displays all available years.
     * @return string The markup for the year selector.
     */
    protected function getYearsMenu() {
        $oWebsite = $this->oWebsite;

        // Any year
        if ($this->selectedYear == 0) {
            $textToDisplay = '<br /><strong>' . $oWebsite->t("articles.archive.any_year") . "</strong>&nbsp;&nbsp;\n";
        } else {
            $textToDisplay = '<br /><a href="' . $oWebsite->getUrlPage("archive", $this->selectedCategory, array("year" => 0)) . '">';
            $textToDisplay.= $oWebsite->t("articles.archive.any_year") . "</a>&nbsp;&nbsp;\n";
        }

        // Other years
        foreach ($this->articleCountInYears as $year => $articleCountInYear) {
            if ($year == $this->selectedYear) {
                $textToDisplay.= '<strong>' . $year . "</strong>&nbsp;&nbsp;\n";
            } else {
                $textToDisplay.= '<a href="' . $oWebsite->getUrlPage("archive", $this->selectedCategory, array("year" => $year)) . '">';
                $textToDisplay.= $year . "</a>&nbsp;&nbsp;\n";
            }
        }

        return $textToDisplay;
    }

    /**
     * Gets the markup for the menubar of the archive.
     * @return string The returned markup.
     */
    protected function getMenubar() {
        return <<<MENUBAR
            <p class="result_selector_menu">
                {$this->getCategoriesMenu()}
                {$this->getYearsMenu()}
            </p>
MENUBAR;
    }

    /**
     * Gets the markup for a single row in the table, from &lt;tr&gt;
     * to &lt;.tr&gt;.
     * @param Article $article The article to display in the row.
     * @param boolean $loggedIn True if edit/delete column must be shown,
     * false otherwise.
     * @return string The markup for the row.
     */
    protected function getTableRow(Article $article, $loggedIn) {
        $oWebsite = $this->oWebsite;

        $textToDisplay = '<tr><td><a href="' . $oWebsite->getUrlPage("article", $article->id);
        $textToDisplay.= '">' . $article->title . "</a>";
        if ($loggedIn) {
            // Display edit links in new cell
            $textToDisplay.= '</td><td style="width:20%">';
            $textToDisplay.= '<a class="arrow" href="' . $oWebsite->getUrlPage("edit_article", $article->id);
            $textToDisplay.= '">' . $oWebsite->t("main.edit") . "</a>";
            $textToDisplay.= ' <a class="arrow" href="' . $oWebsite->getUrlPage("delete_article", $article->id);
            $textToDisplay.= '">' . $oWebsite->t("main.delete") . "</a>";
        }
        $textToDisplay.= "</td></tr>\n";
        return $textToDisplay;
    }

    /**
     * Gets the markup for the whole articles table. If there are no articles,
     * a paragraph with an error message is returned instead.
     * @return string The markup.
     */
    protected function getArticlesTable() {
        $textToDisplay = "";
        $loggedIn = $this->oWebsite->isLoggedInAsStaff();
        $previousMonth = -1;
        $previousYear = -1;
        $tableStarted = false;

        // Account for extra edit/delete column when logged in as staff
        $colspan = $loggedIn ? ' colspan="2"' : "";

        // Start the loop, grouping articles by month
        foreach ($this->articles as $article) {
            $date = strToTime($article->created);
            $currentMonth = date('n', $date);
            $currentYear = date('Y', $date);
            if ($currentMonth != $previousMonth || $currentYear != $previousYear) {
                if ($tableStarted) {
                    // Close off existing table
                    $textToDisplay.= "</table>\n";
                }

                // Start new table
                $textToDisplay.= '<table style="width:90%">';
                $textToDisplay.= '<tr><th ' . $colspan . '>' . ucfirst(strftime("%B %Y", $date)) . '</th></tr>';
                $tableStarted = true;

                // Set new previous values
                $previousMonth = $currentMonth;
                $previousYear = $currentYear;
            }

            $textToDisplay.= $this->getTableRow($article, $loggedIn);
        }

        // Close off tables
        if ($tableStarted) {
            $textToDisplay.="</table>\n";
        } else {
            // No articles found
            $textToDisplay.= <<<NOT_FOUND
                <p>
                    <em>{$this->oWebsite->t("articles.archive.not_found")}</em>
                </p>
NOT_FOUND;
        }

        return $textToDisplay;
    }

    public function getText() {
        // Archive description
        $textToDisplay = "<p>{$this->oWebsite->t("articles.archive.explained")}</p>";

        // Menu bar
        $textToDisplay.= $this->getMenubar();

        // Display table with articles
        $textToDisplay.= $this->getArticlesTable();

        return $textToDisplay;
    }

}
