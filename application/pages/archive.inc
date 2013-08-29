<h2><?php echo $this->t("articles.archive") ?></h2>
<?php
$textToDisplay = "";

$year = (int) $this->getRequestString("year", 0);
$category = (int) $this->getRequestString("id", 0);
$loggedIn = $this->isLoggedInAsStaff(false);

$oArticles = new Articles($this);

// Archive description
$textToDisplay.= "<p>" . $this->t("articles.archive.explained") . "</p>";

// Start menu bar
$textToDisplay.='<p class="lijn">';

// The categories
// Any category
if ($category == 0) {
    $textToDisplay.= '<strong>' . $this->t("categories.all") . "</strong>&nbsp;&nbsp;\n";
} else {
    $textToDisplay.= '<a href="' . $this->getUrlPage("archive", 0, array("year" => $year)) . '">';
    $textToDisplay.= $this->t("categories.all") . "</a>&nbsp;&nbsp;\n";
}
// Other categories
$oCategories = new Categories($this);
$categories = $oCategories->getCategories();
foreach ($categories as $i => $category_name) {
    if ($i == $category) {
        $textToDisplay.= '<strong>' . $category_name . "</strong>&nbsp;&nbsp;\n";
    } else {
        $textToDisplay.= '<a href="' . $this->getUrlPage("archive", $i, array("year" => $year)) . '">';
        $textToDisplay.= $category_name . "</a>&nbsp;&nbsp;\n";
    }
}

// The years
// Any year
if ($year == 0) {
    $textToDisplay.= '<br /><strong>' . $this->t("articles.archive.any_year") . "</strong>&nbsp;&nbsp;\n";
} else {
    $textToDisplay.= '<br /><a href="' . $this->getUrlPage("archive", $category, array("year" => 0)) . '">';
    $textToDisplay.= $this->t("articles.archive.any_year") . "</a>&nbsp;&nbsp;\n";
}
// Other years
$articles_in_years = $oArticles->getArticleCountInYears($category);
foreach($articles_in_years as $i => $articles_in_year) {
    if ($i == $year) {
        $textToDisplay.= '<strong>' . $i . "</strong>&nbsp;&nbsp;\n";
    } else {
        $textToDisplay.= '<a href="' . $this->getUrlPage("archive", $category, array("year" => $i)) . '">';
        $textToDisplay.= $i . "</a>&nbsp;&nbsp;\n";
    }
}
$textToDisplay.= "</p>\n";
// End menu bar
// Display table with articles
$articles = $oArticles->getArticlesDataArchive($year, $category);

$previous_month = -1;
$previous_year = -1;
$table_started = false;
$colspan = $loggedIn ? ' colspan="2"' : ""; // Header rows need to be wider
foreach ($articles as $article) {
    $date = strtotime($article->created);
    $current_month = date('n', $date);
    $current_year = date('Y', $date);
    if ($current_month != $previous_month || $current_year != $previous_year) {
        if ($table_started) {
            // Close off existing tables
            $textToDisplay.= "</table>\n";
        }
        // Start new table
        $textToDisplay.= '<table style="width:90%">';
        $textToDisplay.= '<tr><th ' . $colspan . '>' . ucfirst(strftime("%B %Y", $date)) . '</th></tr>';
        $table_started = true;

        // Set new previous values
        $previous_month = $current_month;
        $previous_year = $current_year;
    }

    // The table cell
    $textToDisplay.= '<tr><td><a href="' . $this->getUrlPage("article", $article->id);
    $textToDisplay.= '">' . $article->title . "</a>";
    if ($loggedIn) {
        // Display edit links in new cell
        $textToDisplay.= '</td><td style="width:20%">';
        $textToDisplay.= '<a class="arrow" href="' . $this->getUrlPage("edit_article", $article->id);
        $textToDisplay.= '">' . $this->t("main.edit") . "</a>";
        $textToDisplay.= ' <a class="arrow" href="' . $this->getUrlPage("delete_article", $article->id);
        $textToDisplay.= '">' . $this->t("main.delete") . "</a>";
    }
    $textToDisplay.= "</td></tr>\n";
}

// Close off tables
if ($table_started) {
    $textToDisplay.="</table>\n";
} else {
    // No articles found
    $textToDisplay.="<p><em>" . $this->t("articles.archive.not_found") . "</em></p>";
   
}


unset($oArticles, $oCategories);

// ERROR HANDLING
if (!$this->errorsDisplayed) {
    $this->echoErrors();
}

// DISPLAY TEXT
echo $textToDisplay;
?>