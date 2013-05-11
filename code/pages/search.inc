<?php
$keyword = '';
if (isset($_REQUEST['searchbox'])) {
    $keyword = $_REQUEST['searchbox'];
}
if (isset($_REQUEST['page'])) {
    $page = (int) $_REQUEST['page'];
} else {
    $page = 1;
}
?>
<h2><?php echo $this->t("articles.search_for") ?> "<?php if (!empty($keyword)) echo $_REQUEST['searchbox']; ?>" </h2>
<?php
if (strlen($keyword) < 3) {
    $this->add_error($this->t("articles.search_term") . " " . $this->t("errors.is_too_short"));
    $result = '<p>' . $this->t("articles.search_term") . " " . $this->t_replaced("errors.is_too_short_num", 3) . '</p>';
} else {
    $oDB = $this->get_database();

    // Articles
    $result = "<h3 class=\"notable\">" . $this->t('articles.search.results_in_articles') . "</h3>\n"; //resultaten in artikelen
    $oArticles = new Articles($this, $oDB);
    $result.= $oArticles->get_articles_search($_REQUEST['searchbox'], $page);
    unset($oArticles);

    // Links
    $oMenu = new Menus($this);
    $links = $oMenu->get_links_search($_REQUEST['searchbox']);
    if (count($links) > 0) {
        $result.= "<h3 class=\"notable\">" . $this->t('articles.search.results_in_links') . "</h3>\n";
        $result.= "<ul class=\"linklist\">\n";
        $result.= $oMenu->get_as_html($links);
        $result.= "</ul>\n";
    }

    unset($oMenu, $oDB, $links);
}





//FOUTAFHANDELING
if (!$this->errorsdisplayed) {
    $this->echo_errors();
}

//ZOEKRESULTATEN WEERGEVEN
echo $result;
?>