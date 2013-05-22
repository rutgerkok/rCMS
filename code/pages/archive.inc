<h2><?php echo $this->t("articles.archive") ?></h2>
<?php
$text_to_display = "";

$year = (int) $this->get_request_string("year", 0);
$category = (int) $this->get_request_string("id", 0);
$logged_in = $this->logged_in_staff(false);

$oArticles = new Articles($this);

// Archive description
$text_to_display.= "<p>" . $this->t("articles.archive.explained") . "</p>";

// Start menu bar
$text_to_display.='<p class="lijn">';

// The categories
// Any category
if ($category == 0) {
    $text_to_display.= '<strong>' . $this->t("categories.all") . "</strong>&nbsp;&nbsp;\n";
} else {
    $text_to_display.= '<a href="' . $this->get_url_page("archive", 0, array("year" => $year)) . '">';
    $text_to_display.= $this->t("categories.all") . "</a>&nbsp;&nbsp;\n";
}
// Other categories
$oCategories = new Categories($this);
$categories = $oCategories->get_categories();
foreach ($categories as $i => $category_name) {
    if ($i == $category) {
        $text_to_display.= '<strong>' . $category_name . "</strong>&nbsp;&nbsp;\n";
    } else {
        $text_to_display.= '<a href="' . $this->get_url_page("archive", $i, array("year" => $year)) . '">';
        $text_to_display.= $category_name . "</a>&nbsp;&nbsp;\n";
    }
}

// The years
// Any year
if ($year == 0) {
    $text_to_display.= '<br /><strong>' . $this->t("articles.archive.any_year") . "</strong>&nbsp;&nbsp;\n";
} else {
    $text_to_display.= '<br /><a href="' . $this->get_url_page("archive", $category, array("year" => 0)) . '">';
    $text_to_display.= $this->t("articles.archive.any_year") . "</a>&nbsp;&nbsp;\n";
}
// Other years
$articles_in_years = $oArticles->get_article_count_in_years($category);
foreach($articles_in_years as $i => $articles_in_year) {
    if ($i == $year) {
        $text_to_display.= '<strong>' . $i . "</strong>&nbsp;&nbsp;\n";
    } else {
        $text_to_display.= '<a href="' . $this->get_url_page("archive", $category, array("year" => $i)) . '">';
        $text_to_display.= $i . "</a>&nbsp;&nbsp;\n";
    }
}
$text_to_display.= "</p>\n";
// End menu bar
// Display table with articles
$articles = $oArticles->get_articles_data_archive($year, $category);

$previous_month = -1;
$previous_year = -1;
$table_started = false;
$colspan = $logged_in ? ' colspan="2"' : ""; // Header rows need to be wider
foreach ($articles as $article) {
    $date = strtotime($article->created);
    $current_month = date('n', $date);
    $current_year = date('Y', $date);
    if ($current_month != $previous_month || $current_year != $previous_year) {
        if ($table_started) {
            // Close off existing tables
            $text_to_display.= "</table>\n";
        }
        // Start new table
        $text_to_display.= '<table style="width:90%">';
        $text_to_display.= '<tr><th ' . $colspan . '>' . ucfirst(strftime("%B %Y", $date)) . '</th></tr>';
        $table_started = true;

        // Set new previous values
        $previous_month = $current_month;
        $previous_year = $current_year;
    }

    // The table cell
    $text_to_display.= '<tr><td><a href="' . $this->get_url_page("article", $article->id);
    $text_to_display.= '">' . $article->title . "</a>";
    if ($logged_in) {
        // Display edit links in new cell
        $text_to_display.= '</td><td style="width:20%">';
        $text_to_display.= '<a class="arrow" href="' . $this->get_url_page("edit_article", $article->id);
        $text_to_display.= '">' . $this->t("main.edit") . "</a>";
        $text_to_display.= ' <a class="arrow" href="' . $this->get_url_page("delete_article", $article->id);
        $text_to_display.= '">' . $this->t("main.delete") . "</a>";
    }
    $text_to_display.= "</td></tr>\n";
}

// Close off tables
if ($table_started) {
    $text_to_display.="</table>\n";
} else {
    // No articles found
    $text_to_display.="<p><em>" . $this->t("articles.archive.not_found") . "</em></p>";
   
}


unset($oArticles, $oCategories);

// ERROR HANDLING
if (!$this->errorsdisplayed) {
    $this->echo_errors();
}

// DISPLAY TEXT
echo $text_to_display;
?>