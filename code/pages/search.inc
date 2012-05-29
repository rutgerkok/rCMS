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
<h2><?php echo $this->translations[97] ?> "<?php if(!empty($keyword)) echo $_REQUEST['searchbox']; ?>" </h2>
<?php
if(strlen($keyword)<3)
{
	$this->add_error('Search term is too short!');
	$result = '<p>Please search again. Minimum length is three characters.</p>';
}
else
{
	$oDB = new Database($this);
	
	$result = "<h3 class=\"notable\">{$this->translations[98]} {$this->translations[84]} ".strtolower($this->translations[16])."</h3>";//resultaten in artikelen
	$oArticles = new Articles($this,$oDB);
	$result.= $oArticles->get_articles_search($_REQUEST['searchbox'],$page);
	unset($oArticles);
	
	$result.= "<h3 class=\"notable\">{$this->translations[98]} {$this->translations[84]} {$this->translations[99]}</h3>";
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