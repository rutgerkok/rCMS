<?php
$keyword = '';
if(isset($_REQUEST['searchbox']))
{
	$keyword = $_REQUEST['searchbox'];
}
if(isset($_REQUEST['page']))
{
	$page = (int) $_REQUEST['page'];
}
else
{
	$page = 1;
}
?>
<h2><?php echo $this->t("articles.search_for") ?> "<?php if(!empty($keyword)) echo $_REQUEST['searchbox']; ?>" </h2>
<?php
if(strlen($keyword)<3)
{
	$this->add_error($this->t("articles.search_term")." ".$this->t("errors.is_too_short"));
	$result = '<p>'.string_replace("#","3",$this->t("errors.is_too_short_num")).'</p>';
}
else
{
	$oDB = new Database($this);
	
	$result = "<h3 class=\"notable\">".$this->t('articles.search.results_in_articles')."</h3>\n";//resultaten in artikelen
	$oArticles = new Articles($this,$oDB);
	$result.= $oArticles->get_articles_search($_REQUEST['searchbox'],$page);
	unset($oArticles);
	
	$result.= "<h3 class=\"notable\">".$this->t('articles.search.results_in_links')."</h3>\n";
	$oMenu = new Menu($this,$oDB);
	$result.= $oMenu->get_menu_search($_REQUEST['searchbox']);
	unset($oMenu,$oDB);
}





//FOUTAFHANDELING
if(!$this->errorsdisplayed)
{
	$this->echo_errors();
}

//ZOEKRESULTATEN WEERGEVEN
echo $result;

?>