<h2><?php echo $this->t("articles.archive") ?></h2>
<?php
$oDB = $this->get_database();
$oArticles = new Articles($this,$oDB,new Categories($oDB,$this));
if(isset($_REQUEST['year']))
{
	if(isset($_REQUEST['cat']))
	{
		$article_code = $oArticles->get_articles_archive($_REQUEST['year'],$_REQUEST['cat']);
	}
	else
	{
		$article_code = $oArticles->get_articles_archive($_REQUEST['year'],false);
	}
}
else
{
	if(isset($_REQUEST['cat']))
	{
		$article_code = $oArticles->get_articles_archive(0,$_REQUEST['cat']);
	}
	else
	{
		$article_code = $oArticles->get_articles_archive();
	}
}

unset($oArticles);

//FOUTAFHANDELING
if(!$this->errorsdisplayed)
{
	$this->echo_errors();
}

//ARTIKELEN WEERGEVEN
echo $article_code;

?>