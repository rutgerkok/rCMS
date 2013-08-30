<?php

$cat_id = 0; //standaardwaarde
$textToDisplay = '';

$oCats = new Categories($this);

if (isSet($_REQUEST['id'])) {
    $cat_id = (int) $_REQUEST['id'];
}
if ($cat_id > 0) { //geef artikelen in die categorie weer
    $name = $oCats->getCategoryName($cat_id);
    if (!empty($name)) { //geef artikelen in die categorie weer
        echo "<h2>" . $oCats->getCategoryName($cat_id) . "</h2> \n";
        echo '<p style="position:relative;top:-.9em">&nbsp;&nbsp;&nbsp;&nbsp;<a class="arrow" href="' . $this->getUrlPage("category") . '">' . $this->t("categories.show_all") . '</a></p>' . "\n"; //toon alle categorie�n
        $oArticles = new Articles($this, $this->getDatabase());
        $textToDisplay.= $oArticles->get_articles_list_category($cat_id, 9, Articles::METAINFO | Articles::ARCHIVE);
        unset($oArticles); //niet meer nodig

        if (empty($textToDisplay)) {
            // No output? Then there are no categories
            $textToDisplay.= "<p><em>{$this->t("errors.nothing_found")}</em></p>";
        }
    }
}

if (empty($textToDisplay)) {
    // No output? Display list of articles
    echo "<h2>" . $this->t("categories.all") . "</h2>\n"; //alle categori�n
    $cats = $oCats->getCategories();
    $textToDisplay.= "<p>\n";
    foreach ($cats as $cat_id => $name) {
        $textToDisplay.= '<a class="arrow" href="' . $this->getUrlPage("category", $cat_id) . '">' . $name . "</a> <br /> \n";
    }
    $textToDisplay.= '</p>';
}
unset($oDB, $oCats);
//FOUTAFHANDELING
if (!$this->errorsDisplayed) {
    $this->echoErrors();
}

//ARTIKELEN WEERGEVEN
echo $textToDisplay;
?>