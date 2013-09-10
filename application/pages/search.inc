<?php
$keyword = '';
if (isSet($_REQUEST['searchbox'])) {
    $keyword = $_REQUEST['searchbox'];
}
if (isSet($_REQUEST['page'])) {
    $page = (int) $_REQUEST['page'];
} else {
    $page = 1;
}
?>
<h2><?php echo $this->t("articles.search_for") ?> "<?php if (!empty($keyword)) echo $_REQUEST['searchbox']; ?>" </h2>
<?php
if (strLen($keyword) < 3) {
    $this->addError($this->t("articles.search_term") . " " . $this->t("errors.is_too_short"));
    $result = '<p>' . $this->t("articles.search_term") . " " . $this->tReplaced("errors.is_too_short_num", 3) . '</p>';
} else {
    // Articles
    $result = "<h3 class=\"notable\">" . $this->t('articles.search.results_in_articles') . "</h3>\n"; //resultaten in artikelen
    
    $oArticles = new Articles($this);
    $totalNumberOfArticles = $oArticles->getMatchesFor($keyword);
    $articlesPerPage = ArticleSearchView::ARTICLES_PER_PAGE;
    $startNumber = ArticleSearchView::getStartNumber($page);
    $articles = $oArticles->getArticlesDataMatch($keyword, $articlesPerPage, $startNumber);
    $oSearchView = new ArticleSearchView($this, $keyword, $articles, $page, $totalNumberOfArticles);

    
    $result.= $oSearchView->getText();

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
if (!$this->errorsDisplayed) {
    $this->echoErrors();
}

//ZOEKRESULTATEN WEERGEVEN
echo $result;
?>